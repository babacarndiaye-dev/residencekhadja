<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventLead;
use App\Models\EventQuote;
use App\Models\EventSpace;
use App\Models\Hotel;
use App\Models\User;
use App\Services\EventBooking;
use App\Services\EventQuoteBuilder;
use App\Services\FinanceLedger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EventsSeeder extends Seeder
{
    public function run(): void
    {
        $hotel = Hotel::current();

        /* --------------------------- Utilisateur --------------------------- */
        $commercial = User::updateOrCreate(
            ['email' => 'commercial@residence-khadija.sn'],
            [
                'name' => 'Pôle Commercial & Événements',
                'role' => 'commercial',
                'job_title' => 'Responsable commercial MICE',
                'hotel_id' => $hotel->id,
                'is_active' => true,
                'password' => Hash::make('khadija'),
            ],
        );

        /* ----------------------------- Salles ----------------------------- */
        foreach (config('spaces.meeting_rooms') as $i => $room) {
            $layouts = collect($room['capacities'])
                ->mapWithKeys(fn ($cap, $name) => [
                    match ($name) {
                        'Réunion', 'Comité' => $name,
                        default => $name,
                    } => $cap,
                ])->all();

            $max = max($room['capacities']);
            EventSpace::updateOrCreate(
                ['slug' => Str::slug($room['name'])],
                [
                    'hotel_id' => $hotel->id,
                    'name' => $room['name'],
                    'area' => $room['area'],
                    'layouts' => $layouts,
                    'features' => $room['features'],
                    'half_day_price' => $max * 1200,
                    'full_day_price' => $max * 2000,
                    'image' => $room['image'] ?? null,
                    'is_active' => true,
                    'sort_order' => $i,
                ],
            );
        }

        if (Event::count() > 0) {
            return;
        }

        $spaces = EventSpace::orderBy('sort_order')->get();

        /* ------------------------------ Leads ----------------------------- */
        $leadsData = [
            ['Sonatel', 'M. Diagne', 'seminaire', 'negociation', 120, 6_500_000, -5, 20],
            ['Ministère du Tourisme', 'Mme Faye', 'conference', 'devis', 300, 14_000_000, -2, 35],
            ['BOA Sénégal', 'M. Touré', 'gala', 'qualifie', 220, 12_000_000, 10, 40],
            ['Famille Ndiaye', 'Mme Ndiaye', 'mariage', 'nouveau', 350, 9_000_000, 25, 60],
            ['Orange Digital Center', 'M. Sy', 'lancement', 'devis', 90, 4_200_000, 8, 30],
            ['CBAO', 'M. Camara', 'reunion', 'perdu', 40, 1_500_000, -10, -3],
        ];

        $leads = collect($leadsData)->map(function ($d) use ($hotel, $commercial) {
            [$company, $contact, $type, $status, $pax, $value, $startOffset, $endOffset] = $d;

            return EventLead::create([
                'hotel_id' => $hotel->id,
                'reference' => 'LEAD-'.now()->format('y').strtoupper(Str::random(4)),
                'company' => $company,
                'contact_name' => $contact,
                'contact_email' => Str::slug($company).'@example.sn',
                'contact_phone' => '+221 33 8'.random_int(10, 99).' '.random_int(10, 99).' '.random_int(10, 99),
                'event_type' => $type,
                'expected_start' => now()->addDays($startOffset)->toDateString(),
                'expected_end' => now()->addDays($startOffset + 1)->toDateString(),
                'pax' => $pax,
                'status' => $status,
                'estimated_value' => $value,
                'source' => 'site',
                'owner_id' => $commercial->id,
                'lost_reason' => $status === 'perdu' ? 'Budget non validé par le client' : null,
            ]);
        });

        $leads->take(3)->each(function (EventLead $lead) use ($commercial) {
            $lead->activities()->create([
                'user_id' => $commercial->id,
                'type' => 'call',
                'subject' => 'Point d’avancement avec le client',
                'body' => 'Client demande une option sur la grande salle + pause gourmande.',
                'occurred_at' => now()->subDays(random_int(1, 6)),
            ]);
            $lead->activities()->create([
                'user_id' => $commercial->id,
                'type' => 'task',
                'subject' => 'Envoyer le devis révisé',
                'due_at' => now()->addDays(2),
                'occurred_at' => now(),
                'done' => false,
            ]);
        });

        /* ------------------------------ Devis ----------------------------- */
        $makeQuote = function (EventLead $lead, array $items) use ($commercial) {
            $quote = EventQuote::create(EventQuoteBuilder::defaults() + [
                'event_lead_id' => $lead->id,
                'reference' => EventQuoteBuilder::nextReference(),
                'title' => $lead->company.' — '.$lead->typeLabel(),
                'pax' => $lead->pax,
                'created_by' => $commercial->id,
                'status' => 'sent',
            ]);
            foreach ($items as $order => $it) {
                $quote->items()->create([
                    'category' => $it[0], 'label' => $it[1], 'quantity' => $it[2],
                    'unit' => $it[3], 'unit_price' => $it[4],
                    'total' => EventQuoteBuilder::lineTotal($it[2], $it[4]), 'sort_order' => $order,
                ]);
            }

            return EventQuoteBuilder::recalculate($quote);
        };

        $sonatel = $leads[0];
        $q1 = $makeQuote($sonatel, [
            ['location', 'Salle Ndar — journée d’étude (2 jours)', 2, 'jour', 400000],
            ['pause', 'Pause gourmande', 240, 'pax', 4500],
            ['restauration', 'Déjeuner d’affaires', 240, 'pax', 15000],
            ['technique', 'Sonorisation + 2 micros HF', 2, 'jour', 60000],
            ['technique', 'Vidéoprojecteur 4K + écran', 2, 'jour', 45000],
        ]);
        // Devis accepté → événement confirmé + salle réservée + acompte payé.
        $event = EventQuoteBuilder::accept(
            $q1,
            now()->addDays(20)->setTime(8, 30),
            now()->addDays(21)->setTime(18, 0),
        );
        EventBooking::book($event, $spaces->firstWhere('name', 'Salle Ndar') ?? $spaces->first(),
            $event->starts_at, $event->ends_at, 'Classe');
        EventBooking::confirm($event);
        $event->update(['pax' => 120, 'rooms_to_block' => 35]);
        $event->agenda()->createMany([
            ['scheduled_at' => $event->starts_at, 'duration_min' => 30, 'title' => 'Accueil café & émargement', 'category' => 'accueil', 'area' => 'Foyer Ndar', 'responsible' => 'Réception'],
            ['scheduled_at' => $event->starts_at->copy()->addMinutes(30), 'duration_min' => 180, 'title' => 'Session plénière', 'category' => 'technique', 'area' => 'Salle Ndar', 'responsible' => 'Régie'],
            ['scheduled_at' => $event->starts_at->copy()->setTime(13, 0), 'duration_min' => 90, 'title' => 'Déjeuner', 'category' => 'restauration', 'area' => 'Restaurant', 'responsible' => 'Maître d’hôtel'],
        ]);
        FinanceLedger::record([
            'direction' => 'income', 'category' => 'evenements', 'method' => 'virement',
            'amount' => $event->quote->deposit_amount,
            'label' => "Acompte événement {$event->reference} — {$event->name}",
            'operation_date' => now()->subDays(2), 'source' => $event,
        ]);
        $event->update(['deposit_invoiced' => true, 'deposit_paid' => true]);

        // Un devis simplement envoyé (non accepté).
        $makeQuote($leads[4], [
            ['location', 'Salle Sine — demi-journée', 1, 'jour', 180000],
            ['pause', 'Pause simple', 90, 'pax', 2500],
            ['technique', 'Mur d’images LED', 1, 'jour', 250000],
        ]);

        /* ---------------------- Événement en option ----------------------- */
        $optionEvent = Event::create([
            'hotel_id' => $hotel->id,
            'reference' => 'EVT-'.strtoupper(Str::random(6)),
            'name' => 'Convention annuelle BOA',
            'event_type' => 'conference',
            'event_lead_id' => $leads[2]->id,
            'client_name' => 'BOA Sénégal',
            'pax' => 200,
            'status' => 'option',
            'starts_at' => now()->addDays(40)->setTime(9, 0),
            'ends_at' => now()->addDays(40)->setTime(22, 0),
            'option_expires_on' => now()->addDays(6)->toDateString(),
            'rooms_to_block' => 50,
            'created_by' => $commercial->id,
        ]);
        EventBooking::book($optionEvent, $spaces->first(), $optionEvent->starts_at, $optionEvent->ends_at, 'Théâtre');
    }
}
