<?php

namespace Tests\Feature;

use App\Models\EventLead;
use App\Models\EventQuote;
use App\Models\EventSpace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EventEnquiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_the_public_page_shows_the_quote_request_form(): void
    {
        $this->get(route('events'))
            ->assertOk()
            ->assertSee('Demander un devis')
            ->assertSee('Type d\'événement', false)
            ->assertSee('name="contact_email"', false)
            ->assertSee(route('events.enquiry'));
    }

    public function test_a_visitor_enquiry_creates_a_lead_and_a_prefilled_draft_quote(): void
    {
        $space = EventSpace::firstOrFail();

        $response = $this->from(route('events'))->post(route('events.enquiry'), [
            'contact_name' => 'Awa Ndiaye',
            'company' => 'Sonatel',
            'contact_email' => 'awa@sonatel.test',
            'contact_phone' => '+221 77 000 00 00',
            'event_type' => 'conference',
            'event_space_id' => $space->id,
            'date' => Carbon::today()->addMonth()->toDateString(),
            'duration' => 'full_day',
            'pax' => 120,
            'layout' => 'Théâtre',
            'catering' => ['PAUSE_GOURMANDE', 'DEJ_AFFAIRES'],
            'equipment' => ['SONO'],
            'message' => 'Prévoir hébergement pour 40 intervenants.',
            'consent' => '1',
        ]);

        $response->assertRedirect(route('events'));
        $response->assertSessionHas('status');

        $lead = EventLead::where('source', 'salle')->where('contact_email', 'awa@sonatel.test')->firstOrFail();
        $quote = EventQuote::where('event_lead_id', $lead->id)->firstOrFail();

        $this->assertSame('nouveau', $lead->status);
        $this->assertNull($lead->owner_id); // demande web = non affectée
        $this->assertSame(120, $lead->pax);
        $this->assertSame('draft', $quote->status);
        $this->assertSame(4, $quote->items()->count()); // salle + 2 restauration/pause + 1 technique
        $this->assertGreaterThan(0, $quote->total);
    }

    public function test_enquiry_requires_consent_and_core_fields(): void
    {
        $this->from(route('events'))->post(route('events.enquiry'), [
            'contact_name' => 'Sans consentement',
            'contact_email' => 'x@example.test',
            'event_type' => 'seminaire',
            'duration' => 'full_day',
            'pax' => 20,
        ])->assertSessionHasErrors('consent');

        $this->assertDatabaseMissing('event_leads', ['contact_email' => 'x@example.test']);
    }

    public function test_honeypot_silently_swallows_bots(): void
    {
        $before = EventLead::count();

        $this->from(route('events'))->post(route('events.enquiry'), [
            'website' => 'http://spam.example',
            'contact_name' => 'Bot',
            'contact_email' => 'bot@spam.test',
            'event_type' => 'seminaire',
            'duration' => 'full_day',
            'pax' => 10,
            'consent' => '1',
        ])->assertRedirect(route('events'));

        $this->assertSame($before, EventLead::count());
    }
}
