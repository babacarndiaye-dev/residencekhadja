<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\HousekeepingTask;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Support\Carbon;

/**
 * Génère le plan de ménage d'une journée à partir de l'état des chambres
 * et des mouvements (départs, séjours en cours).
 */
class HousekeepingPlanner
{
    public static function generateForDate(Hotel $hotel, Carbon $date): int
    {
        $created = 0;

        $rooms = Room::where('hotel_id', $hotel->id)->where('is_active', true)->get();

        foreach ($rooms as $room) {
            [$type, $reservationId, $priority] = self::classify($room, $date);

            if (! $type) {
                continue;
            }

            if (self::taskExists($room->id, $date, $type)) {
                continue;
            }

            HousekeepingTask::create([
                'hotel_id' => $hotel->id,
                'room_id' => $room->id,
                'reservation_id' => $reservationId,
                'service_date' => $date->toDateString(),
                'type' => $type,
                'status' => 'pending',
                'priority' => $priority,
            ]);
            $created++;
        }

        return $created;
    }

    public static function taskExists(int $roomId, Carbon $date, string $type): bool
    {
        return HousekeepingTask::where('room_id', $roomId)
            ->whereDate('service_date', $date)
            ->where('type', $type)
            ->exists();
    }

    /** @return array{0:?string,1:?int,2:string} [type, reservation_id, priority] */
    private static function classify(Room $room, Carbon $date): array
    {
        $departure = Reservation::where('room_id', $room->id)
            ->whereDate('check_out', $date)
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->first();

        if ($departure) {
            return ['departure', $departure->id, 'high'];
        }

        $stay = Reservation::where('room_id', $room->id)
            ->where('status', 'checked_in')
            ->whereDate('check_in', '<', $date)
            ->whereDate('check_out', '>', $date)
            ->first();

        if ($stay) {
            return ['stayover', $stay->id, 'normal'];
        }

        if ($room->status === 'sale') {
            return ['departure', null, 'high'];
        }

        return [null, null, 'normal'];
    }
}
