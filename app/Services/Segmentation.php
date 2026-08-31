<?php

namespace App\Services;

use App\Models\Guest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Traduit une "definition" de segment (tableau de règles) en requête Guest (§19, §54).
 *
 * Règles reconnues :
 *  - opted_in        : bool  → consentement marketing
 *  - min_stays       : int   → séjours honorés ≥ n
 *  - min_spend       : int   → dépenses cumulées ≥ n (FCFA)
 *  - country         : string
 *  - tier            : string (code de palier)
 *  - has_tag         : string
 *  - never_stayed    : bool  → aucun séjour honoré
 *  - inactive_days   : int   → dernier départ il y a ≥ n jours
 *  - birthday_month  : int|"current"
 */
class Segmentation
{
    /** @param array<string,mixed> $definition */
    public static function query(array $definition): Builder
    {
        $q = Guest::query();

        foreach ($definition as $rule => $value) {
            match ($rule) {
                'opted_in' => $value ? $q->where('marketing_opt_in', true) : null,

                'min_stays' => $q->whereHas(
                    'reservations',
                    fn (Builder $r) => $r->whereIn('status', ['checked_in', 'checked_out']),
                    '>=',
                    (int) $value
                ),

                'min_spend' => $q->whereIn('id', fn ($sub) => $sub->from('reservations')
                    ->selectRaw('guest_id')
                    ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
                    ->groupBy('guest_id')
                    ->havingRaw('SUM(total) >= ?', [(int) $value])),

                'country' => $q->where('country', $value),

                'tier' => $q->whereHas('loyaltyAccount.tier', fn (Builder $t) => $t->where('code', $value)),

                'has_tag' => $q->whereJsonContains('tags', $value),

                'never_stayed' => $value ? $q->whereDoesntHave(
                    'reservations',
                    fn (Builder $r) => $r->whereIn('status', ['checked_in', 'checked_out'])
                ) : null,

                'inactive_days' => $q->whereDoesntHave(
                    'reservations',
                    fn (Builder $r) => $r->whereIn('status', ['checked_in', 'checked_out'])
                        ->where('check_out', '>=', now()->subDays((int) $value)->toDateString())
                )->whereHas(
                    'reservations',
                    fn (Builder $r) => $r->whereIn('status', ['checked_in', 'checked_out'])
                ),

                'birthday_month' => $q->whereNotNull('birthdate')->whereMonth(
                    'birthdate',
                    $value === 'current' ? (int) now()->month : (int) $value
                ),

                default => null,
            };
        }

        return $q;
    }

    public static function count(array $definition): int
    {
        return self::query($definition)->count();
    }

    /** @return Collection<int,Guest> */
    public static function preview(array $definition, int $limit = 20)
    {
        return self::query($definition)->orderBy('last_name')->limit($limit)->get();
    }
}
