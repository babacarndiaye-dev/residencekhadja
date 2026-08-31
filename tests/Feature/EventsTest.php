<?php

namespace Tests\Feature;

use App\Mail\EventQuoteSent;
use App\Models\Event;
use App\Models\EventLead;
use App\Models\EventQuote;
use App\Models\EventSpace;
use App\Models\User;
use App\Services\Accounting;
use App\Services\EventBooking;
use App\Services\EventQuoteBuilder;
use App\Services\SalesPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function space(): EventSpace
    {
        return EventSpace::first() ?? EventSpace::create([
            'hotel_id' => 1, 'name' => 'Salle Test', 'slug' => 'salle-test-'.Str::random(4),
            'layouts' => ['Théâtre' => 100], 'half_day_price' => 100000, 'full_day_price' => 180000,
        ]);
    }

    private function event(string $status, Carbon $start, Carbon $end): Event
    {
        return Event::create([
            'hotel_id' => 1,
            'reference' => 'EVT-'.strtoupper(Str::random(6)),
            'name' => 'Test '.Str::random(4),
            'event_type' => 'seminaire',
            'client_name' => 'ACME',
            'pax' => 50,
            'status' => $status,
            'starts_at' => $start,
            'ends_at' => $end,
        ]);
    }

    private function lead(array $attrs = []): EventLead
    {
        return EventLead::create(array_merge([
            'hotel_id' => 1,
            'reference' => 'LEAD-'.strtoupper(Str::random(5)),
            'contact_name' => 'M. Test',
            'event_type' => 'seminaire',
            'status' => 'nouveau',
            'estimated_value' => 1_000_000,
            'pax' => 40,
        ], $attrs));
    }

    private function quoteWithItems(?EventLead $lead = null): EventQuote
    {
        $quote = EventQuote::create(EventQuoteBuilder::defaults() + [
            'reference' => EventQuoteBuilder::nextReference(),
            'title' => 'Devis test',
            'pax' => 100,
            'discount_amount' => 0,
            'event_lead_id' => $lead?->id,
        ]);
        $quote->items()->create(['category' => 'location', 'label' => 'Salle', 'quantity' => 1, 'unit' => 'jour', 'unit_price' => 400000, 'total' => 400000]);
        $quote->items()->create(['category' => 'pause', 'label' => 'Pause', 'quantity' => 100, 'unit' => 'pax', 'unit_price' => 4500, 'total' => 450000]);

        return EventQuoteBuilder::recalculate($quote);
    }

    /* ----------------------------- Devis : envoi --------------------------- */

    public function test_sending_a_quote_emails_the_lead_and_stamps_it(): void
    {
        Mail::fake();
        $commercial = User::where('role', 'commercial')->firstOrFail();
        $lead = $this->lead(['contact_email' => 'client@example.com']);
        $quote = $this->quoteWithItems($lead);

        $this->actingAs($commercial)
            ->post(route('admin.events.quotes.send', $quote))
            ->assertRedirect();

        $quote->refresh();
        $this->assertSame('sent', $quote->status);
        $this->assertNotNull($quote->sent_at);
        $this->assertSame('devis', $lead->fresh()->status);
        Mail::assertQueued(EventQuoteSent::class, fn ($m) => $m->hasTo('client@example.com'));
    }

    public function test_sending_a_quote_without_a_contact_email_still_marks_it_sent(): void
    {
        Mail::fake();
        $commercial = User::where('role', 'commercial')->firstOrFail();
        $quote = $this->quoteWithItems($this->lead());

        $this->actingAs($commercial)->post(route('admin.events.quotes.send', $quote))->assertRedirect();

        $this->assertSame('sent', $quote->fresh()->status);
        Mail::assertNothingQueued();
    }

    /* ------------------------- Réservation de salle ------------------------- */

    public function test_overlapping_confirmed_events_cannot_share_a_space(): void
    {
        $space = $this->space();
        $a = $this->event('confirme', Carbon::parse('2026-10-01 09:00'), Carbon::parse('2026-10-01 18:00'));
        EventBooking::book($a, $space, $a->starts_at, $a->ends_at);

        $b = $this->event('option', Carbon::parse('2026-10-01 14:00'), Carbon::parse('2026-10-01 20:00'));

        $this->expectException(ValidationException::class);
        EventBooking::book($b, $space, $b->starts_at, $b->ends_at);
    }

    public function test_non_overlapping_bookings_are_allowed(): void
    {
        $space = $this->space();
        $a = $this->event('confirme', Carbon::parse('2026-10-02 09:00'), Carbon::parse('2026-10-02 13:00'));
        EventBooking::book($a, $space, $a->starts_at, $a->ends_at);

        $b = $this->event('option', Carbon::parse('2026-10-02 14:00'), Carbon::parse('2026-10-02 18:00'));
        $booking = EventBooking::book($b, $space, $b->starts_at, $b->ends_at);

        $this->assertDatabaseHas('event_space_bookings', ['id' => $booking->id, 'event_id' => $b->id]);
    }

    public function test_cancelled_event_frees_the_space(): void
    {
        $space = $this->space();
        $a = $this->event('confirme', Carbon::parse('2026-10-03 09:00'), Carbon::parse('2026-10-03 18:00'));
        EventBooking::book($a, $space, $a->starts_at, $a->ends_at);
        $a->update(['status' => 'annule']);

        $b = $this->event('option', Carbon::parse('2026-10-03 10:00'), Carbon::parse('2026-10-03 16:00'));
        $booking = EventBooking::book($b, $space, $b->starts_at, $b->ends_at);

        $this->assertNotNull($booking->id);
    }

    public function test_confirm_rechecks_conflicts(): void
    {
        $space = $this->space();
        $opt = $this->event('option', Carbon::parse('2026-10-04 09:00'), Carbon::parse('2026-10-04 18:00'));
        EventBooking::book($opt, $space, $opt->starts_at, $opt->ends_at);

        // Un autre événement confirmé occupe désormais le créneau.
        $rival = $this->event('confirme', Carbon::parse('2026-10-04 10:00'), Carbon::parse('2026-10-04 12:00'));
        // Réservation directe (sans passer par book) pour simuler une prise concurrente.
        $rival->spaceBookings()->create([
            'event_space_id' => $space->id,
            'starts_at' => $rival->starts_at, 'ends_at' => $rival->ends_at,
        ]);

        $this->expectException(ValidationException::class);
        EventBooking::confirm($opt);
    }

    /* ------------------------------- Devis -------------------------------- */

    public function test_quote_totals_are_computed_from_items(): void
    {
        $quote = $this->quoteWithItems();

        $this->assertSame(850000, $quote->subtotal);
        $this->assertSame((int) round(850000 * config('events.default_tax_rate')), $quote->tax_amount);
        $this->assertSame(850000 + $quote->tax_amount, $quote->total);
        $this->assertSame((int) round($quote->total * config('events.default_deposit_rate')), $quote->deposit_amount);
    }

    public function test_accepting_a_quote_creates_an_option_event_and_wins_the_lead(): void
    {
        $lead = $this->lead(['status' => 'devis']);
        $quote = $this->quoteWithItems($lead);
        $quote->update(['status' => 'sent']);

        $event = EventQuoteBuilder::accept($quote, Carbon::parse('2026-11-05 09:00'), Carbon::parse('2026-11-05 18:00'));

        $this->assertSame('option', $event->status);
        $this->assertSame($quote->id, $event->event_quote_id);
        $this->assertNotNull($event->option_expires_on);
        $this->assertSame('accepted', $quote->fresh()->status);
        $this->assertSame('gagne', $lead->fresh()->status);
    }

    /* ------------------------------ Pipeline ----------------------------- */

    public function test_pipeline_rejects_illegal_transitions(): void
    {
        $lead = $this->lead(['status' => 'nouveau']);

        $this->expectException(ValidationException::class);
        SalesPipeline::advance($lead, 'gagne');
    }

    public function test_pipeline_lost_requires_reason(): void
    {
        $lead = $this->lead(['status' => 'qualifie']);

        try {
            SalesPipeline::advance($lead, 'perdu');
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('lost_reason', $e->errors());
        }

        SalesPipeline::advance($lead, 'perdu', ['lost_reason' => 'Budget']);
        $this->assertSame('perdu', $lead->fresh()->status);
    }

    public function test_pipeline_win_requires_accepted_quote(): void
    {
        $lead = $this->lead(['status' => 'negociation']);

        $this->expectException(ValidationException::class);
        SalesPipeline::advance($lead, 'gagne');
    }

    /* ------------------------------ Finance ----------------------------- */

    public function test_deposit_payment_posts_to_finance_and_accounting(): void
    {
        $commercial = User::where('role', 'commercial')->firstOrFail();
        $lead = $this->lead(['status' => 'devis']);
        $quote = $this->quoteWithItems($lead);
        $quote->update(['status' => 'sent']);
        $event = EventQuoteBuilder::accept($quote);

        $this->actingAs($commercial)
            ->post(route('admin.events.deposit', $event))
            ->assertRedirect();

        $event->refresh();
        $this->assertTrue($event->deposit_paid);
        $this->assertDatabaseHas('finance_transactions', [
            'category' => 'evenements', 'amount' => $quote->deposit_amount, 'direction' => 'income',
        ]);

        $b = Accounting::trialBalance('2000-01-01', '2100-01-01');
        $this->assertSame($b->sum('debit'), $b->sum('credit'));
    }

    /* ------------------------------- RBAC ------------------------------- */

    public function test_rbac_commercial_scope(): void
    {
        $commercial = User::where('role', 'commercial')->firstOrFail();
        $reception = User::where('role', 'reception')->firstOrFail();
        $direction = User::where('role', 'direction')->firstOrFail();

        $this->actingAs($commercial)->get(route('admin.events.pipeline'))->assertOk();
        $this->actingAs($commercial)->get(route('admin.events.index'))->assertOk();
        $this->actingAs($direction)->get(route('admin.events.pipeline'))->assertOk();
        $this->actingAs($reception)->get(route('admin.events.pipeline'))->assertForbidden();
    }

    public function test_quote_and_contract_pages_render(): void
    {
        $commercial = User::where('role', 'commercial')->firstOrFail();
        $quote = $this->quoteWithItems($this->lead());
        $event = EventQuoteBuilder::accept($quote->fresh());

        $this->actingAs($commercial)->get(route('admin.events.quotes.print', $quote))->assertOk()->assertSee('DEVIS');
        $this->actingAs($commercial)->get(route('admin.events.contract', $event))->assertOk()->assertSee('CONTRAT');
    }
}
