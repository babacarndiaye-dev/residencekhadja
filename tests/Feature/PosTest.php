<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\User;
use App\Models\Venue;
use App\Services\CartPricer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosTest extends TestCase
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

    private function item(Venue $venue): MenuItem
    {
        $ids = $venue->menuCategories()->pluck('menu_categories.id');

        return MenuItem::whereIn('menu_category_id', $ids)->available()->doesntHave('optionGroups')
            ->whereRelation('category', 'needs_kitchen', true)->firstOrFail();
    }

    private function drink(Venue $venue): MenuItem
    {
        $ids = $venue->menuCategories()->pluck('menu_categories.id');

        return MenuItem::whereIn('menu_category_id', $ids)->available()->doesntHave('optionGroups')
            ->whereRelation('category', 'needs_kitchen', false)->firstOrFail();
    }

    /** Total TTC d'un panier tel que le calcule le POS. */
    private function total(Venue $venue, array $lines, int|array $discount = 0): int
    {
        return app(CartPricer::class)->priceForVenue($venue, $lines, $discount)['total'];
    }

    private function checkedInReservation(): Reservation
    {
        $room = Room::whereNotIn('id', Reservation::whereNotNull('room_id')->pluck('room_id'))->first();

        return Reservation::create([
            'reference' => 'HRK-'.strtoupper(Str::random(6)), 'hotel_id' => 1,
            'guest_id' => Guest::create(['first_name' => 'P', 'last_name' => 'O', 'email' => 'po'.random_int(1000, 9999).'@example.com'])->id,
            'room_category_id' => RoomCategory::first()->id, 'room_id' => $room->id, 'status' => 'checked_in',
            'check_in' => Carbon::yesterday()->toDateString(), 'check_out' => Carbon::tomorrow()->toDateString(),
            'adults' => 1, 'rooms_count' => 1, 'total' => 80000,
        ]);
    }

    /* ----------------------------- Ventes ---------------------------- */

    public function test_cash_sale_creates_paid_order_and_books_revenue(): void
    {
        $venue = $this->venue();
        $item = $this->item($venue);
        $lines = [['item_id' => $item->id, 'quantity' => 2]];
        $discount = ['type' => 'amount', 'value' => 500];
        $expectedTotal = $this->total($venue, $lines, $discount);

        $res = $this->actingAs($this->cashier())->postJson(route('admin.pos.checkout'), [
            'venue_id' => $venue->id, 'sale_type' => 'restaurant', 'table_label' => '7', 'discount' => $discount,
            'lines' => $lines,
            'payments' => [['method' => 'especes', 'amount' => $expectedTotal, 'tendered' => 20000]],
        ]);

        $res->assertOk()->assertJson(['ok' => true, 'total' => $expectedTotal]);
        $this->assertSame(20000 - $expectedTotal, $res->json('change'));

        $order = Order::where('reference', $res->json('reference'))->firstOrFail();
        $this->assertSame('pos', $order->source);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('especes', $order->payment_method);
        $this->assertSame(500, $order->discount);
        $this->assertSame(2, $order->items->sum('quantity'));
        $this->assertDatabaseHas('order_payments', ['order_id' => $order->id, 'method' => 'especes', 'amount' => $expectedTotal]);
        $this->assertDatabaseHas('finance_transactions', [
            'direction' => 'income', 'category' => 'restaurant', 'amount' => $expectedTotal,
        ]);
    }

    public function test_mobile_method_maps_to_treasury_mobile(): void
    {
        $venue = $this->venue();
        $item = $this->item($venue);

        $lines = [['item_id' => $item->id, 'quantity' => 1]];
        $total = $this->total($venue, $lines);

        $ref = $this->actingAs($this->cashier())->postJson(route('admin.pos.checkout'), [
            'venue_id' => $venue->id, 'sale_type' => 'bar', 'lines' => $lines,
            'payments' => [['method' => 'wave', 'amount' => $total]],
        ])->assertOk()->json('reference');

        $order = Order::where('reference', $ref)->first();
        $this->assertSame('wave', $order->payment_method);
        $this->assertDatabaseHas('finance_transactions', ['method' => 'mobile', 'category' => 'restaurant', 'amount' => $order->total]);
    }

    public function test_charge_to_room_adds_a_folio_charge(): void
    {
        $venue = $this->venue();
        $item = $this->item($venue);
        $reservation = $this->checkedInReservation();
        $before = $reservation->grandTotal();

        $lines = [['item_id' => $item->id, 'quantity' => 1]];
        $total = $this->total($venue, $lines);

        $ref = $this->actingAs($this->cashier())->postJson(route('admin.pos.checkout'), [
            'venue_id' => $venue->id, 'sale_type' => 'room_service', 'lines' => $lines,
            'payments' => [['method' => 'chambre', 'amount' => $total, 'reservation_id' => $reservation->id]],
        ])->assertOk()->json('reference');

        $order = Order::where('reference', $ref)->first();
        $this->assertSame('charged_to_room', $order->payment_status);
        $this->assertDatabaseHas('reservation_charges', ['source_id' => $order->id, 'amount' => $order->total]);
        $this->assertSame($before + $order->total, $reservation->fresh()->grandTotal());
    }

    public function test_discount_cannot_exceed_subtotal(): void
    {
        $venue = $this->venue();
        $item = $this->item($venue);

        $ref = $this->actingAs($this->cashier())->postJson(route('admin.pos.checkout'), [
            'venue_id' => $venue->id, 'sale_type' => 'restaurant',
            'discount' => ['type' => 'amount', 'value' => 999999999],
            'lines' => [['item_id' => $item->id, 'quantity' => 1]],
            'payments' => [],
        ])->assertOk()->json('reference');

        $order = Order::where('reference', $ref)->first();
        $this->assertSame($item->price, $order->discount);
        $this->assertSame(0, $order->total);
    }

    public function test_unavailable_item_is_rejected(): void
    {
        $venue = $this->venue();
        $item = $this->item($venue);
        $item->update(['is_available' => false]);

        $this->actingAs($this->cashier())->postJson(route('admin.pos.checkout'), [
            'venue_id' => $venue->id, 'sale_type' => 'restaurant',
            'lines' => [['item_id' => $item->id, 'quantity' => 1]],
            'payments' => [['method' => 'especes', 'amount' => 5000]],
        ])->assertStatus(422);
    }

    /* ------------------------- Mise en attente ---------------------- */

    public function test_hold_then_resume_updates_the_same_order(): void
    {
        $venue = $this->venue();
        $item = $this->item($venue);

        $heldRef = $this->actingAs($this->cashier())->postJson(route('admin.pos.hold'), [
            'venue_id' => $venue->id, 'sale_type' => 'restaurant', 'table_label' => '3',
            'lines' => [['item_id' => $item->id, 'quantity' => 1]],
        ])->assertOk()->json('reference');

        $held = Order::where('reference', $heldRef)->firstOrFail();
        $this->assertSame('held', $held->status);
        $this->assertSame('unpaid', $held->payment_status);

        $resumeLines = [['item_id' => $item->id, 'quantity' => 3]];
        $doneRef = $this->actingAs($this->cashier())->postJson(route('admin.pos.checkout'), [
            'venue_id' => $venue->id, 'sale_type' => 'restaurant', 'resume_order_id' => $held->id,
            'lines' => $resumeLines,
            'payments' => [['method' => 'especes', 'amount' => $this->total($venue, $resumeLines)]],
        ])->assertOk()->json('reference');

        $this->assertSame($heldRef, $doneRef);
        $held->refresh();
        $this->assertSame('paid', $held->payment_status);
        $this->assertSame(3, $held->items->sum('quantity'));
        $this->assertSame(1, Order::where('id', $held->id)->count());
    }

    public function test_void_cancels_a_held_order(): void
    {
        $venue = $this->venue();
        $item = $this->item($venue);

        $ref = $this->actingAs($this->cashier())->postJson(route('admin.pos.hold'), [
            'venue_id' => $venue->id, 'sale_type' => 'restaurant',
            'lines' => [['item_id' => $item->id, 'quantity' => 1]],
        ])->json('reference');
        $order = Order::where('reference', $ref)->firstOrFail();

        $this->actingAs($this->cashier())->postJson(route('admin.pos.void', $order), [
            'reason' => 'Erreur de saisie', 'manager_pin' => '2468',
        ])->assertOk();
        $this->assertSame('cancelled', $order->fresh()->status);

        // sans PIN manager, l'annulation est refusée
        $other = Order::where('reference', $this->actingAs($this->cashier())->postJson(route('admin.pos.hold'), [
            'venue_id' => $venue->id, 'sale_type' => 'restaurant',
            'lines' => [['item_id' => $item->id, 'quantity' => 1]],
        ])->json('reference'))->firstOrFail();
        $this->actingAs($this->cashier())->postJson(route('admin.pos.void', $other), ['reason' => 'x'])->assertStatus(422);
    }

    /* ------------------------------ Divers ------------------------- */

    public function test_receipt_renders_for_pos_order_only(): void
    {
        $venue = $this->venue();
        $item = $this->item($venue);
        $lines = [['item_id' => $item->id, 'quantity' => 1]];
        $ref = $this->actingAs($this->cashier())->postJson(route('admin.pos.checkout'), [
            'venue_id' => $venue->id, 'sale_type' => 'restaurant', 'lines' => $lines,
            'payments' => [['method' => 'especes', 'amount' => $this->total($venue, $lines)]],
        ])->json('reference');
        $order = Order::where('reference', $ref)->firstOrFail();

        $this->actingAs($this->cashier())->get(route('admin.pos.receipt', $order))
            ->assertOk()->assertSee('TOTAL');

        $qr = Order::where('source', 'qr')->first();
        if ($qr) {
            $this->actingAs($this->cashier())->get(route('admin.pos.receipt', $qr))->assertNotFound();
        }
    }

    /* --------------------------- Cuisine (KDS) --------------------------- */

    public function test_drinks_only_sale_skips_the_kitchen(): void
    {
        $venue = $this->venue();
        $drink = $this->drink($venue);

        $lines = [['item_id' => $drink->id, 'quantity' => 2]];
        $ref = $this->actingAs($this->cashier())->postJson(route('admin.pos.checkout'), [
            'venue_id' => $venue->id, 'sale_type' => 'bar', 'lines' => $lines,
            'payments' => [['method' => 'especes', 'amount' => $this->total($venue, $lines), 'tendered' => 10000]],
        ])->assertOk()->json('reference');

        $order = Order::where('reference', $ref)->firstOrFail();
        $this->assertSame('served', $order->status);
        $this->assertNotNull($order->served_at);
        $this->assertFalse($order->items->first()->needs_kitchen);

        $this->actingAs($this->cashier())->get(route('admin.kds.index'))
            ->assertOk()->assertDontSee($order->reference);
    }

    public function test_mixed_sale_shows_only_kitchen_items_on_the_kds(): void
    {
        $venue = $this->venue();
        $food = $this->item($venue);
        $drink = $this->drink($venue);

        $lines = [['item_id' => $food->id, 'quantity' => 1], ['item_id' => $drink->id, 'quantity' => 1]];
        $ref = $this->actingAs($this->cashier())->postJson(route('admin.pos.checkout'), [
            'venue_id' => $venue->id, 'sale_type' => 'restaurant', 'lines' => $lines,
            'payments' => [['method' => 'especes', 'amount' => $this->total($venue, $lines), 'tendered' => 30000]],
        ])->assertOk()->json('reference');

        $order = Order::where('reference', $ref)->firstOrFail();
        $this->assertSame('new', $order->status);

        $this->actingAs($this->cashier())->get(route('admin.kds.index'))
            ->assertOk()
            ->assertSee($order->reference)
            ->assertSee($food->name)
            ->assertDontSee($drink->name);
    }

    public function test_rbac_pos_scope(): void
    {
        $this->actingAs($this->cashier())->get(route('admin.pos.index'))->assertOk();
        $this->actingAs(User::where('role', 'housekeeping')->firstOrFail())->get(route('admin.pos.index'))->assertForbidden();

        $venue = $this->venue();
        $this->actingAs(User::where('role', 'housekeeping')->firstOrFail())
            ->postJson(route('admin.pos.checkout'), [
                'venue_id' => $venue->id, 'sale_type' => 'restaurant',
                'lines' => [['item_id' => $this->item($venue)->id, 'quantity' => 1]],
                'payments' => [['method' => 'especes', 'amount' => 5000]],
            ])->assertForbidden();
    }
}
