<?php

namespace App\Support;

use App\Models\Reservation;

/**
 * Déduit un profil (« segment ») à partir d'un séjour — VIP, professionnel,
 * famille… Sert de badge sur la fiche chambre (GEMS §1) et servira de base
 * à la personnalisation de l'espace client (GEMS §7, phase IA).
 *
 * @phpstan-type Segment array{key: string, label: string, icon: string}
 */
class GuestSegment
{
    /** @return Segment|null */
    public static function for(Reservation $stay): ?array
    {
        $guest = $stay->guest;
        $tier = $guest?->loyaltyAccount?->tier?->name;

        foreach (config('gems.segments', []) as $key => $rule) {
            $match = match (true) {
                ! empty($rule['loyalty_tiers']) => $tier && in_array($tier, $rule['loyalty_tiers'], true),
                ! empty($rule['requires_company']) => filled($guest?->company),
                isset($rule['min_children']) => (int) $stay->children >= (int) $rule['min_children'],
                default => false,
            };

            if ($match) {
                return ['key' => $key, 'label' => $rule['label'], 'icon' => $rule['icon'] ?? 'star'];
            }
        }

        return null;
    }
}
