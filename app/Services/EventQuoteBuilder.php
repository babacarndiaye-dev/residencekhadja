<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventQuote;
use App\Models\Hotel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Calcul des totaux d'un devis événementiel + conversion en événement (option).
 */
class EventQuoteBuilder
{
    /** Recalcule sous-total / TVA / total / acompte depuis les lignes. */
    public static function recalculate(EventQuote $quote): EventQuote
    {
        $subtotal = (int) $quote->items()->sum('total');
        $discount = min((int) $quote->discount_amount, $subtotal);
        $taxable = max(0, $subtotal - $discount);
        $tax = (int) round($taxable * (float) ($quote->tax_rate ?? config('events.default_tax_rate')));
        $total = $taxable + $tax;
        $depositRate = (float) ($quote->deposit_rate ?? config('events.default_deposit_rate'));

        $quote->forceFill([
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'total' => $total,
            'deposit_amount' => (int) round($total * $depositRate),
        ])->save();

        return $quote->refresh();
    }

    /** Ligne calculée : total = quantité × prix unitaire. */
    public static function lineTotal(float $quantity, int $unitPrice): int
    {
        return (int) round($quantity * $unitPrice);
    }

    /**
     * Accepte un devis → crée un événement en option (créneau à préciser).
     * L'éventuel lead passe à « gagné ».
     */
    public static function accept(EventQuote $quote, ?Carbon $startsAt = null, ?Carbon $endsAt = null): Event
    {
        if ($quote->status === 'accepted' && $quote->event) {
            return $quote->event;
        }
        if ($quote->status === 'declined') {
            throw ValidationException::withMessages(['status' => 'Devis refusé.']);
        }

        return DB::transaction(function () use ($quote, $startsAt, $endsAt) {
            $lead = $quote->lead;
            $start = $startsAt
                ?? $lead?->expected_start?->copy()->setTime(9, 0)
                ?? now()->addWeeks(2)->setTime(9, 0);
            $end = $endsAt
                ?? $lead?->expected_end?->copy()->setTime(18, 0)
                ?? (clone $start)->setTime(18, 0);

            $event = Event::create([
                'hotel_id' => $quote->hotel_id,
                'reference' => 'EVT-'.strtoupper(Str::random(6)),
                'name' => $quote->title,
                'event_type' => $lead?->event_type ?? 'seminaire',
                'event_lead_id' => $lead?->id,
                'event_quote_id' => $quote->id,
                'client_name' => $lead?->company ?: ($lead?->contact_name ?? $quote->title),
                'guest_id' => $lead?->guest_id,
                'pax' => $quote->pax ?: ($lead?->pax ?? 0),
                'status' => 'option',
                'starts_at' => $start,
                'ends_at' => $end,
                'option_expires_on' => now()->addDays(config('events.option_hold_days', 7))->toDateString(),
                'created_by' => $quote->created_by,
            ]);

            $quote->update(['status' => 'accepted']);
            $lead?->update(['status' => 'gagne']);

            return $event;
        });
    }

    public static function nextReference(): string
    {
        do {
            $ref = 'DEVIS-'.now()->format('y').strtoupper(Str::random(4));
        } while (EventQuote::where('reference', $ref)->exists());

        return $ref;
    }

    public static function defaults(): array
    {
        return [
            'hotel_id' => Hotel::current()->id,
            'currency' => 'XOF',
            'tax_rate' => config('events.default_tax_rate', 0.18),
            'deposit_rate' => config('events.default_deposit_rate', 0.30),
            'valid_until' => now()->addDays(config('events.quote_validity_days', 14))->toDateString(),
            'status' => 'draft',
        ];
    }
}
