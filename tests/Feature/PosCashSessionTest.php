<?php

namespace Tests\Feature;

use App\Models\CashSession;
use App\Models\FinanceAccount;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use App\Models\Venue;
use App\Services\CartPricer;
use App\Services\PosRegister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Phase 1 POS : session de caisse, paiement mixte, autorisation manager,
 * remboursement, clôture / Z-report.
 */
class PosCashSessionTest extends TestCase
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

    private function cashAccount(): FinanceAccount
    {
        return FinanceAccount::where('code', 'CAI-02')->firstOrFail();
    }

    /* ------------------------------------------------------------------ */

    public function test_opening_a_session_records_the_float_and_links_the_next_sale(): void
    {
        $account = $this->cashAccount();

        $this->actingAs($this->cashier())->post(route('admin.pos.session.open'), [
            'finance_account_id' => $account->id,
            'opening_float' => 100000,
            'denominations' => [10000 => 5, 5000 => 10],
        ])->assertRedirect(route('admin.pos.index'));

        $session = CashSession::where('finance_account_id', $account->id)->where('status', 'open')->firstOrFail();
        $this->assertSame(100000, $session->opening_float);
        $this->assertSame(5, $session->opening_denominations[10000]);

        $venue = $this->venue();
        $lines = [['item_id' => $this->item($venue)->id, 'quantity' => 1]];
        $ref = $this->actingAs($this->cashier())->postJson(route('admin.pos.checkout'), [
            'venue_id' => $venue->id, 'sale_type' => 'restaurant', 'lines' => $lines,
            'payments' => [['method' => 'especes', 'amount' => $this->total($venue, $lines)]],
        ])->assertOk()->json('reference');

        $order = Order::where('reference', $ref)->firstOrFail();
        $this->assertSame($session->id, $order->cash_session_id);
        $this->assertDatabaseHas('order_payments', ['order_id' => $order->id, 'method' => 'especes', 'cash_session_id' => $session->id]);
    }

    public function test_mixed_payment_splits_across_two_methods(): void
    {
        $venue = $this->venue();
        $lines = [['item_id' => $this->item($venue)->id, 'quantity' => 2]];
        $total = $this->total($venue, $lines);
        $half = intdiv($total, 2);

        $ref = $this->actingAs($this->cashier())->postJson(route('admin.pos.checkout'), [
            'venue_id' => $venue->id, 'sale_type' => 'restaurant', 'lines' => $lines,
            'payments' => [
                ['method' => 'especes', 'amount' => $half, 'tendered' => $half],
                ['method' => 'wave', 'amount' => $total - $half],
            ],
        ])->assertOk()->json('reference');

        $order = Order::where('reference', $ref)->firstOrFail();
        $this->assertSame('mixte', $order->payment_method);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(2, OrderPayment::where('order_id', $order->id)->count());
        $this->assertSame($total, (int) OrderPayment::where('order_id', $order->id)->sum('amount'));
        $this->assertDatabaseHas('finance_transactions', ['method' => 'especes', 'category' => 'restaurant', 'amount' => $half]);
        $this->assertDatabaseHas('finance_transactions', ['method' => 'mobile', 'category' => 'restaurant', 'amount' => $total - $half]);
    }

    public function test_percentage_discount_over_the_limit_requires_a_manager_pin(): void
    {
        $venue = $this->venue();
        $item = $this->item($venue);
        $qty = (int) ceil(60000 / max(1, $item->price));
        $lines = [['item_id' => $item->id, 'quantity' => $qty]];
        $discount = ['type' => 'percent', 'value' => 15];
        $total = $this->total($venue, $lines, $discount);

        // Sans PIN → refusé.
        $this->actingAs($this->cashier())->postJson(route('admin.pos.checkout'), [
            'venue_id' => $venue->id, 'sale_type' => 'restaurant', 'lines' => $lines, 'discount' => $discount,
            'payments' => [['method' => 'especes', 'amount' => $total]],
        ])->assertStatus(422);

        // Avec le PIN manager (direction, seed) → accepté.
        $ref = $this->actingAs($this->cashier())->postJson(route('admin.pos.checkout'), [
            'venue_id' => $venue->id, 'sale_type' => 'restaurant', 'lines' => $lines, 'discount' => $discount,
            'payments' => [['method' => 'especes', 'amount' => $total]],
            'manager_pin' => '2468',
        ])->assertOk()->json('reference');

        $order = Order::where('reference', $ref)->firstOrFail();
        $this->assertGreaterThan(config('pos.discount.max_amount'), $order->discount);
        $this->assertDatabaseHas('order_payments', [
            'order_id' => $order->id,
            'authorized_by' => User::where('role', 'direction')->first()->id,
        ]);
    }

    public function test_refund_writes_a_negative_payment_and_an_expense(): void
    {
        $venue = $this->venue();
        $lines = [['item_id' => $this->item($venue)->id, 'quantity' => 1]];
        $ref = $this->actingAs($this->cashier())->postJson(route('admin.pos.checkout'), [
            'venue_id' => $venue->id, 'sale_type' => 'restaurant', 'lines' => $lines,
            'payments' => [['method' => 'especes', 'amount' => $this->total($venue, $lines)]],
        ])->json('reference');
        $order = Order::where('reference', $ref)->firstOrFail();

        // Sans PIN → refusé.
        $this->actingAs($this->cashier())->postJson(route('admin.pos.refund', $order), ['reason' => 'test'])->assertStatus(422);

        $this->actingAs($this->cashier())->postJson(route('admin.pos.refund', $order), [
            'reason' => 'Plat renvoyé', 'manager_pin' => '2468',
        ])->assertOk();

        $order->refresh();
        $this->assertNotNull($order->refunded_at);
        $this->assertSame('Plat renvoyé', $order->refund_reason);
        $this->assertDatabaseHas('order_payments', ['order_id' => $order->id, 'amount' => -$order->total]);
        $this->assertDatabaseHas('finance_transactions', ['direction' => 'expense', 'category' => 'divers_charges', 'amount' => $order->total]);
    }

    public function test_closing_a_session_produces_a_z_report_and_books_the_variance(): void
    {
        $account = $this->cashAccount();
        PosRegister::open($account, $this->cashier(), 100000, []);
        $session = PosRegister::openFor($account);

        $venue = $this->venue();
        $lines = [['item_id' => $this->item($venue)->id, 'quantity' => 1]];
        $total = $this->total($venue, $lines);
        $this->actingAs($this->cashier())->postJson(route('admin.pos.checkout'), [
            'venue_id' => $venue->id, 'sale_type' => 'restaurant', 'lines' => $lines,
            'payments' => [['method' => 'especes', 'amount' => $total]],
        ])->assertOk();

        $expected = $session->fresh()->expected();
        $this->assertSame(100000 + $total, $expected);

        $this->actingAs($this->cashier())->post(route('admin.pos.session.close', $session), [
            'counted_amount' => $expected - 2000,
            'note' => 'Écart constaté',
        ])->assertOk()->assertSee('RAPPORT Z')->assertSee('Espèces');

        $session->refresh();
        $this->assertSame('closed', $session->status);
        $this->assertSame(-2000, $session->variance);
        $this->assertDatabaseHas('audit_logs', ['action' => 'finance.cash.closed']);
    }

    public function test_dashboard_and_register_render_for_a_cashier(): void
    {
        $this->actingAs($this->cashier())->get(route('admin.pos.index'))
            ->assertOk()->assertSee('Ouverture de caisse');

        PosRegister::open($this->cashAccount(), $this->cashier(), 50000, []);

        $this->actingAs($this->cashier())->get(route('admin.pos.index'))
            ->assertOk()->assertSee('Session ouverte');

        $this->actingAs($this->cashier())->get(route('admin.pos.register'))
            ->assertOk()->assertSee('Serveur');
    }

    public function test_manager_sets_their_own_pin(): void
    {
        $direction = User::where('role', 'direction')->firstOrFail();

        $this->actingAs($direction)->get(route('admin.pos.pin.edit'))->assertOk();
        $this->actingAs($direction)->put(route('admin.pos.pin.update'), [
            'pos_pin' => '9999', 'pos_pin_confirmation' => '9999',
        ])->assertRedirect(route('admin.pos.index'));

        $this->assertTrue(Hash::check('9999', $direction->fresh()->pos_pin));

        // un caissier restauration n'a pas accès à cet écran
        $this->actingAs($this->cashier())->get(route('admin.pos.pin.edit'))->assertForbidden();
    }
}
