<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventLead;
use App\Models\EventQuote;
use App\Models\EventSpace;
use App\Models\User;
use App\Services\EventBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class MeetingRoomsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_planning_shows_a_space_booking_in_the_week_grid(): void
    {
        $space = EventSpace::firstOrFail();
        $start = Carbon::today()->addDays(2)->setTime(9, 0);

        $event = Event::create([
            'hotel_id' => 1, 'reference' => 'EVT-'.strtoupper(Str::random(6)),
            'name' => 'Séminaire Alpha', 'event_type' => 'seminaire', 'client_name' => 'ACME',
            'pax' => 40, 'status' => 'confirme', 'starts_at' => $start, 'ends_at' => $start->copy()->addHours(8),
        ]);
        EventBooking::book($event, $space, $event->starts_at, $event->ends_at);

        $this->actingAs(User::where('role', 'commercial')->firstOrFail())
            ->get(route('admin.salles.index', ['from' => Carbon::today()->toDateString()]))
            ->assertOk()
            ->assertSee($space->name)
            ->assertSee('Séminaire Alpha');
    }

    public function test_planning_is_open_to_commercial_direction_reception_only(): void
    {
        $this->actingAs(User::where('role', 'reception')->firstOrFail())->get(route('admin.salles.index'))->assertOk();
        $this->actingAs(User::where('role', 'direction')->firstOrFail())->get(route('admin.salles.index'))->assertOk();
        $this->actingAs(User::where('role', 'housekeeping')->firstOrFail())->get(route('admin.salles.index'))->assertForbidden();
    }

    public function test_quote_request_form_is_commercial_only(): void
    {
        $this->actingAs(User::where('role', 'commercial')->firstOrFail())
            ->get(route('admin.salles.index'))->assertOk()->assertSee('Demande de devis');

        $this->actingAs(User::where('role', 'reception')->firstOrFail())
            ->get(route('admin.salles.index'))->assertOk()->assertDontSee('Demande de devis');

        $this->actingAs(User::where('role', 'reception')->firstOrFail())
            ->post(route('admin.salles.quote_request'), [])->assertForbidden();
    }

    public function test_quote_request_creates_a_lead_and_a_prefilled_draft_quote(): void
    {
        $space = EventSpace::firstOrFail();

        $response = $this->actingAs(User::where('role', 'commercial')->firstOrFail())
            ->post(route('admin.salles.quote_request'), [
                'company' => 'ACME SARL',
                'contact_name' => 'Mme Fall',
                'contact_email' => 'fall@acme.test',
                'event_type' => 'seminaire',
                'event_space_id' => $space->id,
                'date' => Carbon::today()->addDays(20)->toDateString(),
                'start_time' => '08:30',
                'end_time' => '17:30',
                'duration' => 'full_day',
                'pax' => 45,
                'layout' => 'Théâtre',
                'catering' => ['DEJ_AFFAIRES', 'PAUSE_SIMPLE'],
                'equipment' => ['VIDEOPROJ'],
                'message' => 'Accueil café dès 8 h.',
            ]);

        $lead = EventLead::where('source', 'salle')->latest()->firstOrFail();
        $quote = EventQuote::where('event_lead_id', $lead->id)->firstOrFail();

        $response->assertRedirect(route('admin.events.quotes.edit', $quote));

        $this->assertSame('nouveau', $lead->status);
        $this->assertSame(45, $lead->pax);
        $this->assertGreaterThan(0, $lead->estimated_value);

        $this->assertSame('draft', $quote->status);
        $this->assertSame(4, $quote->items()->count()); // location + 2 restauration/pause + technique
        $this->assertGreaterThan(0, $quote->total);
        $this->assertDatabaseHas('event_quote_items', ['event_quote_id' => $quote->id, 'category' => 'location']);
        // Les lignes restauration sont quantifiées au nombre de participants.
        $this->assertSame(45, (int) $quote->items()->where('category', 'restauration')->value('quantity'));
    }
}
