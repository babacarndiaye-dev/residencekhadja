<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\PoolAsset;
use App\Models\PoolReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Réservation d'un transat / bain / cabana au bord de la piscine, par créneau
 * (matinée / après-midi / journée). Anti-double-réservation sur
 * (équipement, date, créneaux qui se chevauchent).
 */
class PoolBooking
{
    public static function isFree(PoolAsset $asset, string $date, string $slot, ?int $ignoreId = null): bool
    {
        return ! PoolReservation::query()
            ->where('pool_asset_id', $asset->id)
            ->whereDate('date', $date)
            ->whereIn('slot', PoolReservation::OVERLAP[$slot] ?? [$slot])
            ->blocking()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    /**
     * @param  array{pool_asset_id:int,date:string,slot:string,guest_name:string,guest_phone?:?string,guests?:int,reservation_id?:?int,note?:?string,price?:?int}  $data
     */
    public static function book(array $data, ?int $userId = null): PoolReservation
    {
        return DB::transaction(function () use ($data, $userId) {
            $asset = PoolAsset::where('is_active', true)->lockForUpdate()->findOrFail($data['pool_asset_id']);

            if (! self::isFree($asset, $data['date'], $data['slot'])) {
                throw ValidationException::withMessages([
                    'pool_asset_id' => "{$asset->label} est déjà réservé sur ce créneau.",
                ]);
            }

            $guests = (int) ($data['guests'] ?? 1);
            if ($guests > $asset->capacity) {
                throw ValidationException::withMessages([
                    'guests' => "{$asset->label} accueille au maximum {$asset->capacity} personne(s).",
                ]);
            }

            return PoolReservation::create([
                'hotel_id' => Hotel::current()->id,
                'pool_asset_id' => $asset->id,
                'reservation_id' => $data['reservation_id'] ?? null,
                'guest_name' => $data['guest_name'],
                'guest_phone' => $data['guest_phone'] ?? null,
                'date' => $data['date'],
                'slot' => $data['slot'],
                'guests' => max(1, $guests),
                'price' => $data['price'] ?? $asset->priceFor($data['slot']),
                'status' => 'booked',
                'note' => $data['note'] ?? null,
                'created_by' => $userId,
            ]);
        });
    }

    /** Disponibilités d'une date : par équipement, l'état de chaque créneau. */
    public static function board(string $date): array
    {
        $assets = PoolAsset::where('hotel_id', Hotel::current()->id)
            ->orderBy('sort_order')->orderBy('label')->get();

        $reservations = PoolReservation::with('asset', 'reservation.guest')
            ->where('hotel_id', Hotel::current()->id)
            ->whereDate('date', $date)
            ->get()
            ->groupBy('pool_asset_id');

        return $assets->map(fn (PoolAsset $a) => [
            'asset' => $a,
            'reservations' => $reservations->get($a->id, collect())->sortBy('slot')->values(),
        ])->all();
    }
}
