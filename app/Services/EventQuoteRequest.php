<?php

namespace App\Services;

use App\Models\EventLead;
use App\Models\EventQuote;
use App\Models\EventSpace;
use App\Models\Hotel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Demande de devis « salle / séminaire » : crée une affaire dans le pipeline
 * commercial + un devis brouillon pré-rempli (location + restauration + technique).
 *
 * Alimenté par le formulaire public « Séminaires & Événements » (visiteur) et par
 * le back-office « Salles » (/admin/salles). Le devis atterrit dans l'éditeur de
 * devis du module Événements (lignes, envoi, acceptation → événement en option).
 */
class EventQuoteRequest
{
    /**
     * @param  array<string,mixed>  $data  company?, contact_name, contact_email?, contact_phone?,
     *                                     event_type, event_space_id?, date?, duration?, pax,
     *                                     layout?, catering? (codes), equipment? (codes), message?
     */
    public static function create(array $data, ?int $ownerId, string $activityNote): EventQuote
    {
        $pax = max(1, (int) ($data['pax'] ?? 1));
        $duration = ($data['duration'] ?? 'full_day') === 'half_day' ? 'half_day' : 'full_day';
        $space = ! empty($data['event_space_id']) ? EventSpace::find($data['event_space_id']) : null;
        $company = ! empty($data['company']) ? $data['company'] : null;
        $typeLabel = config("events.event_types.{$data['event_type']}", $data['event_type']);
        $date = ! empty($data['date']) ? Carbon::parse($data['date'])->toDateString() : null;

        // Lignes du devis à partir des sélections.
        $lines = [];

        if ($space) {
            $unit = $duration === 'half_day' ? $space->half_day_price : $space->full_day_price;
            $lines[] = [
                'category' => 'location',
                'label' => 'Location '.$space->name.(! empty($data['layout']) ? ' — disposition '.$data['layout'] : ''),
                'quantity' => 1,
                'unit' => $duration === 'half_day' ? 'demi-journée' : 'journée',
                'unit_price' => (int) $unit,
            ];
        }

        $packages = collect(config('events.catering_packages'))->keyBy('code');
        foreach ((array) ($data['catering'] ?? []) as $code) {
            if (! $pkg = $packages->get($code)) {
                continue;
            }
            $lines[] = [
                'category' => $pkg['category'],
                'label' => $pkg['name'],
                'quantity' => $pax,
                'unit' => 'pers.',
                'unit_price' => (int) $pkg['per_pax_price'],
            ];
        }

        $gear = collect(config('events.av_equipment'))->keyBy('code');
        foreach ((array) ($data['equipment'] ?? []) as $code) {
            if (! $eq = $gear->get($code)) {
                continue;
            }
            $lines[] = [
                'category' => 'technique',
                'label' => $eq['name'],
                'quantity' => 1,
                'unit' => $eq['unit'],
                'unit_price' => (int) $eq['price'],
            ];
        }

        $estimate = collect($lines)->sum(fn ($l) => EventQuoteBuilder::lineTotal($l['quantity'], $l['unit_price']));

        return DB::transaction(function () use ($data, $pax, $space, $company, $typeLabel, $lines, $estimate, $ownerId, $activityNote, $date) {
            $lead = EventLead::create([
                'hotel_id' => Hotel::current()->id,
                'reference' => 'LEAD-'.now()->format('y').strtoupper(Str::random(4)),
                'company' => $company,
                'contact_name' => $data['contact_name'],
                'contact_email' => $data['contact_email'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'event_type' => $data['event_type'],
                'expected_start' => $date,
                'expected_end' => $date,
                'pax' => $pax,
                'status' => 'nouveau',
                'estimated_value' => $estimate,
                'source' => 'salle',
                'owner_id' => $ownerId,
                'notes' => $data['message'] ?? null,
            ]);

            $quote = EventQuote::create(EventQuoteBuilder::defaults() + [
                'event_lead_id' => $lead->id,
                'reference' => EventQuoteBuilder::nextReference(),
                'title' => ($company ?: $data['contact_name']).' — '.$typeLabel,
                'pax' => $pax,
                'created_by' => $ownerId,
            ]);

            $order = 0;
            foreach ($lines as $l) {
                $quote->items()->create([
                    'category' => $l['category'],
                    'label' => $l['label'],
                    'quantity' => $l['quantity'],
                    'unit' => $l['unit'],
                    'unit_price' => $l['unit_price'],
                    'total' => EventQuoteBuilder::lineTotal($l['quantity'], $l['unit_price']),
                    'sort_order' => $order++,
                ]);
            }
            EventQuoteBuilder::recalculate($quote);

            $lead->activities()->create([
                'user_id' => $ownerId,
                'type' => 'note',
                'subject' => $activityNote,
                'body' => $space ? "Salle souhaitée : {$space->name}" : 'Salle à définir',
                'occurred_at' => now(),
                'done' => false,
            ]);

            return $quote->load('lead');
        });
    }
}
