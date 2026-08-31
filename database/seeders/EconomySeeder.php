<?php

namespace Database\Seeders;

use App\Models\FinanceAccount;
use App\Models\Hotel;
use App\Models\LedgerAccount;
use App\Models\MenuItem;
use App\Models\MenuItemRecipe;
use App\Models\PurchaseOrder;
use App\Models\StockCategory;
use App\Models\StockItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting;
use App\Services\StockLedger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EconomySeeder extends Seeder
{
    public function run(): void
    {
        $hotel = Hotel::current();

        foreach ([
            ['Économat', 'economat@residence-khadija.sn', 'stock', 'Magasinier'],
            ['Comptabilité', 'finance@residence-khadija.sn', 'finance', 'Comptable'],
        ] as [$name, $email, $role, $title]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $name, 'role' => $role, 'job_title' => $title,
                'hotel_id' => $hotel->id, 'is_active' => true, 'password' => Hash::make('khadija'),
            ]);
        }

        // Plan de comptes.
        foreach (config('accounting.chart') as $code => [$label, $type]) {
            LedgerAccount::updateOrCreate(
                ['hotel_id' => $hotel->id, 'code' => $code],
                ['name' => $label, 'type' => $type, 'is_active' => true],
            );
        }

        // Comptes financiers.
        foreach (config('finance.default_accounts') as $acc) {
            FinanceAccount::updateOrCreate(
                ['hotel_id' => $hotel->id, 'code' => $acc['code']],
                ['name' => $acc['name'], 'type' => $acc['type'], 'is_active' => true],
            );
        }

        // Catégories de stock.
        $cats = [];
        $i = 0;
        foreach (config('stock.categories') as $slug => $label) {
            $cats[$slug] = StockCategory::updateOrCreate(
                ['hotel_id' => $hotel->id, 'slug' => $slug],
                ['name' => $label, 'sort_order' => $i++],
            );
        }

        // Magasins.
        $warehouses = [];
        foreach ([
            ['Magasin général', 'MG', 'general'],
            ['Réserve cuisine', 'RC', 'kitchen'],
            ['Cave / bar', 'CB', 'bar'],
            ['Lingerie', 'LG', 'housekeeping'],
        ] as [$n, $code, $type]) {
            $warehouses[$type] = Warehouse::updateOrCreate(
                ['hotel_id' => $hotel->id, 'code' => $code],
                ['name' => $n, 'type' => $type, 'is_active' => true],
            );
        }

        // Fournisseurs.
        foreach ([
            'Grossiste Alimentaire Thiès', 'Boissons du Sénégal SARL', 'Hygiène Pro Dakar', 'Économat Central',
        ] as $n) {
            Supplier::updateOrCreate(
                ['hotel_id' => $hotel->id, 'name' => $n],
                ['is_active' => true, 'payment_terms' => '30 jours'],
            );
        }

        $this->seedItems($hotel, $cats, $warehouses);
        $this->seedDemoPurchase($hotel, $warehouses);
        $this->seedRecipes($hotel);
    }

    /** Quelques recettes de démo pour le décrément de stock + la marge (P3). */
    private function seedRecipes(Hotel $hotel): void
    {
        if (MenuItemRecipe::query()->exists()) {
            return;
        }

        $stock = StockItem::where('hotel_id', $hotel->id)->get()->keyBy('name');
        $menu = MenuItem::where('hotel_id', $hotel->id)->get();

        // [fragment du nom d'article de carte => [ [nom ingrédient, quantité], … ]]
        $recipes = [
            'Thiéboudienne' => [['Riz brisé parfumé', 0.15], ['Poisson thiof (frais)', 0.25], ['Oignons', 0.1], ['Huile végétale 20 L', 0.03]],
            'Yassa' => [['Poulet fermier', 0.3], ['Oignons', 0.2], ['Riz brisé parfumé', 0.12]],
            'Mafé' => [['Pâte d’arachide', 0.12], ['Poulet fermier', 0.25], ['Oignons', 0.1]],
            'Bissap' => [['Bissap séché', 0.03]],
            'Café' => [['Café en grains', 0.012]],
        ];

        foreach ($menu as $item) {
            foreach ($recipes as $fragment => $lines) {
                if (! str_contains(mb_strtolower($item->name), mb_strtolower($fragment))) {
                    continue;
                }
                foreach ($lines as [$name, $qty]) {
                    $stockItem = $stock->get($name);
                    if ($stockItem) {
                        $item->recipe()->firstOrCreate(['stock_item_id' => $stockItem->id], ['quantity' => $qty]);
                    }
                }
            }
        }
    }

    private function seedItems(Hotel $hotel, array $cats, array $warehouses): void
    {
        if (StockItem::where('hotel_id', $hotel->id)->exists()) {
            return;
        }

        // [nom, catégorie, unité, seuil, coût, stock initial, magasin]
        $rows = [
            ['Riz brisé parfumé', 'aliments', 'sac', 4, 22000, 12, 'kitchen'],
            ['Huile végétale 20 L', 'aliments', 'unité', 3, 21000, 8, 'kitchen'],
            ['Oignons', 'aliments', 'kg', 15, 700, 40, 'kitchen'],
            ['Poisson thiof (frais)', 'aliments', 'kg', 8, 4500, 14, 'kitchen'],
            ['Poulet fermier', 'aliments', 'kg', 10, 3200, 22, 'kitchen'],
            ['Pâte d’arachide', 'aliments', 'kg', 6, 2500, 10, 'kitchen'],
            ['Bissap séché', 'aliments', 'kg', 3, 3800, 6, 'kitchen'],
            ['Eau minérale 50 cl', 'boissons', 'carton', 10, 4200, 30, 'bar'],
            ['Soda 33 cl', 'boissons', 'carton', 8, 6500, 18, 'bar'],
            ['Bière locale 33 cl', 'boissons', 'carton', 6, 12000, 9, 'bar'],
            ['Café en grains', 'boissons', 'kg', 4, 6000, 8, 'kitchen'],
            ['Gel hydroalcoolique 5 L', 'entretien', 'unité', 4, 9000, 10, 'general'],
            ['Détergent sol 5 L', 'entretien', 'unité', 5, 4500, 12, 'general'],
            ['Papier toilette (colis 48)', 'consommables', 'lot', 6, 14000, 15, 'general'],
            ['Serviettes de bain', 'consommables', 'unité', 40, 3500, 120, 'housekeeping'],
            ['Produits d’accueil (kit)', 'consommables', 'lot', 30, 1200, 90, 'housekeeping'],
            ['Barquettes room service', 'emballages', 'lot', 10, 8000, 20, 'kitchen'],
            ['Sacs poubelle 100 L', 'emballages', 'rouleau', 8, 2500, 16, 'general'],
        ];

        foreach ($rows as [$name, $cat, $unit, $min, $cost, $qty, $wh]) {
            $item = StockItem::create([
                'hotel_id' => $hotel->id,
                'stock_category_id' => $cats[$cat]->id,
                'sku' => 'ART-'.Str::upper(Str::random(6)),
                'name' => $name,
                'unit' => $unit,
                'min_qty' => $min,
                'avg_cost' => $cost,
                'is_active' => true,
            ]);

            StockLedger::move($item, $warehouses[$wh], 'in', $qty, [
                'reason' => 'initial', 'unit_cost' => $cost, 'note' => 'Stock initial',
            ]);
        }
    }

    private function seedDemoPurchase(Hotel $hotel, array $warehouses): void
    {
        if (PurchaseOrder::where('hotel_id', $hotel->id)->exists()) {
            return;
        }

        $supplier = Supplier::where('hotel_id', $hotel->id)->first();
        $magasinier = User::where('role', 'stock')->first();
        $items = StockItem::where('hotel_id', $hotel->id)->inRandomOrder()->take(3)->get();

        $order = PurchaseOrder::create([
            'reference' => 'PO-'.Str::upper(Str::random(6)),
            'hotel_id' => $hotel->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouses['kitchen']->id,
            'requested_by' => $magasinier->id,
            'status' => 'submitted',
            'expected_on' => now()->addDays(3),
            'submitted_at' => now(),
        ]);

        $subtotal = 0;
        foreach ($items as $it) {
            $qty = random_int(3, 10);
            $price = (int) max(500, round($it->avg_cost * 1.05));
            $order->lines()->create([
                'stock_item_id' => $it->id, 'quantity' => $qty,
                'unit_price' => $price, 'line_total' => $qty * $price,
            ]);
            $subtotal += $qty * $price;
        }

        $tax = (int) round($subtotal * config('stock.tax_rate', 0));
        $order->update(['subtotal' => $subtotal, 'tax' => $tax, 'total' => $subtotal + $tax]);

        // Une écriture d'ouverture pour peupler la compta.
        Accounting::post('OD', now()->subDay(), 'À nouveau — trésorerie initiale', [
            ['account' => '571000', 'debit' => 2000000],
            ['account' => '521000', 'debit' => 8000000],
            ['account' => '758000', 'credit' => 10000000],
        ]);
    }
}
