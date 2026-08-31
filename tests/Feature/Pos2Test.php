<?php

namespace Tests\Feature;

use App\Mail\PosInvoice;
use App\Models\BreakfastCheckin;
use App\Models\Guest;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\QrLocation;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\User;
use App\Models\Venue;
use App\Services\CartPricer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 2 POS : plan de salle, division d'addition, client hôtel + folio,
 * suivi room service, contrôle petit-déjeuner, facture A4.
 */
class Pos2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function cashier(): User
    {
        return User::where('role', 'restaurant')->firstOrFail();
    }

    private function venue(): Venue
    {
        return Venue::where('is_active', true)->where('is_room_service', false)->first() ?? Venue::first();
    }

    private function item(?Venue $venue = null): MenuItem
    {
        $venue ??= $this->venue();
        $ids = $venue->menuCategories()->pluck('menu_categories.id');

        return MenuItem::whereIn('menu_category_id', $ids)->available()->doesntHave('optionGroups')
            ->whereRelation('category', 'needs_kitchen', true)->firstOrFail();
    }

    private function total(Venue $venue, array $lines, int|array $discount = 0): int
    {
        return app(CartPricer::class)->priceForVenue($venue, $lines, $discount)['total'];
    }

    private function stay(): Reservation
    {
        $room = Room::whereNotIn('id', Reservation::whereNotNull('room_id')->pluck('room_id'))->firstOrFail();

        return Reservation::create([
            'reference' => 'HRK-'.strtoupper(Str::random(6)), 'hotel_id' => 1,
            'guest_id' => Guest::create(['first_name' => 'Awa', 'last_name' => 'Sy', 'email' => 'awa'.random_int(1, 9999).'@example.com', 'phone' => '+221770000000'])->id,
            'room_category_id' => RoomCategory::first()->id, 'room_id' => $room->id, 'status' => 'checked_in',
            'check_in' => Carbon::yesterday()->toDateString(), 'check_out' => Carbon::tomorrow()->toDateString(),
            'adults' => 2, 'rooms_count' => 1, 'total' => 120000,
        ]);
    }

    private function ring(array $overrides = []): Order
    {
        $venue = $this->venue();
        $lines = $overrides['lines'] ?? [['item_id' => $this->item($venue)->id, 'quantity' => 1]];
        $payments = $overrides['payments'] ?? [['method' => 'especes', 'amount' => $this->total($venue, $lines)]];

        $ref = $this->actingAs($this->cashier())->postJson(route('admin.pos.checkout'), array_merge([
            'venue_id' => $venue->id, 'sale_type' => 'restaurant', 'lines' => $lines, 'payments' => $payments,
        ], $overrides['payload'] ?? []))->assertOk()->json('reference');

        return Order::where('reference', $ref)->firstOrFail();
    }

    /* ------------------------------------------------------------------ */

    public function test_a_table_links_the_order_and_marks_it_occupied(): void
    {
        $venue = $this->venue();
        $table = QrLocation::tables()->where('venue_id', $venue->id)->firstOrFail();

        $order = $this->ring(['payload' => ['table_id' => $table->id]]);

        $this->assertSame($table->id, $order->qr_location_id);
        $this->assertSame($table->label, $order->table_label);
        $this->assertSame('libre', $table->fresh()->occupancyStatus()); // vente réglée → table libérée

        // une commande en attente occupe la table
        $held = $this->actingAs($this->cashier())->postJson(route('admin.pos.hold'), [
            'venue_id' => $venue->id, 'sale_type' => 'restaurant', 'table_id' => $table->id,
            'lines' => [['item_id' => $this->item($venue)->id, 'quantity' => 1]],
        ])->assertOk()->json('reference');
        $this->assertSame('occupee', $table->fresh()->occupancyStatus());

        $this->actingAs($this->cashier())->get(route('admin.pos.floor', ['venue' => $venue->id]))
            ->assertOk()->assertSee($table->label);
        $this->assertNotNull(Order::where('reference', $held)->first());
    }

    public function test_equal_split_pays_the_full_total_in_n_lines(): void
    {
        $venue = $this->venue();
        $lines = [['item_id' => $this->item($venue)->id, 'quantity' => 3]];
        $total = $this->total($venue, $lines);
        $base = intdiv($total, 3);

        $order = $this->ring([
            'lines' => $lines,
            'payments' => [
                ['method' => 'especes', 'amount' => $base],
                ['method' => 'especes', 'amount' => $base],
                ['method' => 'especes', 'amount' => $total - 2 * $base],
            ],
        ]);

        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(3, $order->payments()->count());
        $this->assertSame($total, (int) $order->payments()->sum('amount'));
    }

    public function test_guest_search_returns_a_folio_split_by_origin(): void
    {
        $reservation = $this->stay();
        $venue = $this->venue();
        $lines = [['item_id' => $this->item($venue)->id, 'quantity' => 1]];

        foreach (['restaurant', 'bar'] as $type) {
            $this->ring([
                'payments' => [['method' => 'chambre', 'amount' => $this->total($venue, $lines), 'reservation_id' => $reservation->id]],
                'payload' => ['sale_type' => $type],
            ]);
        }

        $row = collect($this->actingAs($this->cashier())
            ->getJson(route('admin.pos.guests', ['q' => $reservation->room->number]))
            ->assertOk()->json('results'))->firstWhere('id', $reservation->id);

        $this->assertNotNull($row);
        $this->assertGreaterThan(0, $row['folio']['restaurant']);
        $this->assertGreaterThan(0, $row['folio']['bar']);
        $this->assertSame($row['folio']['restaurant'] + $row['folio']['bar'], $row['folio']['consumptions']);
    }

    public function test_room_service_pipeline_advances_and_notifies_reception(): void
    {
        DatabaseNotification::query()->delete();
        $reservation = $this->stay();
        $venue = $this->venue();
        $lines = [['item_id' => $this->item($venue)->id, 'quantity' => 1]];

        $order = $this->ring([
            'lines' => $lines,
            'payments' => [['method' => 'chambre', 'amount' => $this->total($venue, $lines), 'reservation_id' => $reservation->id]],
            'payload' => ['sale_type' => 'room_service'],
        ]);
        $this->assertSame('new', $order->fresh()->status);

        $reception = User::where('role', 'reception')->firstOrFail();

        foreach (['preparing', 'ready', 'out_for_delivery', 'delivered'] as $expected) {
            $this->actingAs($this->cashier())->post(route('admin.pos.room_service.advance', $order))->assertRedirect();
            $this->assertSame($expected, $order->fresh()->status);
        }

        $this->assertNotNull($order->fresh()->delivered_at);
        $this->assertGreaterThan(0, $reception->notifications()->count());
    }

    public function test_breakfast_included_records_without_a_charge(): void
    {
        $reservation = $this->stay();
        $reservation->update(['extras' => ['petit-dejeuner']]);

        $this->actingAs($this->cashier())->post(route('admin.pos.breakfast.store'), [
            'reservation_id' => $reservation->id, 'guests' => 2,
        ])->assertRedirect();

        $checkin = BreakfastCheckin::where('reservation_id', $reservation->id)->sole();
        $this->assertTrue($checkin->included);
        $this->assertNull($checkin->reservation_charge_id);
    }

    public function test_breakfast_not_included_imputes_a_folio_charge_and_is_idempotent(): void
    {
        $reservation = $this->stay();
        $reservation->update(['extras' => []]);
        $price = (int) collect(config('booking.extras'))->firstWhere('key', 'petit-dejeuner')['price'];

        $this->actingAs($this->cashier())->post(route('admin.pos.breakfast.store'), [
            'reservation_id' => $reservation->id, 'guests' => 2,
        ])->assertRedirect();

        $checkin = BreakfastCheckin::where('reservation_id', $reservation->id)->sole();
        $this->assertFalse($checkin->included);
        $this->assertNotNull($checkin->reservation_charge_id);
        $this->assertSame($price * 2, (int) $checkin->charge->amount);

        // 2ᵉ passage le même jour → pas de doublon, montant recalculé
        $this->actingAs($this->cashier())->post(route('admin.pos.breakfast.store'), [
            'reservation_id' => $reservation->id, 'guests' => 3,
        ])->assertRedirect();

        $this->assertSame(1, BreakfastCheckin::where('reservation_id', $reservation->id)->count());
        $this->assertSame($price * 3, (int) $checkin->fresh()->charge->amount);
    }

    public function test_pos_invoice_gets_a_sequential_number_and_can_be_sent(): void
    {
        Mail::fake();
        $year = now()->year;

        $a = $this->ring();
        $b = $this->ring();

        $this->actingAs($this->cashier())->get(route('admin.pos.invoice', $a))->assertOk();
        $this->actingAs($this->cashier())->get(route('admin.pos.invoice', $b))->assertOk();

        $this->assertSame("KH-{$year}-000001", $a->fresh()->invoice_number);
        $this->assertSame("KH-{$year}-000002", $b->fresh()->invoice_number);

        $this->actingAs($this->cashier())->post(route('admin.pos.invoice.send', $a), [
            'email' => 'client@example.com',
        ])->assertRedirect();

        Mail::assertQueued(PosInvoice::class, fn ($m) => $m->hasTo('client@example.com'));
    }
}
