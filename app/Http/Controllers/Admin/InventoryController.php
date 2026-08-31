<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\InventoryCount;
use App\Models\StockItem;
use App\Models\StockLevel;
use App\Models\Warehouse;
use App\Services\StockLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryController extends Controller
{
    public function index()
    {
        return view('admin.stock.inventory.index', [
            'counts' => InventoryCount::with(['warehouse', 'user'])->withCount('lines')->latest()->paginate(20),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $warehouse = Warehouse::findOrFail($data['warehouse_id']);
        $userId = $request->user()->id;

        $count = DB::transaction(function () use ($warehouse, $data, $userId) {
            $count = InventoryCount::create([
                'hotel_id' => Hotel::current()->id,
                'warehouse_id' => $warehouse->id,
                'user_id' => $userId,
                'reference' => 'INV-'.Str::upper(Str::random(6)),
                'status' => 'open',
                'note' => $data['note'] ?? null,
            ]);

            // Une ligne par article actif, avec le stock système figé.
            foreach (StockItem::active()->get() as $item) {
                $system = (float) StockLevel::where('stock_item_id', $item->id)
                    ->where('warehouse_id', $warehouse->id)->value('quantity');

                $count->lines()->create([
                    'stock_item_id' => $item->id,
                    'system_qty' => $system,
                ]);
            }

            return $count;
        });

        return redirect()->route('admin.stock.inventory.show', $count);
    }

    public function show(InventoryCount $inventoryCount)
    {
        $inventoryCount->load(['warehouse', 'lines.item.category']);

        return view('admin.stock.inventory.show', ['count' => $inventoryCount]);
    }

    public function update(Request $request, InventoryCount $inventoryCount)
    {
        abort_unless($inventoryCount->status === 'open', 422);

        $data = $request->validate([
            'lines' => ['required', 'array'],
            'lines.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach ($inventoryCount->lines as $line) {
            if (array_key_exists($line->id, $data['lines'])) {
                $value = $data['lines'][$line->id];
                $line->update(['counted_qty' => $value === null || $value === '' ? null : (float) $value]);
            }
        }

        return back()->with('status', 'Comptage enregistré.');
    }

    public function close(Request $request, InventoryCount $inventoryCount)
    {
        abort_unless($inventoryCount->status === 'open', 422);

        DB::transaction(function () use ($inventoryCount) {
            $inventoryCount->load('lines.item', 'warehouse');

            foreach ($inventoryCount->lines as $line) {
                if ($line->counted_qty === null) {
                    continue;
                }
                StockLedger::setLevel($line->item, $inventoryCount->warehouse, (float) $line->counted_qty, $inventoryCount);
            }

            $inventoryCount->update(['status' => 'closed', 'closed_at' => now()]);
        });

        AuditLog::record('stock.inventory.closed', $inventoryCount);

        return redirect()->route('admin.stock.inventory.index')
            ->with('status', "Inventaire {$inventoryCount->reference} clôturé, stocks ajustés.");
    }
}
