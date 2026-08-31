<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin(): User
    {
        return User::where('role', 'admin')->firstOrFail();
    }

    /**
     * Nombre de liens de menu actifs (le partial de nav est rendu deux fois, bureau + mobile,
     * donc on divise par deux). On cible la classe complète propre aux liens de nav pour ne
     * pas compter d'éventuels `bg-terracotta-500` présents dans le contenu de la page.
     */
    private function activeLinks(string $html): int
    {
        return substr_count($html, 'text-sm font-medium bg-terracotta-500 text-white') / 2;
    }

    public function test_exactly_one_menu_item_is_active_on_every_ambiguous_page(): void
    {
        $routes = [
            'admin.rooms.index', 'admin.rooms.manage',
            'admin.hr.dashboard', 'admin.hr.schedule', 'admin.hr.attendance', 'admin.hr.payroll.index',
            'admin.finance.dashboard', 'admin.finance.cash',
            'admin.crm.dashboard', 'admin.crm.loyalty',
            'admin.events.pipeline', 'admin.events.index',
            'admin.dashboard',
        ];

        $admin = $this->admin();

        foreach ($routes as $route) {
            $html = $this->actingAs($admin)->get(route($route))->assertOk()->getContent();
            $this->assertSame(1, $this->activeLinks($html), "Plus d'un lien actif sur {$route}");
        }
    }

    public function test_the_active_item_is_the_most_specific_one(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.rooms.manage'))->getContent();

        // « Gestion des chambres » actif, « Plan des chambres » ne l'est pas.
        $this->assertStringContainsString('bg-terracotta-500 text-white', $html);
        $this->assertMatchesRegularExpression('/bg-terracotta-500 text-white[^<]*>\s*Gestion des chambres/', $html);
        $this->assertDoesNotMatchRegularExpression('/bg-terracotta-500 text-white[^<]*>\s*Plan des chambres/', $html);
    }

    public function test_the_group_holding_the_current_page_is_pre_opened(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.hr.schedule'))->getContent();

        // Accordéon : le groupe de la page courante est ouvert d'office (semé côté serveur).
        $this->assertStringContainsString("navGroup: 'rh-paie'", $html);
    }

    public function test_no_group_is_pre_opened_outside_the_groups(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.dashboard'))->getContent();

        $this->assertStringContainsString('navGroup: null', $html);
    }

    public function test_the_sidebar_has_no_emoji_icons(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.dashboard'))->getContent();

        foreach (['🏠', '🛎️', '🍽️', '🧹', '📦', '💳', '⭐', '🌐', '👥', '📊'] as $emoji) {
            $this->assertStringNotContainsString($emoji, $html, "Icône {$emoji} encore présente dans le menu");
        }
    }
}
