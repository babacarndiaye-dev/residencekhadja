<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Branding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingTest extends TestCase
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

    public function test_logo_falls_back_to_the_shipped_files_by_default(): void
    {
        $this->assertStringContainsString('img/logo-hrk.svg', Branding::logo());
        $this->assertStringContainsString('img/logo-hrk-mono.svg', Branding::logoMono());

        $this->get(route('home'))->assertOk()->assertSee('img/logo-hrk.svg', false);
    }

    public function test_editor_is_reserved_to_direction(): void
    {
        $reception = User::where('role', 'reception')->firstOrFail();

        $this->actingAs($reception)->get(route('admin.branding.edit'))->assertForbidden();
        $this->actingAs($reception)->put(route('admin.branding.update'))->assertForbidden();
    }

    public function test_direction_can_open_the_editor(): void
    {
        $this->actingAs($this->direction())->get(route('admin.branding.edit'))
            ->assertOk()->assertSee('Identité visuelle');
    }

    public function test_uploaded_logo_is_stored_and_served_across_the_site(): void
    {
        Storage::fake('public');

        $this->actingAs($this->direction())
            ->put(route('admin.branding.update'), [
                'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
                'logo_mono' => UploadedFile::fake()->image('logo-mono.png', 200, 200),
            ])
            ->assertRedirect()->assertSessionHasNoErrors();

        $paths = Branding::paths();
        $this->assertNotNull($paths['logo']);
        $this->assertNotNull($paths['logo_mono']);
        Storage::disk('public')->assertExists($paths['logo']);
        Storage::disk('public')->assertExists($paths['logo_mono']);

        $this->assertDatabaseHas('site_settings', ['key' => 'branding.logo_path']);
        $this->assertStringContainsString('/storage/branding/', Branding::logo());

        $this->get(route('home'))->assertOk()->assertSee(Branding::logo(), false);
    }

    public function test_logo_can_be_reset_to_the_default(): void
    {
        Storage::fake('public');

        $this->actingAs($this->direction())
            ->put(route('admin.branding.update'), ['logo' => UploadedFile::fake()->image('logo.png', 200, 200)]);

        $stored = Branding::paths()['logo'];
        $this->assertNotNull($stored);

        $this->actingAs($this->direction())
            ->put(route('admin.branding.update'), ['remove_logo' => '1'])
            ->assertRedirect()->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($stored);
        $this->assertNull(Branding::paths()['logo']);
        $this->assertStringContainsString('img/logo-hrk.svg', Branding::logo());
    }

    public function test_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->direction())
            ->put(route('admin.branding.update'), [
                'logo' => UploadedFile::fake()->create('logo.pdf', 20, 'application/pdf'),
            ])
            ->assertSessionHasErrors('logo');

        $this->assertNull(Branding::paths()['logo']);
    }
}
