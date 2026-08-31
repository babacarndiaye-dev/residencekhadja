<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\StockCategory;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\StockLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StockController extends Controller
{
    /* ------------------------------ Articles -------------------------- */

    public function index(Request $request)
    {
        $categoryId = $request->integer('categorie') ?: null;
        $onlyLow = $request->boolean('ruptures');

        $items = StockItem::query()
            ->with(['category', 'levels.warehouse'])
            ->when($categoryId, fn ($q) => $q->where('stock_category_id', $categoryId))
            ->orderBy('name')
            ->get();

        if ($onlyLow) {
            $items = $items->filter->isBelowThreshold()->values();
        }

        return view('admin.stock.index', [
            'items' => $items,
            'categories' => StockCategory::orderBy('sort_order')->get(),
            'activeCategory' => $categoryId,
            'onlyLow' => $onlyLow,
            'lowCount' => StockItem::active()->get()->filter->isBelowThreshold()->count(),
            'totalValue' => StockItem::active()->get()->sum->stockValue(),
        ]);
    }

    public function createItem()
    {
        return view('admin.stock.item', [
            'item' => new StockItem(['is_active' => true, 'unit' => 'unité']),
            'categories' => StockCategory::orderBy('name')->get(),
            'units' => config('stock.units'),
        ]);
    }

    public function editItem(StockItem $item)
    {
        return view('admin.stock.item', [
            'item' => $item->load('levels.warehouse'),
            'categories' => StockCategory::orderBy('name')->get(),
            'units' => config('stock.units'),
        ]);
    }

    public function storeItem(Request $request)
    {
        $data = $this->validateItem($request);
        $data['hotel_id'] = Hotel::current()->id;
        $data['sku'] = $data['sku'] ?: 'ART-'.Str::upper(Str::random(6));

        $item = StockItem::create($data);
        AuditLog::record('stock.item.created', $item);

        return redirect()->route('admin.stock.index')->with('status', 'Article créé.');
    }

    public function updateItem(Request $request, StockItem $item)
    {
        $item->update($this->validateItem($request, $item));
        AuditLog::record('stock.item.updated', $item);

        return redirect()->route('admin.stock.index')->with('status', 'Article mis à jour.');
    }

    /* ------------------------------ Mouvements ------------------------ */

    public function movements(Request $request)
    {
        $filters = $request->validate([
            'item' => ['nullable', 'integer'],
            'warehouse' => ['nullable', 'integer'],
            'direction' => ['nullable', 'in:in,out'],
        ]);

        $movements = StockMovement::query()
            ->with(['item', 'warehouse', 'user'])
            ->when($filters['item'] ?? null, fn ($q, $v) => $q->where('stock_item_id', $v))
            ->when($filters['warehouse'] ?? null, fn ($q, $v) => $q->where('warehouse_id', $v))
            ->when($filters['direction'] ?? null, fn ($q, $v) => $q->where('direction', $v))
            ->latest('created_at')
            ->paginate(40)->withQueryString();

        return view('admin.stock.movements', [
            'movements' => $movements,
            'filters' => $filters,
            'items' => StockItem::active()->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'reasons' => config('stock.movement_reasons'),
        ]);
    }

    public function storeMovement(Request $request)
    {
        $data = $request->validate([
            'stock_item_id' => ['required', 'exists:stock_items,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'direction' => ['required', 'in:in,out'],
            'reason' => ['required', Rule::in(array_keys(config('stock.movement_reasons')))],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit_cost' => ['nullable', 'integer', 'min:0'],
            'reference' => ['nullable', 'string', 'max:60'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $item = StockItem::findOrFail($data['stock_item_id']);
        $warehouse = Warehouse::findOrFail($data['warehouse_id']);

        StockLedger::move($item, $warehouse, $data['direction'], (float) $data['quantity'], [
            'reason' => $data['reason'],
            'unit_cost' => $data['unit_cost'] ?? null,
            'reference' => $data['reference'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        return back()->with('status', 'Mouvement enregistré.');
    }

    /* ------------------------------ Transferts ---------------------- */

    public function transfers(Request $request)
    {
        $groups = StockMovement::query()
            ->whereNotNull('transfer_group')
            ->with(['item', 'warehouse'])
            ->latest('created_at')
            ->get()
            ->groupBy('transfer_group')
            ->take(40);

        return view('admin.stock.transfers', [
            'groups' => $groups,
            'items' => StockItem::active()->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeTransfer(Request $request)
    {
        $data = $request->validate([
            'stock_item_id' => ['required', 'exists:stock_items,id'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'different:from_warehouse_id', 'exists:warehouses,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        StockLedger::transfer(
            StockItem::findOrFail($data['stock_item_id']),
            Warehouse::findOrFail($data['from_warehouse_id']),
            Warehouse::findOrFail($data['to_warehouse_id']),
            (float) $data['quantity'],
            $data['note'] ?? null,
        );

        return back()->with('status', 'Transfert effectué.');
    }

    /* --------------------- Fournisseurs & magasins ----------------- */

    public function suppliers()
    {
        return view('admin.stock.suppliers', [
            'suppliers' => Supplier::withCount('purchaseOrders')->orderBy('name')->get(),
            'warehouses' => Warehouse::orderBy('name')->get(),
            'warehouseTypes' => config('stock.warehouse_types'),
        ]);
    }

    public function storeSupplier(Request $request)
    {
        Supplier::create($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'payment_terms' => ['nullable', 'string', 'max:80'],
        ]) + ['hotel_id' => Hotel::current()->id]);

        return back()->with('status', 'Fournisseur ajouté.');
    }

    public function updateSupplier(Request $request, Supplier $supplier)
    {
        $supplier->update($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'payment_terms' => ['nullable', 'string', 'max:80'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Fournisseur mis à jour.');
    }

    public function storeWarehouse(Request $request)
    {
        Warehouse::create($request->validate([
            'name' => ['required', 'string', 'max:80'],
            'code' => ['nullable', 'string', 'max:20'],
            'type' => ['required', Rule::in(array_keys(config('stock.warehouse_types')))],
        ]) + ['hotel_id' => Hotel::current()->id]);

        return back()->with('status', 'Magasin créé.');
    }

    /* ---------------------------- Helpers -------------------------- */

    private function validateItem(Request $request, ?StockItem $item = null): array
    {
        return $request->validate([
            'stock_category_id' => ['required', 'exists:stock_categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['nullable', 'string', 'max:40'],
            'unit' => ['required', Rule::in(config('stock.units'))],
            'min_qty' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:300'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
