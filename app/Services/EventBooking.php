<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventSpace;
use App\Models\EventSpaceBooking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Réservation de salles événementielles — anti-double réservation.
 *
 * Deux créneaux [start, end) se chevauchent si start_A < end_B ET end_A > start_B.
 * Seuls les événements option / confirmé / réalisé bloquent une salle.
 */
class EventBooking
{
    /** Réservations qui entrent en conflit avec le créneau donné pour une salle. */
    public static function conflicts(
        EventSpace $space,
        Carbon $startsAt,
        Carbon $endsAt,
        ?int $excludeEventId = null,
        bool $lock = false,
    ) {
        $q = EventSpaceBooking::query()
            ->where('event_space_id', $space->id)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->whereHas('event', fn ($e) => $e->whereIn('status', Event::BLOCKING))
            ->when($excludeEventId, fn ($qq) => $qq->where('event_id', '!=', $excludeEventId));

        if ($lock) {
            $q->lockForUpdate();
        }

        return $q->with('event')->get();
    }

    public static function isFree(EventSpace $space, Carbon $startsAt, Carbon $endsAt, ?int $excludeEventId = null): bool
    {
        return self::conflicts($space, $startsAt, $endsAt, $excludeEventId)->isEmpty();
    }

    /** Affecte une salle à un événement sur un créneau (transaction + verrou). */
    public static function book(
        Event $event,
        EventSpace $space,
        Carbon $startsAt,
        Carbon $endsAt,
        ?string $layout = null,
        ?string $setupNotes = null,
    ): EventSpaceBooking {
        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            throw ValidationException::withMessages(['ends_at' => 'La fin doit suivre le début.']);
        }

        return DB::transaction(function () use ($event, $space, $startsAt, $endsAt, $layout, $setupNotes) {
            $conflicts = self::conflicts($space, $startsAt, $endsAt, $event->id, lock: true);

            if ($conflicts->isNotEmpty()) {
                $other = $conflicts->first()->event;
                throw ValidationException::withMessages([
                    'event_space_id' => "{$space->name} est déjà réservée sur ce créneau ({$other->reference} — {$other->name}).",
                ]);
            }

            return $event->spaceBookings()->create([
                'event_space_id' => $space->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'layout' => $layout,
                'setup_notes' => $setupNotes,
            ]);
        });
    }

    /** Passe une option en confirmé après re-vérification de toutes ses salles. */
    public static function confirm(Event $event): Event
    {
        if ($event->status === 'annule') {
            throw ValidationException::withMessages(['status' => 'Événement annulé.']);
        }

        return DB::transaction(function () use ($event) {
            foreach ($event->spaceBookings()->with('space')->get() as $booking) {
                $conflicts = self::conflicts(
                    $booking->space,
                    $booking->starts_at,
                    $booking->ends_at,
                    $event->id,
                    lock: true,
                );
                if ($conflicts->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'status' => "Conflit sur {$booking->space->name} : confirmation impossible.",
                    ]);
                }
            }

            $event->update(['status' => 'confirme', 'option_expires_on' => null]);

            return $event->refresh();
        });
    }
}
