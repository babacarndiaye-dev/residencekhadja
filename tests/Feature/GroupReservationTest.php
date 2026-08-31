<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Réservation de plusieurs chambres = un seul traitement (confirmation,
 * check-in, check-out) et une seule facture consolidée.
 */
class GroupReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
    }

    private function reception(): User
    {
        return User::where('role', 'reception')->firstOrFail();
    }

    /** @return Collection<int, Reservation> */
    private function group(string $status = 'confirmed', int $rooms = 2)
    {
        $cats = RoomCategory::active()->orderBy('sort_order')->take($rooms)->get();
        $guest = Guest::create(['first_name' => 'Groupe', 'last_name' => 'Test', 'email' => 'groupe@example.com']);
        $ref = 'HRK-'.strtoupper(Str::random(6));

        return $cats->values()->map(fn ($cat, $i) => Reservation::create([
            'reference' => $ref.'-'.($i + 1),
            'group_reference' => $ref,
            'hotel_id' => $cat->hotel_id,
            'guest_id' => $guest->id,
            'room_category_id' => $cat->id,
            'status' => $status,
            'channel' => 'direct',
            'check_in' => Carbon::today()->toDateString(),
            'check_out' => Carbon::today()->addDays(2)->toDateString(),
            'adults' => 2,
            'rooms_count' => 1,
            'room_total' => 100000,
            'tax_amount' => 10000,
            'total' => 110000,
            'deposit' => 33000,
            'confirmed_at' => now(),
        ]));
    }

    public function test_show_page_presents_the_whole_group(): void
    {
        $members = $this->group();

        $this->actingAs($this->reception())
            ->get(route('admin.reservations.show', $members->first()))
            ->assertOk()
            ->assertSee($members->first()->group_reference)
            ->assertSee('2 chambres')
            ->assertSee($members[1]->reference);
    }

    public function test_confirm_confirms_every_room_at_once(): void
    {
        $members = $this->group('pending');

        $this->actingAs($this->reception())
            ->post(route('admin.reservations.confirm', $members->first()))
            ->assertRedirect();

        foreach ($members as $m) {
            $this->assertSame('confirmed', $m->refresh()->status);
        }
    }

    public function test_check_in_assigns_a_distinct_room_per_line(): void
    {
        $members = $this->group('confirmed');
        $rooms = $members->map(fn ($m) => Room::where('room_category_id', $m->room_category_id)
            ->where('status', 'propre')->where('is_active', true)->firstOrFail());

        $assign = $members->mapWithKeys(fn ($m, $i) => [$m->id => $rooms[$i]->id])->all();

        $this->actingAs($this->reception())
            ->post(route('admin.reservations.check_in', $members->first()), ['assign' => $assign])
            ->assertRedirect();

        foreach ($members as $i => $m) {
            $this->assertSame('checked_in', $m->refresh()->status);
            $this->assertSame($rooms[$i]->id, $m->room_id);
            $this->assertSame('occupee', $rooms[$i]->refresh()->status);
        }
    }

    public function test_check_out_settles_one_balance_and_one_invoice_number(): void
    {
        $members = $this->group('checked_in');
        foreach ($members as $m) {
            $room = Room::where('room_category_id', $m->room_category_id)->where('status', 'propre')->firstOrFail();
            $m->update(['room_id' => $room->id]);
            $room->update(['status' => 'occupee']);
        }

        $this->actingAs($this->reception())
            ->post(route('admin.reservations.check_out', $members->first()), ['settle_balance' => '1', 'method' => 'carte'])
            ->assertRedirect(route('admin.reservations.invoice', $members->first()));

        // Un seul encaissement pour tout le séjour, porté par la 1re chambre.
        $payments = Payment::whereIn('reservation_id', $members->pluck('id'))->get();
        $this->assertCount(1, $payments);
        $this->assertSame($members->first()->id, $payments->first()->reservation_id);
        $this->assertSame(220000, (int) $payments->first()->amount);

        $invoiceNumbers = $members->map(fn ($m) => $m->refresh()->invoice_number)->unique();
        $this->assertCount(1, $invoiceNumbers);
        $this->assertNotNull($invoiceNumbers->first());

        foreach ($members as $m) {
            $this->assertSame('checked_out', $m->refresh()->status);
        }
    }

    public function test_invoice_is_a_single_consolidated_document(): void
    {
        $members = $this->group('checked_out');
        $members->each->update(['invoice_number' => 'F-TEST-1']);

        $response = $this->actingAs($this->reception())
            ->get(route('admin.reservations.invoice', $members->first()))
            ->assertOk()
            ->assertSee('F-TEST-1')
            ->assertSee('2 chambre(s)');

        foreach ($members as $m) {
            $response->assertSee($m->roomCategory->name);
        }
    }

    public function test_payment_is_recorded_on_the_primary_member(): void
    {
        $members = $this->group('checked_in');

        $this->actingAs($this->reception())
            ->post(route('admin.reservations.payment', $members->last()), [
                'amount' => 50000, 'method' => 'especes', 'type' => 'deposit',
            ])->assertRedirect();

        $payments = Payment::whereIn('reservation_id', $members->pluck('id'))->get();
        $this->assertCount(1, $payments);
        $this->assertSame($members->first()->id, $payments->first()->reservation_id);
    }

    public function test_walk_in_multi_category_creates_one_group_and_one_invoice(): void
    {
        $cats = RoomCategory::active()->orderBy('sort_order')->take(2)->get();

        $this->actingAs($this->reception())->post(route('admin.reservations.store'), [
            'first_name' => 'Modou', 'last_name' => 'Sarr', 'phone' => '+221770000001',
            'check_in' => Carbon::today()->toDateString(),
            'check_out' => Carbon::today()->addDays(2)->toDateString(),
            'adults' => 4, 'children' => 0,
            'lines' => [
                ['room_category_id' => $cats[0]->id, 'rooms_count' => 1],
                ['room_category_id' => $cats[1]->id, 'rooms_count' => 2],
            ],
        ])->assertRedirect();

        $rows = Reservation::where('channel', 'walk_in')->orderBy('id')->get();
        $this->assertCount(3, $rows);
        $this->assertNotNull($rows->first()->group_reference);
        $this->assertSame(1, $rows->pluck('group_reference')->unique()->count());
        $this->assertGreaterThan(0, (int) $rows->sum('total'));
    }

    public function test_reservation_list_collapses_a_group_into_one_row(): void
    {
        $members = $this->group('confirmed');

        $this->actingAs($this->reception())
            ->get(route('admin.reservations.index'))
            ->assertOk()
            ->assertSee($members->first()->group_reference)
            ->assertSee('2 chambres')
            ->assertDontSee($members[1]->reference);
    }
}
