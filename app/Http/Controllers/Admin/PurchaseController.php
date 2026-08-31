<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FinanceAccount;
use App\Models\GoodsReceipt;
use App\Models\Hotel;
use App\Models\PurchaseOrder;
use App\Models\StockItem;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\Warehouse;
use App\Services\Accounting;
use App\Services\FinanceLedger;
use App\Services\StockLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $orders = PurchaseOrder::query()
            ->with(['supplier', 'warehouse'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25)->withQueryString();

        return view('admin.purchases.index', [
            'orders' => $orders,
            'status' => $status,
            'statuses' => PurchaseOrder::STATUSES,
            'summary' => [
                'to_approve' => PurchaseOrder::where('status', 'submitted')->count(),
                'ordered' => PurchaseOrder::whereIn('status', ['ordered', 'partially_received'])->count(),
                'payable' => (int) SupplierInvoice::outstandingScope()->selectRaw('sum(total - paid_amount) as d')->value('d'),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.purchases.form', [
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'items' => StockItem::active()->with('category')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'expected_on' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:300'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.stock_item_id' => ['required', 'exists:stock_items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit_price' => ['required', 'integer', 'min:0'],
        ]);

        $order = DB::transaction(function () use ($data, $request) {
            $order = PurchaseOrder::create([
                'reference' => 'PO-'.Str::upper(Str::random(6)),
                'hotel_id' => Hotel::current()->id,
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'requested_by' => $request->user()->id,
                'status' => 'draft',
                'expected_on' => $data['expected_on'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            foreach ($data['lines'] as $line) {
                $order->lines()->create([
                    'stock_item_id' => $line['stock_item_id'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => (int) round($line['quantity'] * $line['unit_price']),
                ]);
            }

            $this->recomputeTotals($order);

            return $order;
        });

        AuditLog::record('purchase.created', $order);

        return redirect()->route('admin.purchases.show', $order);
    }

    public function show(PurchaseOrder $purchase)
    {
        $purchase->load(['supplier', 'warehouse', 'requester', 'approver', 'lines.item', 'receipts.lines', 'invoices']);

        return view('admin.purchases.show', [
            'order' => $purchase,
            'accounts' => FinanceAccount::where('is_active', true)->orderBy('id')->get(),
            'methods' => config('finance.payment_methods'),
        ]);
    }

    public function transition(Request $request, PurchaseOrder $purchase, string $to)
    {
        $map = [
            'submit' => ['from' => ['draft'], 'set' => ['status' => 'submitted', 'submitted_at' => now()]],
            'approve' => ['from' => ['submitted'], 'set' => ['status' => 'approved', 'approved_at' => now(), 'approved_by' => $request->user()->id]],
            'order' => ['from' => ['approved'], 'set' => ['status' => 'ordered', 'ordered_at' => now()]],
            'cancel' => ['from' => ['draft', 'submitted', 'approved', 'ordered'], 'set' => ['status' => 'cancelled']],
        ];

        abort_unless(isset($map[$to]) && in_array($purchase->status, $map[$to]['from'], true), 422);

        // « approve » réservé à la direction.
        abort_unless($to !== 'approve' || $request->user()->hasRole('direction'), 403);

        $purchase->update($map[$to]['set']);
        AuditLog::record("purchase.$to", $purchase);

        return back()->with('status', "Commande {$purchase->reference} : {$purchase->statusLabel()}.");
    }

    public function receive(Request $request, PurchaseOrder $purchase)
    {
        abort_unless(in_array($purchase->status, ['ordered', 'partially_received', 'approved'], true), 422);

        $purchase->load('lines.item');

        $data = $request->validate([
            'qty' => ['required', 'array'],
            'qty.*' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        DB::transaction(function () use ($request, $purchase, $data) {
            $receipt = GoodsReceipt::create([
                'reference' => 'GR-'.Str::upper(Str::random(6)),
                'hotel_id' => $purchase->hotel_id,
                'purchase_order_id' => $purchase->id,
                'received_by' => $request->user()->id,
                'note' => $data['note'] ?? null,
            ]);

            $receivedValue = 0;

            foreach ($purchase->lines as $line) {
                $qty = (float) ($data['qty'][$line->id] ?? 0);
                $qty = min($qty, $line->outstandingQty());
                if ($qty <= 0) {
                    continue;
                }

                $receipt->lines()->create(['purchase_order_line_id' => $line->id, 'quantity' => $qty]);

                StockLedger::move($line->item, $purchase->warehouse, 'in', $qty, [
                    'reason' => 'purchase_receipt',
                    'unit_cost' => $line->unit_price,
                    'reference' => $receipt->reference,
                    'source' => $receipt,
                ]);

                $line->increment('received_qty', $qty);
                $receivedValue += (int) round($qty * $line->unit_price);
            }

            $purchase->update([
                'status' => $purchase->fresh()->load('lines')->isFullyReceived() ? 'received' : 'partially_received',
                'received_at' => now(),
            ]);

            // Comptabilisation de la réception : DR achats + TVA, CR fournisseurs.
            if ($receivedValue > 0) {
                $tax = (int) round($receivedValue * config('stock.tax_rate', 0));
                Accounting::post('AC', now(), "Réception {$receipt->reference} — {$purchase->supplier->name}", [
                    ['account' => '601000', 'debit' => $receivedValue],
                    ['account' => '445100', 'debit' => $tax],
                    ['account' => '401000', 'credit' => $receivedValue + $tax],
                ], $receipt);
            }
        });

        AuditLog::record('purchase.received', $purchase);

        return back()->with('status', 'Réception enregistrée, stock mis à jour.');
    }

    public function storeInvoice(Request $request, PurchaseOrder $purchase)
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:60'],
            'amount_ht' => ['required', 'integer', 'min:0'],
            'tax' => ['nullable', 'integer', 'min:0'],
            'issued_on' => ['required', 'date'],
            'due_on' => ['nullable', 'date'],
        ]);

        SupplierInvoice::create([
            'hotel_id' => $purchase->hotel_id,
            'supplier_id' => $purchase->supplier_id,
            'purchase_order_id' => $purchase->id,
            'reference' => $data['reference'],
            'amount_ht' => $data['amount_ht'],
            'tax' => $data['tax'] ?? 0,
            'total' => $data['amount_ht'] + ($data['tax'] ?? 0),
            'status' => 'unpaid',
            'issued_on' => $data['issued_on'],
            'due_on' => $data['due_on'] ?? null,
        ]);

        return back()->with('status', 'Facture fournisseur enregistrée.');
    }

    public function payInvoice(Request $request, SupplierInvoice $invoice)
    {
        abort_if($invoice->status === 'paid', 422);

        $data = $request->validate([
            'finance_account_id' => ['required', 'exists:finance_accounts,id'],
            'method' => ['required', Rule::in(array_keys(config('finance.payment_methods')))],
            'amount' => ['required', 'integer', 'min:1', 'max:'.$invoice->balance()],
        ]);

        DB::transaction(function () use ($invoice, $data) {
            FinanceLedger::record([
                'direction' => 'expense',
                'category' => 'achats',
                'finance_account_id' => $data['finance_account_id'],
                'method' => $data['method'],
                'amount' => $data['amount'],
                'label' => "Règlement fournisseur {$invoice->supplier->name} — {$invoice->reference}",
                'source' => $invoice,
                'debit_account' => '401000',
            ]);

            $paid = $invoice->paid_amount + $data['amount'];
            $invoice->update([
                'paid_amount' => $paid,
                'status' => $paid >= $invoice->total ? 'paid' : 'partially_paid',
            ]);
        });

        AuditLog::record('purchase.invoice.paid', $invoice, ['amount' => $data['amount']]);

        return back()->with('status', 'Règlement fournisseur enregistré.');
    }

    private function recomputeTotals(PurchaseOrder $order): void
    {
        $subtotal = (int) $order->lines()->sum('line_total');
        $tax = (int) round($subtotal * config('stock.tax_rate', 0));
        $order->update(['subtotal' => $subtotal, 'tax' => $tax, 'total' => $subtotal + $tax]);
    }
}
