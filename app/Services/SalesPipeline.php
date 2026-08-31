<?php

namespace App\Services;

use App\Models\EventLead;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Progression du pipeline commercial événementiel (§21).
 */
class SalesPipeline
{
    /** Transitions autorisées depuis chaque étape. */
    public const TRANSITIONS = [
        'nouveau' => ['qualifie', 'perdu'],
        'qualifie' => ['devis', 'negociation', 'perdu'],
        'devis' => ['negociation', 'gagne', 'perdu'],
        'negociation' => ['devis', 'gagne', 'perdu'],
        'gagne' => [],
        'perdu' => ['nouveau'],
    ];

    public static function advance(EventLead $lead, string $to, array $meta = []): EventLead
    {
        $allowed = self::TRANSITIONS[$lead->status] ?? [];

        if (! in_array($to, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Transition {$lead->statusLabel()} → {$to} non autorisée.",
            ]);
        }

        if ($to === 'perdu' && empty($meta['lost_reason'])) {
            throw ValidationException::withMessages(['lost_reason' => 'Indiquez le motif de perte.']);
        }

        if ($to === 'gagne' && $lead->quotes()->where('status', 'accepted')->doesntExist()) {
            throw ValidationException::withMessages([
                'status' => 'Un devis accepté est requis pour marquer l’affaire gagnée.',
            ]);
        }

        $lead->update([
            'status' => $to,
            'lost_reason' => $to === 'perdu' ? $meta['lost_reason'] : null,
        ]);

        return $lead->refresh();
    }

    /** @return array<string,Collection> */
    public static function board(?int $hotelId = null): array
    {
        $leads = EventLead::query()
            ->when($hotelId, fn ($q) => $q->where('hotel_id', $hotelId))
            ->with('owner')
            ->orderByDesc('estimated_value')
            ->get()
            ->groupBy('status');

        $stages = [];
        foreach (array_keys(config('events.pipeline_stages')) as $stage) {
            $stages[$stage] = $leads->get($stage) ?? collect();
        }

        return $stages;
    }

    public static function weightedForecast(?int $hotelId = null): int
    {
        $weights = ['nouveau' => 0.1, 'qualifie' => 0.3, 'devis' => 0.5, 'negociation' => 0.7];

        return (int) EventLead::query()
            ->when($hotelId, fn ($q) => $q->where('hotel_id', $hotelId))
            ->whereIn('status', array_keys($weights))
            ->get()
            ->sum(fn (EventLead $l) => $l->estimated_value * $weights[$l->status]);
    }
}
