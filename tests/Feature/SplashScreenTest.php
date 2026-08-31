<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Splash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SplashScreenTest extends TestCase
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

    /** @param  array<string, mixed>  $overrides */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'enabled' => '1',
            'animation' => 'cinematic',
            'background_from' => '#1b2023',
            'background_to' => '#3a2a22',
            'glow' => '1',
            'welcome_text' => 'Bienvenue',
            'hotel_name' => '',
            'signature' => 'Votre confort, notre priorité',
            'duration_seconds' => '2.6',
        ], $overrides);
    }

    public function test_splash_renders_on_the_public_site_by_default(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('id="hrk-splash"', false)
            ->assertSee('Bienvenue')
            ->assertSee(config('hotel.name'))
            ->assertSee('Votre confort, notre priorité');
    }

    public function test_splash_can_be_disabled(): void
    {
        $this->actingAs($this->direction())
            ->put(route('admin.splash.update'), $this->payload(['enabled' => '0']))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertFalse(Splash::enabled());
        $this->get(route('home'))->assertOk()->assertDontSee('id="hrk-splash"', false);
    }

    public function test_direction_can_open_the_editor_and_the_preview(): void
    {
        $this->actingAs($this->direction())->get(route('admin.splash.edit'))
            ->assertOk()->assertSee('Écran d’accueil');

        $this->actingAs($this->direction())->get(route('admin.splash.preview'))
            ->assertOk()->assertSee('id="hrk-splash"', false);
    }

    public function test_editor_is_reserved_to_direction(): void
    {
        $reception = User::where('role', 'reception')->firstOrFail();

        $this->actingAs($reception)->get(route('admin.splash.edit'))->assertForbidden();
        $this->actingAs($reception)->put(route('admin.splash.update'), $this->payload())->assertForbidden();
    }

    public function test_updating_content_persists_and_shows_on_the_site(): void
    {
        $this->actingAs($this->direction())
            ->put(route('admin.splash.update'), $this->payload([
                'welcome_text' => 'Karibu',
                'hotel_name' => 'Résidence Khadidja',
                'signature' => 'L’hospitalité, autrement',
                'animation' => 'zoom',
                'duration_seconds' => '3.2',
            ]))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('site_settings', ['key' => 'splash.welcome_text', 'value' => 'Karibu']);
        $this->assertSame(3200, Splash::view()['duration_ms']);

        $this->get(route('home'))->assertOk()
            ->assertSee('Karibu')
            ->assertSee('Résidence Khadidja')
            ->assertSee('L’hospitalité, autrement')
            ->assertSee('data-animation="zoom"', false)
            ->assertSee('data-duration="3200"', false);
    }

    public function test_invalid_background_colour_is_rejected(): void
    {
        $this->actingAs($this->direction())
            ->put(route('admin.splash.update'), $this->payload(['background_from' => 'bleu nuit']))
            ->assertSessionHasErrors('background_from');

        $this->assertDatabaseMissing('site_settings', ['key' => 'splash.background_from']);
    }

    public function test_custom_logo_is_stored_and_served(): void
    {
        Storage::fake('public');

        $this->actingAs($this->direction())
            ->put(route('admin.splash.update'), $this->payload([
                'logo' => UploadedFile::fake()->image('badge.png', 240, 240),
            ]))
            ->assertRedirect()->assertSessionHasNoErrors();

        $path = Splash::all()['logo_path'];
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
        $this->assertStringContainsString('/storage/splash/', Splash::view()['logo_url']);

        $this->get(route('home'))->assertOk()->assertSee(Splash::view()['logo_url'], false);
    }

    public function test_preview_reflects_unsaved_query_overrides(): void
    {
        $this->actingAs($this->direction())
            ->get(route('admin.splash.preview', ['welcome_text' => 'Salut', 'animation' => 'minimal']))
            ->assertOk()
            ->assertSee('Salut')
            ->assertSee('data-animation="minimal"', false);
    }
}
