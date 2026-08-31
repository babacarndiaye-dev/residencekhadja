<?php

namespace App\Services;

use App\Models\AvailabilityDay;
use App\Models\Reservation;
use App\Models\RoomCategory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Disponibilité par catégorie sur une plage de dates.
 *
 * Deux séjours [in, out) se chevauchent si  in_A < out_B  ET  out_A > in_B.
 * remaining() peut poser un verrou (FOR UPDATE) pour l'anti-double réservation.
 * Depuis la Phase 14, le calendrier ARI (`availability_calendar`) peut réduire
 * l'inventaire ouvert et imposer des restrictions (stop-sell, CTA/CTD, min/max stay).
 */
class Availability
{
    public static function remaining(
        RoomCategory $category,
        string $checkIn,
        string $checkOut,
        ?int $excludeReservationId = null,
        bool $lock = false,
    ): int {
        $sellable = $category->sellableRoomsCount();

        $query = Reservation::query()
            ->where('room_category_id', $category->id)
            ->whereIn('status', Reservation::BLOCKING)
            ->whereDate('check_in', '<', $checkOut)
            ->whereDate('check_out', '>', $checkIn)
            ->when($excludeReservationId, fn ($q) => $q->where('id', '!=', $excludeReservationId));

        if ($lock) {
            $query->lockForUpdate();
        }

        $booked = (int) $query->sum('rooms_count');
        $remaining = max(0, $sellable - $booked);

        // Plafond manuel du calendrier ARI : la plus petite ouverture sur les nuits du séjour.
        if (Schema::hasTable('availability_calendar')) {
            $cap = AvailabilityDay::where('room_category_id', $category->id)
                ->whereNotNull('rooms_open')
                ->whereDate('date', '>=', $checkIn)
                ->whereDate('date', '<', $checkOut)
                ->min('rooms_open');

            if ($cap !== null) {
                $remaining = min($remaining, max(0, (int) $cap - $booked));
            }
        }

        return $remaining;
    }

    /**
     * Restrictions de calendrier qui bloquent un séjour [checkIn, checkOut).
     *
     * @return array<int,string> messages
     */
    public static function stayRestrictions(RoomCategory $category, string $checkIn, string $checkOut): array
    {
        if (! Schema::hasTable('availability_calendar')) {
            return [];
        }

        $in = Carbon::parse($checkIn);
        $out = Carbon::parse($checkOut);
        $nights = max(1, $in->diffInDays($out));

        $days = AvailabilityDay::where('room_category_id', $category->id)
            ->whereDate('date', '>=', $in->toDateString())
            ->whereDate('date', '<', $out->toDateString())
            ->get()
            ->keyBy(fn ($d) => $d->date->toDateString());

        $reasons = [];

        foreach ($days as $iso => $day) {
            if ($day->stop_sell) {
                $reasons[] = 'Vente stoppée le '.Carbon::parse($iso)->format('d/m/Y').'.';
            }
        }

        $arrival = $days->get($in->toDateString());
        if ($arrival?->cta) {
            $reasons[] = 'Arrivée impossible le '.$in->format('d/m/Y').' (fermé à l’arrivée).';
        }
        if ($arrival && $arrival->min_stay > $nights) {
            $reasons[] = "Séjour minimum de {$arrival->min_stay} nuit(s) requis à cette date.";
        }
        if ($arrival && $arrival->max_stay && $nights > $arrival->max_stay) {
            $reasons[] = "Séjour maximum de {$arrival->max_stay} nuit(s) à cette date.";
        }

        $departureDay = AvailabilityDay::where('room_category_id', $category->id)
            ->whereDate('date', $out->toDateString())->first();
        if ($departureDay?->ctd) {
            $reasons[] = 'Départ impossible le '.$out->format('d/m/Y').' (fermé au départ).';
        }

        return array_values(array_unique($reasons));
    }

    public static function canBook(
        RoomCategory $category,
        string $checkIn,
        string $checkOut,
        int $roomsCount,
        bool $lock = false,
    ): bool {
        if (self::stayRestrictions($category, $checkIn, $checkOut) !== []) {
            return false;
        }

        return self::remaining($category, $checkIn, $checkOut, lock: $lock) >= $roomsCount;
    }

    /**
     * Disponibilité de chaque catégorie active, indexée par id.
     *
     * @return array<int,int>
     */
    public static function map(string $checkIn, string $checkOut): array
    {
        return RoomCategory::query()->active()->get()
            ->mapWithKeys(fn (RoomCategory $c) => [
                $c->id => self::canBook($c, $checkIn, $checkOut, 1) ? self::remaining($c, $checkIn, $checkOut) : 0,
            ])
            ->all();
    }
}
