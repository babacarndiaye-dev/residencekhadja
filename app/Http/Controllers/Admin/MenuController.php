<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\StockItem;
use App\Models\Venue;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    public function index()
    {
        return view('admin.menu.index', [
            'categories' => MenuCategory::query()->ordered()
                ->with(['items' => fn ($q) => $q->orderBy('sort_order')->with('recipe.stockItem'), 'venues'])
                ->get(),
            'venues' => Venue::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    /* --------------------------- Catégories --------------------------- */

    public function storeCategory(Request $request)
    {
        $data = $this->validateCategory($request);
        $data['hotel_id'] = Hotel::current()->id;
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(4));

        $category = MenuCategory::create($data);
        $category->venues()->sync($request->input('venues', []));
        AuditLog::record('menu.category.created', $category);

        return back()->with('status', 'Catégorie créée.');
    }

    public function updateCategory(Request $request, MenuCategory $category)
    {
        $category->update($this->validateCategory($request));
        $category->venues()->sync($request->input('venues', []));
        AuditLog::record('menu.category.updated', $category);

        return back()->with('status', 'Catégorie mise à jour.');
    }

    public function destroyCategory(MenuCategory $category)
    {
        $category->delete();
        AuditLog::record('menu.category.deleted', $category);

        return back()->with('status', 'Catégorie supprimée.');
    }

    /* ----------------------------- Items ----------------------------- */

    public function createItem()
    {
        return view('admin.menu.item', [
            'item' => new MenuItem(['is_available' => true]),
            'categories' => MenuCategory::ordered()->get(),
        ] + $this->recipeRefs());
    }

    public function editItem(MenuItem $item)
    {
        $item->load('optionGroups.options', 'recipe');

        return view('admin.menu.item', [
            'item' => $item,
            'categories' => MenuCategory::ordered()->get(),
        ] + $this->recipeRefs());
    }

    /** @return array<string,mixed> listes pour l'éditeur de recette */
    private function recipeRefs(): array
    {
        return [
            'stockItems' => StockItem::active()->orderBy('name')->get(['id', 'name', 'unit', 'avg_cost']),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function storeItem(Request $request)
    {
        $data = $this->validateItem($request);
        $data['image'] = $this->resolveImage($request, null) ?? $data['image'];

        $item = MenuItem::create($data + [
            'hotel_id' => Hotel::current()->id,
            'slug' => Str::slug($request->input('name')).'-'.Str::lower(Str::random(4)),
        ]);
        $this->syncOptions($request, $item);
        $this->syncRecipe($request, $item);
        AuditLog::record('menu.item.created', $item);

        return redirect()->route('admin.menu.index')->with('status', 'Article créé.');
    }

    public function updateItem(Request $request, MenuItem $item)
    {
        $data = $this->validateItem($request);
        $uploaded = $this->resolveImage($request, $item);
        if ($uploaded !== null) {
            $data['image'] = $uploaded;
        }

        $item->update($data);
        $this->syncOptions($request, $item);
        $this->syncRecipe($request, $item);
        AuditLog::record('menu.item.updated', $item);

        return redirect()->route('admin.menu.index')->with('status', 'Article mis à jour.');
    }

    public function toggleItem(MenuItem $item)
    {
        $item->update(['is_available' => ! $item->is_available]);

        return back()->with('status', "« {$item->name} » ".($item->is_available ? 'disponible' : 'indisponible').'.');
    }

    public function destroyItem(MenuItem $item)
    {
        $item->delete();
        AuditLog::record('menu.item.deleted', $item);

        return back()->with('status', 'Article supprimé.');
    }

    /* ---------------------------- Helpers ---------------------------- */

    private function validateCategory(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:180'],
            'room_service' => ['nullable', 'boolean'],
            'needs_kitchen' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99'],
        ]) + [
            'room_service' => $request->boolean('room_service'),
            'needs_kitchen' => $request->boolean('needs_kitchen'),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function validateItem(Request $request): array
    {
        $v = $request->validate([
            'menu_category_id' => ['required', 'exists:menu_categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:400'],
            'story' => ['nullable', 'string', 'max:600'],
            'price' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'string', 'max:400'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'allergens' => ['nullable', 'string', 'max:200'],
            'tags' => ['nullable', 'string', 'max:200'],
            'is_available' => ['nullable', 'boolean'],
            'is_signature' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99'],
        ]);

        unset($v['image_file']);
        $v['allergens'] = $this->splitList($request->input('allergens'));
        $v['tags'] = $this->splitList($request->input('tags'));
        $v['is_available'] = $request->boolean('is_available');
        $v['is_signature'] = $request->boolean('is_signature');

        return $v;
    }

    /** Retourne le chemin du fichier téléversé (disque public), ou null si aucun. */
    private function resolveImage(Request $request, ?MenuItem $item): ?string
    {
        if (! $request->hasFile('image_file')) {
            return null;
        }

        if ($item?->image && ! Str::startsWith($item->image, ['http://', 'https://', '/'])) {
            Storage::disk('public')->delete($item->image);
        }

        return $request->file('image_file')->store('menu', 'public');
    }

    private function splitList(?string $value): array
    {
        return collect(explode(',', (string) $value))
            ->map(fn ($s) => trim($s))->filter()->values()->all();
    }

    private function syncOptions(Request $request, MenuItem $item): void
    {
        $item->optionGroups()->delete();

        foreach ($request->input('groups', []) as $gi => $group) {
            if (empty($group['name'])) {
                continue;
            }
            $g = $item->optionGroups()->create([
                'name' => Str::limit($group['name'], 60, ''),
                'type' => in_array($group['type'] ?? 'single', ['single', 'multi'], true) ? $group['type'] : 'single',
                'required' => ! empty($group['required']),
                'sort_order' => $gi,
            ]);
            foreach ($group['options'] ?? [] as $oi => $opt) {
                if (empty($opt['name'])) {
                    continue;
                }
                $g->options()->create([
                    'name' => Str::limit($opt['name'], 60, ''),
                    'price_delta' => (int) ($opt['price_delta'] ?? 0),
                    'sort_order' => $oi,
                ]);
            }
        }
    }

    /** Enregistre la recette (nomenclature de stock) de l'article. */
    private function syncRecipe(Request $request, MenuItem $item): void
    {
        $item->recipe()->delete();

        $seen = [];
        foreach ($request->input('recipe', []) as $line) {
            $stockItemId = (int) ($line['stock_item_id'] ?? 0);
            $qty = (float) ($line['quantity'] ?? 0);
            if ($stockItemId <= 0 || $qty <= 0 || isset($seen[$stockItemId])) {
                continue;
            }
            $seen[$stockItemId] = true;

            $item->recipe()->create([
                'stock_item_id' => $stockItemId,
                'warehouse_id' => ! empty($line['warehouse_id']) ? (int) $line['warehouse_id'] : null,
                'quantity' => $qty,
            ]);
        }
    }
}
