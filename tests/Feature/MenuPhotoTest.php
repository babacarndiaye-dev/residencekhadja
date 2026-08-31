<?php

namespace Tests\Feature;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MenuPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function restaurant(): User
    {
        return User::where('role', 'restaurant')->firstOrFail();
    }

    public function test_a_dish_photo_can_be_uploaded_and_is_served_from_storage(): void
    {
        Storage::fake('public');
        $category = MenuCategory::first();

        $this->actingAs($this->restaurant())
            ->post(route('admin.menu.item.store'), [
                'menu_category_id' => $category->id,
                'name' => 'Thiéboudieune royal',
                'price' => 6500,
                'image_file' => UploadedFile::fake()->image('thieb.jpg', 800, 600),
            ])
            ->assertRedirect(route('admin.menu.index'));

        $item = MenuItem::where('name', 'Thiéboudieune royal')->firstOrFail();
        $this->assertStringStartsWith('menu/', $item->image);
        Storage::disk('public')->assertExists($item->image);
        $this->assertStringContainsString('/storage/menu/', $item->imageUrl());
    }

    public function test_pasting_an_external_url_still_works(): void
    {
        $category = MenuCategory::first();

        $this->actingAs($this->restaurant())
            ->post(route('admin.menu.item.store'), [
                'menu_category_id' => $category->id,
                'name' => 'Yassa poulet du test',
                'price' => 5000,
                'image' => 'https://images.unsplash.com/photo-123?w=800',
            ])
            ->assertRedirect();

        $item = MenuItem::where('name', 'Yassa poulet du test')->firstOrFail();
        $this->assertSame('https://images.unsplash.com/photo-123?w=800', $item->image);
        $this->assertSame('https://images.unsplash.com/photo-123?w=800', $item->imageUrl());
    }

    public function test_uploading_a_replacement_deletes_the_old_file(): void
    {
        Storage::fake('public');
        $item = MenuItem::whereNotNull('image')->first() ?? MenuItem::first();
        $item->update(['image' => 'menu/old.jpg']);
        Storage::disk('public')->put('menu/old.jpg', 'x');

        $this->actingAs($this->restaurant())
            ->put(route('admin.menu.item.update', $item), [
                'menu_category_id' => $item->menu_category_id,
                'name' => $item->name,
                'price' => $item->price,
                'image_file' => UploadedFile::fake()->image('new.jpg'),
            ])
            ->assertRedirect();

        Storage::disk('public')->assertMissing('menu/old.jpg');
        $this->assertStringStartsWith('menu/', $item->fresh()->image);
    }
}
