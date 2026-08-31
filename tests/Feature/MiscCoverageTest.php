<?php

namespace Tests\Feature;

use App\Models\RatePlan;
use App\Models\RoomCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Écrans peu couverts : Paramètres, sitemap, pages d'erreur. */
class MiscCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function direction(): User
    {
        return User::where('role', 'direction')->firstOrFail();
    }

    public function test_settings_page_lists_categories_and_rate_plans(): void
    {
        $this->actingAs($this->direction())->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee(RoomCategory::first()->name)
            ->assertSee(RatePlan::first()->name);
    }

    public function test_direction_updates_a_category_price_and_capacity(): void
    {
        $category = RoomCategory::first();

        $this->actingAs($this->direction())
            ->put(route('admin.settings.category', $category), [
                'price' => 99000,
                'capacity' => 3,
                'featured' => '1',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $category->refresh();
        $this->assertSame(99000, $category->price);
        $this->assertSame(3, $category->capacity);
        $this->assertTrue($category->featured);
    }

    public function test_direction_updates_a_rate_plan_multiplier(): void
    {
        $plan = RatePlan::first();

        $this->actingAs($this->direction())
            ->put(route('admin.settings.rate_plan', $plan), ['multiplier' => 1.25, 'is_active' => '1'])
            ->assertRedirect();

        $this->assertEquals(1.25, $plan->refresh()->multiplier);
    }

    public function test_settings_are_reserved_to_direction(): void
    {
        $reception = User::where('role', 'reception')->firstOrFail();

        $this->actingAs($reception)->get(route('admin.settings.index'))->assertForbidden();
    }

    public function test_sitemap_is_public_xml_and_lists_key_pages(): void
    {
        $res = $this->get('/sitemap.xml');

        $res->assertOk();
        $this->assertStringContainsString('xml', strtolower($res->headers->get('content-type')));
        $res->assertSee(route('home'), false);
        $res->assertSee(route('rooms.index'), false);
    }

    public function test_unknown_url_serves_the_branded_404(): void
    {
        $this->get('/cette-page-nexiste-pas')
            ->assertNotFound()
            ->assertSee('Erreur 404');
    }
}
