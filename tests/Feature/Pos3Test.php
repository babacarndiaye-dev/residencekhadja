<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Venue;
use App\Services\Analytics;
use App\Services\CartPricer;
use App\Services\KhadijaAI;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Phase 3 POS : recettes → stock + marge, reporting, alertes, poste (PIN),
 * PWA directeur, assistant KHADJA AI, réglages centralisés.
 */
class Pos3Test extends TestCase
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

    /** Article de carte doté d'une recette (semée par EconomySeeder). */
    private function recipeItem(): MenuItem
    {
        $venue = $this->venue();
        $ids = $venue->menuCategories()->pluck('menu_categories.id');

        return MenuItem::with('recipe.stockItem')
            ->whereIn('menu_category_id', $ids)
            ->has('recipe')->available()->doesntHave('optionGroups')
            ->firstOrFail();
    }

    private function ring(MenuItem $item, int $qty = 1): Order
    {
        $venue = $this->venue();
        $lines = [['item_id' => $item->id, 'quantity' => $qty]];
        $total = app(CartPricer::class)->priceForVenue($venue, $lines)['total'];

        $ref = $this->actingAs($this->cashier())->postJson(route('admin.pos.checkout'), [
            'venue_id' => $venue->id, 'sale_type' => 'restaurant', 'lines' => $lines,
            'payments' => [['method' => 'especes', 'amount' => $total]],
        ])->assertOk()->json('reference');

        return Order::where('reference', $ref)->firstOrFail();
    }

    /* ------------------------------------------------------------------ */

    public function test_a_sale_decrements_recipe_stock_and_a_refund_restores_it(): void
    {
        $item = $this->recipeItem();
        $ing = $item->recipe->first();
        $before = (float) StockLevel::where('stock_item_id', $ing->stock_item_id)->sum('quantity');

        $order = $this->ring($item, 2);

        $this->assertNotNull($order->fresh()->stock_applied_at);
        $expectedOut = (float) $ing->quantity * 2;
        $this->assertEqualsWithDelta($before - $expectedOut, (float) StockLevel::where('stock_item_id', $ing->stock_item_id)->sum('quantity'), 0.001);
        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $ing->stock_item_id, 'direction' => 'out', 'reason' => 'consumption',
        ]);

        // remboursement total → recrédit
        $this->actingAs($this->cashier())->postJson(route('admin.pos.refund', $order), [
            'reason' => 'Plat renvoyé', 'manager_pin' => '2468',
        ])->assertOk();

        $this->assertNull($order->fresh()->stock_applied_at);
        $this->assertEqualsWithDelta($before, (float) StockLevel::where('stock_item_id', $ing->stock_item_id)->sum('quantity'), 0.001);
        $this->assertSame(1, StockMovement::where('stock_item_id', $ing->stock_item_id)->where('reason', 'return')->count());
    }

    public function test_bestsellers_report_carries_cost_and_margin(): void
    {
        $item = $this->recipeItem();
        $this->ring($item, 3);

        $rows = collect(Analytics::posBestsellers(Carbon::today(), Carbon::today()));
        $row = $rows->firstWhere('name', $item->name);

        $this->assertNotNull($row);
        $this->assertGreaterThan(0, $row['cost']);
        $this->assertSame($row['revenue'] - $row['cost'], $row['margin']);
        $this->assertNotNull($row['margin_pct']);
    }

    public function test_pos_alerts_notify_on_low_recipe_stock_and_dedupe(): void
    {
        DatabaseNotification::query()->delete();

        $ing = $this->recipeItem()->recipe->first()->stockItem;
        // Force le stock sous le seuil.
        StockLevel::where('stock_item_id', $ing->id)->update(['quantity' => 0]);
        $ing->update(['min_qty' => 5]);

        $this->artisan('pos:alerts')->assertSuccessful();
        $first = User::where('role', 'stock')->firstOrFail()->notifications()->count();
        $this->assertGreaterThan(0, $first);

        // 2ᵉ passage le même jour → pas de doublon
        $this->artisan('pos:alerts')->assertSuccessful();
        $this->assertSame($first, User::where('role', 'stock')->firstOrFail()->notifications()->count());
    }

    public function test_operator_takes_a_station_with_a_pin(): void
    {
        $direction = User::where('role', 'direction')->firstOrFail(); // PIN 2468 (seed)

        $this->actingAs($this->cashier())->post(route('admin.pos.station'), [
            'user_id' => $direction->id, 'pin' => '0000', 'station' => 'bar',
        ])->assertStatus(422);

        $this->actingAs($this->cashier())->post(route('admin.pos.station'), [
            'user_id' => $direction->id, 'pin' => '2468', 'station' => 'bar',
        ])->assertRedirect(route('admin.pos.index'))
            ->assertSessionHas('pos.operator_id', $direction->id)
            ->assertSessionHas('pos.station', 'bar');

        $this->actingAs($this->cashier())->withSession([
            'pos.operator_id' => $direction->id, 'pos.operator_name' => $direction->name, 'pos.station' => 'bar',
        ])->get(route('admin.pos.register'))->assertOk()->assertSee('Bar');
    }

    public function test_pilotage_pwa_is_direction_only(): void
    {
        $this->actingAs($this->cashier())->get(route('pilotage.home'))->assertForbidden();

        $this->actingAs(User::where('role', 'direction')->firstOrFail())
            ->get(route('pilotage.home'))->assertOk()->assertSee('Pilotage');

        $this->actingAs(User::where('role', 'direction')->firstOrFail())
            ->get(route('pilotage.manifest'))->assertOk()->assertJsonPath('scope', '/pilotage');
    }

    public function test_khadija_ai_returns_an_answer_when_configured(): void
    {
        config(['services.anthropic.key' => null]);
        $this->assertFalse(KhadijaAI::configured());
        $this->assertFalse(KhadijaAI::ask('CA du jour ?', $this->cashier())['ok']);

        config(['services.anthropic.key' => 'sk-test']);
        Http::fake(['*' => Http::response([
            'content' => [['type' => 'text', 'text' => 'Le CA du jour est de 0 FCFA.']],
            'stop_reason' => 'end_turn',
        ])]);

        $result = KhadijaAI::ask("Quel est mon CA aujourd'hui ?", $this->cashier());
        $this->assertTrue($result['ok']);
        $this->assertStringContainsString('CA du jour', $result['answer']);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/v1/messages') && $r->hasHeader('x-api-key', 'sk-test'));
    }

    public function test_pos_settings_are_in_the_manifest_and_override_config(): void
    {
        // Le groupe « Caisse (POS) » est bien exposé dans le manifeste des réglages.
        $keys = collect(SiteSettings::fields())->pluck('key');
        $this->assertTrue($keys->contains('pos.discount.max_percent'));
        $this->assertTrue($keys->contains('pos.ticket_footer'));

        // Une valeur enregistrée surcharge la config au boot (SiteSettings::apply).
        SiteSettings::put(['pos.discount.max_percent' => '5']);
        SiteSettings::apply();

        $this->assertSame(5, (int) config('pos.discount.max_percent'));
    }
}
