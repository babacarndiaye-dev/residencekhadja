<?php

namespace Tests\Feature;

use App\Mail\ReservationConfirmed;
use App\Models\Reservation;
use App\Services\BookingQuote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function dates(): array
    {
        return [
            'check_in' => Carbon::today()->addDays(10)->toDateString(),
            'check_out' => Carbon::today()->addDays(13)->toDateString(),
        ];
    }

    public function test_full_tunnel_produces_a_reference(): void
    {
        Mail::fake();

        $this->post(route('booking.search'), $this->dates() + [
            'adults' => 2, 'children' => 1, 'rooms' => 1, 'promo' => 'TERANGA',
        ])->assertRedirect(route('booking.rooms'));

        $this->get(route('booking.rooms'))->assertOk()->assertSee('Composez votre séjour');

        $this->post(route('booking.rooms.store'), [
            'rate_plan' => 'flexible', 'lines' => ['suite-junior' => 1],
        ])->assertRedirect(route('booking.extras'));

        $this->post(route('booking.extras.store'), [
            'extras' => ['petit-dejeuner', 'transfert-aeroport'],
            'special_requests' => ['Étage élevé'],
        ])->assertRedirect(route('booking.summary'));

        $this->get(route('booking.summary'))->assertOk()->assertSee('Vos coordonnées');

        $this->post(route('booking.confirm'), [
            'civility' => 'Mme', 'first_name' => 'Awa', 'last_name' => 'Ba',
            'email' => 'awa@example.com', 'phone' => '+221770000000', 'country' => 'Sénégal',
            'payment' => 'sur_place', 'terms' => '1',
        ])->assertRedirect(route('booking.done'));

        $this->get(route('booking.done'))->assertOk()->assertSee('Référence :')->assertSee('Awa');

        $row = Reservation::where('channel', 'direct')->whereHas('guest', fn ($q) => $q->where('email', 'awa@example.com'))->sole();
        $this->assertNull($row->group_reference);            // 1 seule chambre → pas de groupe
        $this->assertStringStartsWith('HRK-', $row->reference);
        Mail::assertQueued(ReservationConfirmed::class, fn ($m) => $m->hasTo('awa@example.com'));
    }

    public function test_multi_room_different_categories_creates_grouped_reservations(): void
    {
        $this->post(route('booking.search'), $this->dates() + ['adults' => 3, 'children' => 0, 'rooms' => 2]);

        // Devis asynchrone : 1 Chambre Supérieure + 1 Suite Junior.
        $lines = ['chambre-superieure' => 1, 'suite-junior' => 1];
        $json = $this->postJson(route('booking.quote'), ['rate_plan' => 'flexible', 'lines' => $lines])
            ->assertOk()->json('quote');
        $this->assertSame(2, $json['room_count']);
        $this->assertCount(2, $json['room_lines']);

        $this->post(route('booking.rooms.store'), ['rate_plan' => 'flexible', 'lines' => $lines])
            ->assertRedirect(route('booking.extras'));
        $this->post(route('booking.extras.store'), []);

        $group = BookingQuote::for(session('booking'));

        $this->post(route('booking.confirm'), [
            'civility' => 'M.', 'first_name' => 'Modou', 'last_name' => 'Sy',
            'email' => 'modou@example.com', 'phone' => '+221771112233', 'country' => 'Sénégal',
            'payment' => 'sur_place', 'terms' => '1',
        ])->assertRedirect(route('booking.done'));

        $rows = Reservation::whereNotNull('group_reference')->orderBy('id')->get();
        $this->assertCount(2, $rows);
        $this->assertNotNull($rows[0]->group_reference);
        $this->assertSame($rows[0]->group_reference, $rows[1]->group_reference);
        $this->assertSame([$rows[0]->group_reference.'-1', $rows[0]->group_reference.'-2'], $rows->pluck('reference')->all());
        $this->assertNotSame($rows[0]->room_category_id, $rows[1]->room_category_id);

        // La somme des totaux des chambres = total du devis groupé (au FCFA près).
        $this->assertSame($group['total'], (int) $rows->sum('total'));
        $this->assertSame($group['tax'], (int) $rows->sum('tax_amount'));

        $this->get(route('booking.done'))->assertOk()->assertSee('2 ch.');
    }

    public function test_search_rejects_past_dates(): void
    {
        $this->post(route('booking.search'), [
            'check_in' => Carbon::yesterday()->toDateString(),
            'check_out' => Carbon::today()->toDateString(),
            'adults' => 2, 'rooms' => 1,
        ])->assertSessionHasErrors('check_in');
    }

    public function test_rooms_step_requires_a_prior_search(): void
    {
        $this->get(route('booking.rooms'))->assertRedirect(route('booking.start'));
    }

    public function test_summary_requires_a_selected_room(): void
    {
        $this->post(route('booking.search'), $this->dates() + ['adults' => 2, 'rooms' => 1]);
        $this->get(route('booking.summary'))->assertRedirect(route('booking.rooms'));
    }

    public function test_store_room_requires_at_least_one_room(): void
    {
        $this->post(route('booking.search'), $this->dates() + ['adults' => 2, 'rooms' => 1]);
        $this->post(route('booking.rooms.store'), ['rate_plan' => 'flexible', 'lines' => []])
            ->assertSessionHasErrors('lines');
    }

    public function test_promo_code_reduces_total(): void
    {
        $this->post(route('booking.search'), $this->dates() + ['adults' => 2, 'rooms' => 1]);
        $this->post(route('booking.rooms.store'), ['rate_plan' => 'flexible', 'lines' => ['chambre-deluxe' => 1]]);
        $this->post(route('booking.extras.store'), []);

        $withoutPromo = BookingQuote::for(session('booking'));

        $this->post(route('booking.search'), $this->dates() + ['adults' => 2, 'rooms' => 1, 'promo' => 'DIRECT12']);
        $this->post(route('booking.rooms.store'), ['rate_plan' => 'flexible', 'lines' => ['chambre-deluxe' => 1]]);
        $withPromo = BookingQuote::for(session('booking'));

        $this->assertGreaterThan(0, $withPromo['discount_amount']);
        $this->assertLessThan($withoutPromo['total'], $withPromo['total']);
    }
}
