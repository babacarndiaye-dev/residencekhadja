<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Applique (ou contre-passe) la consommation de stock d'une commande d'après la
 * recette de chaque article vendu. Réutilise `StockLedger::move()` (décrément +
 * CUMP). Sans effet si la table des recettes n'existe pas ou si aucun article
 * n'a de recette.
 */
class RecipeConsumption
{
    /**
     * @param  'out'|'in'  $direction  'out' = vente (décrément), 'in' = remboursement (recrédit)
     */
    public static function applyForOrder(Order $order, string $direction = 'out'): void
    {
        if (! Schema::hasTable('menu_item_recipes')) {
            return;
        }

        // Idempotence : une commande ne consomme qu'une fois, ne se contre-passe qu'une fois.
        if ($direction === 'out' && $order->stock_applied_at) {
            return;
        }
        if ($direction === 'in' && ! $order->stock_applied_at) {
            return;
        }

        $order->loadMissing('items.menuItem.recipe.stockItem.category', 'items.menuItem.recipe.warehouse');

        $warehouses = Warehouse::where('hotel_id', $order->hotel_id)->where('is_active', true)->get()->keyBy('code');
        $touched = false;

        foreach ($order->items as $line) {
            $recipe = $line->menuItem?->recipe ?? collect();
            foreach ($recipe as $ing) {
                $stockItem = $ing->stockItem;
                if (! $stockItem) {
                    continue;
                }

                $warehouse = $ing->warehouse ?: self::resolveWarehouse($warehouses, $stockItem->category?->slug);
                if (! $warehouse) {
                    continue;
                }

                StockLedger::move(
                    $stockItem,
                    $warehouse,
                    $direction,
                    (float) $ing->quantity * (int) $line->quantity,
                    [
                        'reason' => $direction === 'out' ? 'consumption' : 'return',
                        'source' => $order,
                        'allow_negative' => true,
                        'note' => 'Vente '.$order->reference,
                    ],
                );
                $touched = true;
            }
        }

        if ($touched) {
            $order->forceFill(['stock_applied_at' => $direction === 'out' ? now() : null])->save();
        }
    }

    /** @param  Collection<string, Warehouse>  $warehouses */
    private static function resolveWarehouse($warehouses, ?string $categorySlug): ?Warehouse
    {
        $map = config('pos.recipe.warehouse_by_stock_category', []);
        $code = ($categorySlug && isset($map[$categorySlug]))
            ? $map[$categorySlug]
            : config('pos.recipe.default_warehouse_code');

        return $warehouses->get($code) ?? $warehouses->first();
    }
}
