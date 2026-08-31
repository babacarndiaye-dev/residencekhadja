<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\QrLocation;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tests\TestCase;

class QrOrderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function tableCode(): string
    {
        return QrLocation::where('type', 'table')->value('code');
    }

    private function itemsFor(QrLocation $location): Collection
    {
        $catIds = $location->venue->menuCategories()->pluck('menu_categories.id');

        return MenuItem::whereIn('menu_category_id', $catIds)->where('is_available', true)
            ->whereDoesntHave('optionGroups')->take(2)->get();
    }

    public function test_menu_resolves_for_table_and_room_and_rejects_unknown(): void
    {
        $this->get('/carte/'.$this->tableCode())->assertOk()->assertSee('Ajouter');
        $this->get('/carte/'.QrLocation::where('type', 'room')->value('code'))->assertOk();
        $this->get('/carte/PAS-UN-CODE')->assertNotFound();

        $loc = QrLocation::where('type', 'table')->first();
        $loc->update(['is_active' => false]);
        $this->get('/carte/'.$loc->code)->assertStatus(410);
    }

    public function test_customer_can_place_an_order_and_price_is_server_side(): void
    {
        $code = $this->tableCode();
        $location = QrLocation::where('code', $code)->with('venue')->first();
        $items = $this->itemsFor($location);

        $res = $this->post("/carte/{$code}/commande", [
            'idempotency_key' => (string) Str::uuid(),
            'guest_name' => 'Awa',
            'items' => [
                ['item_id' => $items[0]->id, 'quantity' => 2, 'unit_price' => 1], // prix falsifié ignoré
                ['item_id' => $items[1]->id, 'quantity' => 1],
            ],
        ]);

        $order = Order::latest()->first();
        $res->assertRedirect(route('qr.track', [$code, $order->reference]));

        $expectedSub = $items[0]->price * 2 + $items[1]->price;
        $this->assertSame($expectedSub, $order->subtotal);
        $this->assertSame('new', $order->status);
        $this->assertCount(2, $order->items);
        $this->assertGreaterThan($order->subtotal, $order->total); // taxes ajoutées
    }

    public function test_order_submission_is_idempotent(): void
    {
        $code = $this->tableCode();
        $location = QrLocation::where('code', $code)->with('venue')->first();
        $item = $this->itemsFor($location)->first();
        $key = (string) Str::uuid();

        $payload = ['idempotency_key' => $key, 'items' => [['item_id' => $item->id, 'quantity' => 1]]];

        $this->post("/carte/{$code}/commande", $payload);
        $this->post("/carte/{$code}/commande", $payload);

        $this->assertSame(1, Order::count());
    }

    public function test_room_service_order_is_charged_to_the_active_reservation(): void
    {
        $category = RoomCategory::first();
        $room = Room::where('room_category_id', $category->id)
            ->whereNotIn('id', Reservation::whereNotNull('room_id')->pluck('room_id'))
            ->first();

        $reservation = Reservation::create([
            'reference' => 'HRK-RSVC01',
            'hotel_id' => $category->hotel_id,
            'guest_id' => Guest::create(['first_name' => 'Modou', 'last_name' => 'Sy', 'email' => 'modou@example.com'])->id,
            'room_category_id' => $category->id,
            'room_id' => $room->id,
            'status' => 'checked_in',
            'check_in' => Carbon::yesterday()->toDateString(),
            'check_out' => Carbon::tomorrow()->toDateString(),
            'adults' => 1, 'rooms_count' => 1,
            'total' => 100000,
        ]);

        $loc = QrLocation::where('type', 'room')->where('room_id', $room->id)->first();
        $this->assertNotNull($loc, 'QR room service manquant pour la chambre');

        // Room service = catégories flaggées room_service
        $item = MenuItem::whereHas('category', fn ($q) => $q->where('room_service', true))
            ->where('is_available', true)->whereDoesntHave('optionGroups')->first();

        $this->post("/carte/{$loc->code}/commande", [
            'idempotency_key' => (string) Str::uuid(),
            'items' => [['item_id' => $item->id, 'quantity' => 1]],
            'charge_to_room' => '1',
        ])->assertRedirect();

        $order = Order::latest()->first();
        $this->assertSame('charged_to_room', $order->payment_status);
        $this->assertSame($reservation->id, $order->reservation_id);

        $reservation->refresh();
        $this->assertSame($order->total, $reservation->chargesTotal());
        $this->assertSame(100000 + $order->total, $reservation->grandTotal());
        $this->assertSame($reservation->grandTotal(), $reservation->balance());
    }

    public function test_call_server_and_request_bill_create_service_requests(): void
    {
        $code = $this->tableCode();

        $this->post("/carte/{$code}/appel", ['type' => 'water'])->assertRedirect();
        $this->post("/carte/{$code}/addition")->assertRedirect();

        $this->assertSame(1, ServiceRequest::where('type', 'water')->count());
        $this->assertSame(1, ServiceRequest::where('type', 'bill')->count());
    }

    public function test_kds_advances_order_through_the_lifecycle(): void
    {
        $code = $this->tableCode();
        $location = QrLocation::where('code', $code)->with('venue')->first();
        $item = $this->itemsFor($location)->first();
        $this->post("/carte/{$code}/commande", ['idempotency_key' => (string) Str::uuid(), 'items' => [['item_id' => $item->id, 'quantity' => 1]]]);
        $order = Order::latest()->first();

        $staff = User::where('role', 'restaurant')->first();

        foreach (['preparing', 'ready', 'served', 'completed'] as $expected) {
            $this->actingAs($staff)->post(route('admin.kds.advance', $order))->assertRedirect();
            $this->assertSame($expected, $order->refresh()->status);
        }
    }

    public function test_role_restaurant_reaches_kds_but_housekeeping_does_not(): void
    {
        $this->actingAs(User::where('role', 'restaurant')->first())->get(route('admin.kds.index'))->assertOk();
        $this->actingAs(User::where('role', 'restaurant')->first())->get(route('admin.menu.index'))->assertOk();
        $this->actingAs(User::where('role', 'housekeeping')->first())->get(route('admin.kds.index'))->assertForbidden();
    }

    public function test_menu_item_availability_toggle(): void
    {
        $item = MenuItem::where('is_available', true)->first();

        $this->actingAs(User::where('role', 'restaurant')->first())
            ->post(route('admin.menu.item.toggle', $item))->assertRedirect();

        $this->assertFalse($item->refresh()->is_available);
    }

    public function test_admin_can_charge_an_order_to_a_room_from_the_back_office(): void
    {
        $code = $this->tableCode();
        $location = QrLocation::where('code', $code)->with('venue')->first();
        $item = $this->itemsFor($location)->first();
        $this->post("/carte/{$code}/commande", ['idempotency_key' => (string) Str::uuid(), 'items' => [['item_id' => $item->id, 'quantity' => 1]]]);
        $order = Order::latest()->first();

        $category = RoomCategory::first();
        $room = Room::where('room_category_id', $category->id)
            ->whereNotIn('id', Reservation::whereNotNull('room_id')->pluck('room_id'))
            ->first();
        $reservation = Reservation::create([
            'reference' => 'HRK-CHG01',
            'hotel_id' => $category->hotel_id,
            'guest_id' => Guest::create(['first_name' => 'A', 'last_name' => 'B', 'email' => 'ab@example.com'])->id,
            'room_category_id' => $category->id, 'room_id' => $room->id,
            'status' => 'checked_in',
            'check_in' => Carbon::yesterday()->toDateString(), 'check_out' => Carbon::tomorrow()->toDateString(),
            'adults' => 1, 'rooms_count' => 1, 'total' => 50000,
        ]);

        $this->actingAs(User::where('role', 'restaurant')->first())
            ->post(route('admin.orders.charge', $order), ['reservation_id' => $reservation->id])
            ->assertRedirect();

        $this->assertSame('charged_to_room', $order->refresh()->payment_status);
        $this->assertSame($order->total, $reservation->refresh()->chargesTotal());
    }
}
