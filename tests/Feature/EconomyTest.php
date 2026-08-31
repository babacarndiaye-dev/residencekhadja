<?php

namespace Tests\Feature;

use App\Models\CashSession;
use App\Models\FinanceAccount;
use App\Models\FinanceTransaction;
use App\Models\Guest;
use App\Models\InventoryCount;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\PurchaseOrder;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\StockCategory;
use App\Models\StockItem;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting;
use App\Services\FinanceLedger;
use App\Services\StockLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EconomyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function stock(): User
    {
        return User::where('role', 'stock')->firstOrFail();
    }

    private function finance(): User
    {
        return User::where('role', 'finance')->firstOrFail();
    }

    private function assertLedgerBalanced(): void
    {
        $b = Accounting::trialBalance('2000-01-01', '2100-01-01');
        $this->assertSame($b->sum('debit'), $b->sum('credit'), 'La balance générale doit être équilibrée.');
    }

    /* ------------------------------ Stock --------------------------- */

    public function test_receipt_updates_level_and_weighted_average_cost(): void
    {
        $item = StockItem::create([
            'hotel_id' => 1, 'stock_category_id' => StockCategory::first()->id,
            'name' => 'Test article', 'unit' => 'kg', 'min_qty' => 5, 'avg_cost' => 0,
        ]);
        $wh = Warehouse::first();

        StockLedger::move($item, $wh, 'in', 10, ['reason' => 'purchase_receipt', 'unit_cost' => 1000]);
        StockLedger::move($item, $wh, 'in', 10, ['reason' => 'purchase_receipt', 'unit_cost' => 2000]);

        $this->assertSame(20.0, $item->fresh()->onHand());
        $this->assertSame(1500, $item->fresh()->avg_cost); // CUMP (10*1000 + 10*2000)/20

        StockLedger::move($item, $wh, 'out', 5, ['reason' => 'consumption']);
        $this->assertSame(15.0, $item->fresh()->onHand());
    }

    public function test_issue_beyond_stock_is_rejected(): void
    {
        $item = StockItem::where('hotel_id', 1)->first();
        $wh = $item->levels->first()->warehouse;

        $this->expectException(ValidationException::class);
        StockLedger::move($item, $wh, 'out', 999999, ['reason' => 'consumption']);
    }

    public function test_transfer_moves_quantity_between_warehouses(): void
    {
        $item = StockItem::where('hotel_id', 1)->whereHas('levels')->first();
        $from = $item->levels->first()->warehouse;
        $to = Warehouse::where('id', '!=', $from->id)->first();

        $beforeFrom = (float) $item->levels()->where('warehouse_id', $from->id)->value('quantity');

        StockLedger::transfer($item, $from, $to, 2);

        $this->assertSame($beforeFrom - 2, (float) $item->levels()->where('warehouse_id', $from->id)->value('quantity'));
        $this->assertSame(2.0, (float) $item->levels()->where('warehouse_id', $to->id)->value('quantity'));
    }

    public function test_inventory_close_adjusts_stock(): void
    {
        $wh = Warehouse::first();
        $item = StockItem::where('hotel_id', 1)->first();

        $this->actingAs($this->stock())
            ->post(route('admin.stock.inventory.store'), ['warehouse_id' => $wh->id])
            ->assertRedirect();

        $count = InventoryCount::latest()->first();
        $line = $count->lines()->where('stock_item_id', $item->id)->first();

        $this->actingAs($this->stock())
            ->put(route('admin.stock.inventory.update', $count), ['lines' => [$line->id => 3]]);

        $this->actingAs($this->stock())
            ->post(route('admin.stock.inventory.close', $count))->assertRedirect();

        $this->assertSame('closed', $count->fresh()->status);
        $this->assertSame(3.0, (float) $item->levels()->where('warehouse_id', $wh->id)->value('quantity'));
    }

    /* ---------------------------- Achats --------------------------- */

    public function test_purchase_workflow_receives_stock_and_posts_accounting(): void
    {
        $stock = $this->stock();
        $item = StockItem::where('hotel_id', 1)->first();
        $before = $item->onHand();
        $supplier = Supplier::first();

        $this->actingAs($stock)->post(route('admin.purchases.store'), [
            'supplier_id' => $supplier->id,
            'warehouse_id' => Warehouse::first()->id,
            'lines' => [['stock_item_id' => $item->id, 'quantity' => 4, 'unit_price' => 1250]],
        ])->assertRedirect();

        $po = PurchaseOrder::latest('id')->first();
        $this->assertSame(5000, $po->subtotal);

        $this->actingAs($stock)->post(route('admin.purchases.transition', [$po, 'submit']));
        $this->actingAs(User::where('role', 'direction')->first())->post(route('admin.purchases.approve', $po));
        $this->assertSame('approved', $po->fresh()->status);

        $this->actingAs($stock)->post(route('admin.purchases.transition', [$po, 'order']));
        $this->actingAs($stock)->post(route('admin.purchases.receive', $po), [
            'qty' => [$po->lines->first()->id => 4],
        ])->assertRedirect();

        $this->assertSame('received', $po->fresh()->status);
        $this->assertSame($before + 4, $item->fresh()->onHand());
        $this->assertDatabaseHas('journal_entries', ['journal' => 'AC']);
        $this->assertLedgerBalanced();
    }

    public function test_supplier_invoice_payment_hits_finance_and_accounting(): void
    {
        $po = PurchaseOrder::where('status', 'submitted')->first();
        $invoice = SupplierInvoice::create([
            'hotel_id' => 1, 'supplier_id' => $po->supplier_id, 'purchase_order_id' => $po->id,
            'reference' => 'F-2026-42', 'amount_ht' => 100000, 'tax' => 18000, 'total' => 118000,
            'status' => 'unpaid', 'issued_on' => now()->toDateString(),
        ]);

        $this->actingAs($this->finance())->post(route('admin.purchases.invoice.pay', $invoice), [
            'finance_account_id' => FinanceAccount::where('type', 'bank')->first()->id,
            'method' => 'virement',
            'amount' => 118000,
        ])->assertRedirect();

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertDatabaseHas('finance_transactions', ['direction' => 'expense', 'category' => 'achats', 'amount' => 118000]);
        $this->assertLedgerBalanced();
    }

    /* ---------------------------- Finance -------------------------- */

    public function test_manual_income_and_expense_produce_balanced_entries(): void
    {
        $account = FinanceAccount::where('type', 'cash')->first();
        $entriesBefore = JournalEntry::count();
        $txBefore = FinanceTransaction::count();

        FinanceLedger::record([
            'direction' => 'income', 'category' => 'divers_produits', 'method' => 'especes',
            'amount' => 25000, 'label' => 'Recette test', 'finance_account_id' => $account->id,
        ]);
        FinanceLedger::record([
            'direction' => 'expense', 'category' => 'energie', 'method' => 'especes',
            'amount' => 9000, 'label' => 'Facture SENELEC', 'finance_account_id' => $account->id,
        ]);

        $this->assertSame($txBefore + 2, FinanceTransaction::count());
        $this->assertSame($entriesBefore + 2, JournalEntry::count());
        $this->assertSame(1, JournalEntry::where('label', 'Recette test')->where('journal', 'VT')->count());
        $this->assertLedgerBalanced();
    }

    public function test_reservation_payment_is_mirrored_into_the_finance_journal(): void
    {
        $category = RoomCategory::first();
        $room = Room::whereNotIn('id', Reservation::whereNotNull('room_id')->pluck('room_id'))->first();
        $reservation = Reservation::create([
            'reference' => 'HRK-FIN001', 'hotel_id' => 1,
            'guest_id' => Guest::create(['first_name' => 'P', 'last_name' => 'Q', 'email' => 'pq@example.com'])->id,
            'room_category_id' => $category->id, 'room_id' => $room->id, 'status' => 'checked_in',
            'check_in' => Carbon::yesterday()->toDateString(), 'check_out' => Carbon::tomorrow()->toDateString(),
            'adults' => 1, 'rooms_count' => 1, 'total' => 80000,
        ]);

        $reservation->payments()->create([
            'amount' => 80000, 'method' => 'carte', 'type' => 'balance', 'received_at' => now(),
        ]);

        $this->assertDatabaseHas('finance_transactions', [
            'direction' => 'income', 'category' => 'hebergement', 'amount' => 80000,
        ]);
        $this->assertLedgerBalanced();
    }

    public function test_cash_session_close_records_variance_entry(): void
    {
        $account = FinanceAccount::where('type', 'cash')->first();

        $this->actingAs($this->finance())->post(route('admin.finance.cash.open'), [
            'finance_account_id' => $account->id, 'opening_float' => 50000,
        ])->assertRedirect();

        $session = CashSession::where('status', 'open')->latest()->first();

        // Un manque de 3000.
        $this->actingAs($this->finance())->post(route('admin.finance.cash.close', $session), [
            'counted_amount' => 47000,
        ])->assertRedirect();

        $session->refresh();
        $this->assertSame('closed', $session->status);
        $this->assertSame(-3000, $session->variance);
        $this->assertDatabaseHas('journal_entries', ['journal' => 'OD']);
        $this->assertLedgerBalanced();
    }

    public function test_trial_balance_export_is_csv(): void
    {
        $res = $this->actingAs($this->finance())->get(route('admin.accounting.export'));
        $res->assertOk();
        $this->assertStringContainsString('text/csv', $res->headers->get('content-type'));
    }

    public function test_manual_journal_entry_must_balance(): void
    {
        LedgerAccount::firstOrCreate(['hotel_id' => 1, 'code' => '571000'], ['name' => 'Caisse', 'type' => 'asset']);

        $this->actingAs($this->finance())->post(route('admin.accounting.entries.store'), [
            'journal' => 'OD', 'entry_date' => now()->toDateString(), 'label' => 'Déséquilibrée',
            'lines' => [
                ['code' => '571000', 'debit' => 1000, 'credit' => 0],
                ['code' => '758000', 'debit' => 0, 'credit' => 500],
            ],
        ])->assertSessionHasErrors('lines');

        $this->assertSame(0, JournalEntry::where('label', 'Déséquilibrée')->count());
    }

    /* ----------------------------- RBAC --------------------------- */

    public function test_role_scoping(): void
    {
        $this->actingAs($this->stock())->get(route('admin.stock.index'))->assertOk();
        $this->actingAs($this->stock())->get(route('admin.purchases.index'))->assertOk();
        $this->actingAs($this->finance())->get(route('admin.finance.dashboard'))->assertOk();
        $this->actingAs($this->finance())->get(route('admin.accounting.balance'))->assertOk();

        $this->actingAs(User::where('role', 'housekeeping')->first())->get(route('admin.finance.dashboard'))->assertForbidden();
        $this->actingAs($this->finance())->get(route('admin.stock.item.create'))->assertForbidden();
    }
}
