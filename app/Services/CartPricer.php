<?php

namespace App\Services;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\QrLocation;
use App\Models\Venue;
use Illuminate\Validation\ValidationException;

/**
 * Valide un panier / ticket et calcule les montants. Tous les prix (articles,
 * options) sont recalculés côté serveur : le client ne peut pas influer sur le total.
 * Utilisé par la carte QR (QrLocation) et par la caisse restaurant (Venue).
 */
class CartPricer
{
    /**
     * @param  array<int,array{item_id:int,quantity:int,options?:array<int>,note?:string}>  $lines
     * @return array{lines: array<int,array<string,mixed>>, subtotal:int, service_charge:int, discount:int, tax:int, total:int}
     */
    public function price(QrLocation $location, array $lines): array
    {
        $categoryIds = $location->venue
            ? $location->venue->menuCategories()->pluck('menu_categories.id')->all()
            : [];

        return $this->priceLines($categoryIds, $lines);
    }

    /**
     * Variante caisse : on facture au niveau du point de restauration.
     *
     * @param  int|array{type:string,value:int}  $discount  montant fixe, ou {type:'amount'|'percent', value}
     */
    public function priceForVenue(?Venue $venue, array $lines, int|array $discount = 0): array
    {
        $categoryIds = $venue
            ? $venue->menuCategories()->pluck('menu_categories.id')->all()
            : MenuCategory::active()->pluck('id')->all();

        return $this->priceLines($categoryIds, $lines, $discount);
    }

    /**
     * @param  array<int,int>  $categoryIds
     * @param  int|array{type:string,value:int}  $discount
     */
    private function priceLines(array $categoryIds, array $lines, int|array $discount = 0): array
    {
        if (empty($lines)) {
            throw ValidationException::withMessages(['cart' => 'Le ticket est vide.']);
        }

        $items = MenuItem::query()
            ->with('optionGroups.options', 'category:id,needs_kitchen')
            ->whereIn('id', collect($lines)->pluck('item_id')->unique())
            ->get()
            ->keyBy('id');

        $priced = [];
        $subtotal = 0;

        foreach ($lines as $i => $line) {
            $item = $items->get($line['item_id'] ?? 0);

            if (! $item || ! $item->is_available || ! in_array($item->menu_category_id, $categoryIds, true)) {
                throw ValidationException::withMessages([
                    "cart.$i" => '« '.($item->name ?? 'Un article')." » n'est plus disponible.",
                ]);
            }

            $qty = max(1, min(50, (int) ($line['quantity'] ?? 1)));
            $chosen = array_map('intval', $line['options'] ?? []);
            $unit = $item->price;
            $optionSnapshot = [];

            foreach ($item->optionGroups as $group) {
                $groupOptionIds = $group->options->pluck('id')->all();
                $picked = array_values(array_intersect($chosen, $groupOptionIds));

                if ($group->type === 'single' && count($picked) > 1) {
                    throw ValidationException::withMessages(["cart.$i" => "Un seul choix pour « {$group->name} »."]);
                }
                if ($group->required && count($picked) === 0) {
                    throw ValidationException::withMessages(["cart.$i" => "Choix requis : « {$group->name} »."]);
                }

                foreach ($group->options->whereIn('id', $picked) as $opt) {
                    $unit += $opt->price_delta;
                    $optionSnapshot[] = [
                        'id' => $opt->id,
                        'group' => $group->name,
                        'name' => $opt->name,
                        'price_delta' => (int) $opt->price_delta,
                    ];
                }
            }

            $lineTotal = $unit * $qty;
            $subtotal += $lineTotal;

            $priced[] = [
                'menu_item_id' => $item->id,
                'name' => $item->name,
                'unit_price' => $unit,
                'quantity' => $qty,
                'options' => $optionSnapshot,
                'note' => isset($line['note']) ? mb_substr(trim((string) $line['note']), 0, 180) : null,
                'line_total' => $lineTotal,
                'needs_kitchen' => (bool) ($item->category?->needs_kitchen ?? true),
            ];
        }

        ['amount' => $discountAmount, 'percent' => $discountPercent] = $this->resolveDiscount($discount, $subtotal);
        $serviceCharge = (int) round($subtotal * config('menu.service_charge_rate', 0));
        $tax = (int) round(($subtotal - $discountAmount + $serviceCharge) * config('menu.tax_rate', 0));

        return [
            'lines' => $priced,
            'subtotal' => $subtotal,
            'service_charge' => $serviceCharge,
            'discount' => $discountAmount,
            'discount_percent' => $discountPercent,
            'tax' => $tax,
            'total' => $subtotal - $discountAmount + $serviceCharge + $tax,
        ];
    }

    /**
     * @param  int|array{type:string,value:int}  $spec
     * @return array{amount:int, percent:?int}
     */
    private function resolveDiscount(int|array $spec, int $subtotal): array
    {
        if (is_array($spec)) {
            $value = max(0, (int) ($spec['value'] ?? 0));

            if (($spec['type'] ?? 'amount') === 'percent') {
                $value = min($value, 100);

                return ['amount' => min((int) round($subtotal * $value / 100), $subtotal), 'percent' => $value ?: null];
            }

            return ['amount' => min($value, $subtotal), 'percent' => null];
        }

        return ['amount' => max(0, min($spec, $subtotal)), 'percent' => null];
    }
}
