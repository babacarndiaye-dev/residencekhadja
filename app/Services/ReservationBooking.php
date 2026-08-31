<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\RoomCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Persiste une réservation à partir d'un devis (BookingQuote::for()).
 *
 * 1 chambre  → une ligne `reservations`, référence simple HRK-XXXXXX.
 * N chambres → une ligne par chambre, reliées par `group_reference`, références
 *              HRK-XXXXXX-1, -2, … Un seul traitement (confirmation, check-in,
 *              check-out) et une seule facture consolidée côté back-office.
 *
 * Le découpage suppléments / remise / taxe de séjour / TVA garantit que
 * somme(reservations.total) === total du devis groupé, au FCFA près : la 1re
 * chambre porte les montants partagés + le reliquat de TVA, les chambres 2..N
 * portent uniquement la TVA de leur hébergement.
 */
class ReservationBooking
{
    /**
     * @param  array<string,mixed>  $base  Attributs communs à toutes les chambres
     *                                     (hotel_id, guest_id, rate_plan_id, status,
     *                                     channel, check_in, check_out, adults,
     *                                     children, currency, …). NE DOIT PAS contenir
     *                                     reference / group_reference / room_category_id
     *                                     ni les colonnes de montants.
     * @param  array<int,array{cat:RoomCategory, room_total:int}>  $units  une entrée par chambre physique
     * @param  array<string,mixed>  $group  sortie de BookingQuote::for()
     * @param  array{promo?:?string, extras?:array<int,string>}  $meta
     * @return Collection<int, Reservation>
     */
    public static function persist(array $base, array $units, array $group, array $meta = []): Collection
    {
        $units = array_values($units);
        $grouped = count($units) > 1;
        $groupRef = self::reference();
        $taxRate = (float) ($group['tax_rate'] ?? 0);

        // TVA portée par les chambres 2..N : le reliquat revient à la 1re.
        $otherTax = 0;
        foreach (array_slice($units, 1) as $u) {
            $otherTax += (int) round($u['room_total'] * $taxRate);
        }

        $created = collect();
        foreach ($units as $i => $u) {
            $primary = $i === 0;
            $roomTotal = (int) $u['room_total'];
            $extras = $primary ? (int) ($group['extras_total'] ?? 0) : 0;
            $discount = $primary ? (int) ($group['discount_amount'] ?? 0) : 0;
            $tourist = $primary ? (int) ($group['tourist_tax'] ?? 0) : 0;
            $tax = $primary
                ? (int) ($group['tax'] ?? 0) - $otherTax
                : (int) round($roomTotal * $taxRate);
            $total = $roomTotal + $extras - $discount + $tax + $tourist;

            $created->push(Reservation::create($base + [
                'reference' => $grouped ? $groupRef.'-'.($i + 1) : $groupRef,
                'group_reference' => $grouped ? $groupRef : null,
                'room_category_id' => $u['cat']->id,
                'rooms_count' => 1,
                'room_total' => $roomTotal,
                'extras_total' => $extras,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'tourist_tax' => $tourist,
                'total' => $total,
                'deposit' => (int) round($total * 0.30),
                'promo_code' => $primary ? ($meta['promo'] ?? null) : null,
                'extras' => $primary ? ($meta['extras'] ?? []) : [],
            ]));
        }

        return $created;
    }

    /** Génère une référence HRK-XXXXXX unique. */
    public static function reference(): string
    {
        do {
            $ref = 'HRK-'.strtoupper(Str::random(6));
        } while (Reservation::query()->where('reference', $ref)->exists());

        return $ref;
    }
}
