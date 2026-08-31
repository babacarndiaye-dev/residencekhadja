<?php

namespace App\Services;

use App\Models\PromoCode;
use App\Models\RoomCategory;

/**
 * Calcule un devis de séjour à partir de l'état du tunnel de réservation
 * stocké en session. Aucune persistance : uniquement de l'affichage.
 *
 * Multi-chambres : `booking['room_lines']` = [['slug'=>…, 'qty'=>n], …].
 * Rétro-compatibilité : `booking['room_slug']` (+ `rooms`) est converti en une
 * seule ligne. Un plan tarifaire unique (`booking['rate_plan']`) s'applique à
 * toutes les lignes.
 */
class BookingQuote
{
    /**
     * @param  array<string,mixed>  $booking
     * @return array<string,mixed>
     */
    public static function for(array $booking): array
    {
        $cfg = config('booking');

        $nights = max(1, nights_between($booking['check_in'] ?? null, $booking['check_out'] ?? null) ?: 1);
        $adults = max(1, (int) ($booking['adults'] ?? 2));
        $children = max(0, (int) ($booking['children'] ?? 0));
        $guests = $adults + $children;

        $ratePlanKey = $booking['rate_plan'] ?? 'flexible';
        $ratePlan = $cfg['rate_plans'][$ratePlanKey] ?? $cfg['rate_plans']['flexible'];

        // --- Lignes de chambres -------------------------------------------------
        $rawLines = self::normaliseLines($booking);
        $slugs = collect($rawLines)->pluck('slug')->filter()->unique()->all();
        $categories = $slugs
            ? RoomCategory::query()->whereIn('slug', $slugs)->get()->keyBy('slug')
            : collect();

        $lines = [];
        $roomTotal = 0;
        $roomCount = 0;
        foreach ($rawLines as $line) {
            $cat = $categories->get($line['slug']);
            $qty = max(0, (int) ($line['qty'] ?? 0));
            if (! $cat || $qty < 1) {
                continue;
            }
            $nightly = (int) round($cat['price'] * $ratePlan['multiplier']);
            $lineTotal = $nightly * $nights * $qty;
            $roomTotal += $lineTotal;
            $roomCount += $qty;
            $lines[] = [
                'slug' => $cat['slug'],
                'name' => $cat['name'],
                'category' => $cat['category'] ?? null,
                'qty' => $qty,
                'nightly' => $nightly,
                'nights' => $nights,
                'line_total' => $lineTotal,
                'capacity' => $cat['capacity'] ?? null,
            ];
        }

        // --- Services additionnels -------------------------------------------------
        $extraLines = [];
        $extrasTotal = 0;
        $selected = $booking['extras'] ?? [];
        foreach ($cfg['extras'] as $extra) {
            if (! in_array($extra['key'], $selected, true)) {
                continue;
            }
            $amount = match ($extra['per']) {
                'guest_night' => $extra['price'] * $guests * $nights,
                'guest' => $extra['price'] * $guests,
                default => $extra['price'], // 'stay'
            };
            $extraLines[] = ['name' => $extra['name'], 'detail' => $extra['unit'], 'amount' => $amount];
            $extrasTotal += $amount;
        }

        $touristTax = $cfg['tourist_tax'] * $guests * $nights;
        $subtotal = $roomTotal + $extrasTotal;

        // --- Code promo ---------------------------------------------------------
        $discount = null;
        $discountAmount = 0;
        $promoCodes = array_merge($cfg['promo_codes'] ?? [], PromoCode::activeMap());
        $code = strtoupper(trim((string) ($booking['promo'] ?? '')));
        if ($code !== '' && isset($promoCodes[$code])) {
            $promo = $promoCodes[$code];
            $discountAmount = $promo['type'] === 'percent'
                ? (int) round($subtotal * $promo['value'] / 100)
                : (int) $promo['value'];
            $discountAmount = min($discountAmount, $subtotal);
            $discount = ['label' => $promo['label'], 'amount' => $discountAmount];
        }

        $taxable = max(0, $subtotal - $discountAmount);
        $tax = (int) round($taxable * $cfg['tax_rate']);
        $total = $taxable + $tax + $touristTax;
        $deposit = (int) round($total * 0.30);

        return [
            'nights' => $nights,
            'rooms' => max(1, $roomCount),
            'room_count' => $roomCount,
            'adults' => $adults,
            'children' => $children,
            'guests' => $guests,
            'room_lines' => $lines,
            // Compat : première catégorie sélectionnée (vues / code hérités).
            'room' => $lines ? $categories->get($lines[0]['slug']) : null,
            'rate_plan' => ['key' => $ratePlanKey] + $ratePlan,
            'nightly' => $lines[0]['nightly'] ?? 0,
            'room_total' => $roomTotal,
            'extra_lines' => $extraLines,
            'extras_total' => $extrasTotal,
            'tourist_tax' => $touristTax,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'discount_amount' => $discountAmount,
            'tax' => $tax,
            'tax_rate' => $cfg['tax_rate'],
            'total' => $total,
            'deposit' => $deposit,
        ];
    }

    /**
     * Normalise l'état de session en une liste de lignes [{slug, qty}].
     *
     * @return array<int, array{slug:string, qty:int}>
     */
    public static function normaliseLines(array $booking): array
    {
        $lines = $booking['room_lines'] ?? null;

        if (is_array($lines) && $lines) {
            return collect($lines)
                ->map(fn ($l) => ['slug' => (string) ($l['slug'] ?? ''), 'qty' => max(0, (int) ($l['qty'] ?? 0))])
                ->filter(fn ($l) => $l['slug'] !== '' && $l['qty'] > 0)
                ->values()
                ->all();
        }

        // Rétro-compat : ancienne sélection mono-catégorie.
        if (! empty($booking['room_slug'])) {
            return [['slug' => (string) $booking['room_slug'], 'qty' => max(1, (int) ($booking['rooms'] ?? 1))]];
        }

        return [];
    }
}
