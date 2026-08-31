<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoomCategoryAdminTest extends TestCase
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

    public function test_direction_creates_a_category_that_shows_on_the_vitrine(): void
    {
        Storage::fake('public');

        $this->actingAs($this->direction())
            ->post(route('admin.room_categories.store'), [
                'name' => 'Villa Océan',
                'category' => 'suite',
                'price' => 250000,
                'capacity' => 4,
                'size' => 90,
                'bed' => '1 lit king + 2 simples',
                'view' => 'Océan',
                'short' => 'La plus spacieuse',
                'description' => 'Vaste villa privée avec terrasse.',
                'amenities' => 'Terrasse, Cuisine, Jacuzzi',
                'is_active' => '1',
                'featured' => '1',
                'photos' => [UploadedFile::fake()->image('villa.jpg', 1200, 800)],
            ])
            ->assertRedirect(route('admin.room_categories.index'));

        $category = RoomCategory::where('name', 'Villa Océan')->firstOrFail();
        $this->assertSame('villa-ocean', $category->slug);
        $this->assertSame(['Terrasse', 'Cuisine', 'Jacuzzi'], $category->amenities);
        $this->assertCount(1, $category->images);
        Storage::disk('public')->assertExists($category->images[0]);

        // La vitrine liste la nouvelle catégorie.
        $this->get(route('rooms.index'))->assertOk()->assertSee('Villa Océan');
    }

    public function test_slug_is_suffixed_when_it_collides(): void
    {
        RoomCategory::create([
            'hotel_id' => 1, 'slug' => 'duplex-vue-mer', 'name' => 'Duplex Vue Mer',
            'category' => 'suite', 'price' => 1, 'capacity' => 2, 'is_active' => true,
        ]);

        $this->actingAs($this->direction())
            ->post(route('admin.room_categories.store'), [
                'name' => 'Duplex Vue Mer', 'category' => 'suite', 'price' => 200000, 'capacity' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('room_categories', ['slug' => 'duplex-vue-mer-2']);
    }

    public function test_update_replaces_amenities_and_can_drop_a_photo(): void
    {
        Storage::fake('public');
        $category = RoomCategory::first();
        $category->update(['images' => ['room-categories/old.jpg', 'https://cdn/x.jpg']]);
        Storage::disk('public')->put('room-categories/old.jpg', 'x');

        $this->actingAs($this->direction())
            ->put(route('admin.room_categories.update', $category), [
                'name' => $category->name,
                'category' => $category->category,
                'price' => $category->price,
                'capacity' => $category->capacity,
                'amenities' => 'Wifi, Clim',
                'is_active' => '1',
                'remove_images' => ['room-categories/old.jpg'],
            ])
            ->assertRedirect();

        $category->refresh();
        $this->assertSame(['Wifi', 'Clim'], $category->amenities);
        $this->assertSame(['https://cdn/x.jpg'], array_values($category->images));
        Storage::disk('public')->assertMissing('room-categories/old.jpg');
    }

    public function test_cannot_delete_a_category_that_has_rooms(): void
    {
        $category = Room::first()->category;

        $this->actingAs($this->direction())
            ->delete(route('admin.room_categories.destroy', $category))
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('room_categories', ['id' => $category->id]);
    }

    public function test_deletes_an_empty_category(): void
    {
        $category = RoomCategory::create([
            'hotel_id' => 1, 'slug' => 'temp-cat', 'name' => 'Temp', 'category' => 'chambre',
            'price' => 1000, 'capacity' => 1, 'is_active' => false,
        ]);

        $this->actingAs($this->direction())
            ->delete(route('admin.room_categories.destroy', $category))
            ->assertRedirect(route('admin.room_categories.index'));

        $this->assertDatabaseMissing('room_categories', ['id' => $category->id]);
    }

    public function test_category_crud_is_direction_only(): void
    {
        $reception = User::where('role', 'reception')->firstOrFail();

        $this->actingAs($reception)->get(route('admin.room_categories.index'))->assertForbidden();
    }
}
