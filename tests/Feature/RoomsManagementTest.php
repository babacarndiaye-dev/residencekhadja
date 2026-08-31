<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RoomsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function reception(): User
    {
        return User::where('role', 'reception')->firstOrFail();
    }

    public function test_manage_page_lists_rooms_for_reception(): void
    {
        $this->actingAs($this->reception())
            ->get(route('admin.rooms.manage'))
            ->assertOk()
            ->assertSee('Gestion des chambres')
            ->assertSee(Room::first()->number);
    }

    public function test_manage_page_is_forbidden_for_housekeeping(): void
    {
        $hk = User::where('role', 'housekeeping')->firstOrFail();

        $this->actingAs($hk)->get(route('admin.rooms.manage'))->assertForbidden();
    }

    public function test_reception_creates_a_single_room(): void
    {
        $category = RoomCategory::first();

        $this->actingAs($this->reception())
            ->post(route('admin.rooms.store'), [
                'mode' => 'single',
                'room_category_id' => $category->id,
                'status' => 'propre',
                'floor' => '5',
                'number' => '5001',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rooms', [
            'number' => '5001',
            'room_category_id' => $category->id,
            'floor' => '5',
            'is_active' => true,
        ]);
    }

    public function test_single_room_rejects_a_duplicate_number(): void
    {
        $existing = Room::first();
        $before = Room::count();

        $this->actingAs($this->reception())
            ->post(route('admin.rooms.store'), [
                'mode' => 'single',
                'room_category_id' => $existing->room_category_id,
                'status' => 'propre',
                'number' => $existing->number,
            ])
            ->assertSessionHasErrors('number');

        $this->assertSame($before, Room::count());
    }

    public function test_reception_bulk_creates_a_range_and_skips_existing(): void
    {
        $category = RoomCategory::first();

        $this->actingAs($this->reception())
            ->post(route('admin.rooms.store'), [
                'mode' => 'bulk',
                'room_category_id' => $category->id,
                'status' => 'propre',
                'prefix' => 'B',
                'from' => 1,
                'to' => 3,
                'pad' => 3,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rooms', ['number' => 'B001']);
        $this->assertDatabaseHas('rooms', ['number' => 'B002']);
        $this->assertDatabaseHas('rooms', ['number' => 'B003']);

        // Rejouer la même série : tout est ignoré, rien n'est dupliqué.
        $this->actingAs($this->reception())
            ->post(route('admin.rooms.store'), [
                'mode' => 'bulk',
                'room_category_id' => $category->id,
                'status' => 'propre',
                'prefix' => 'B',
                'from' => 1,
                'to' => 3,
                'pad' => 3,
            ])
            ->assertRedirect();

        $this->assertSame(1, Room::where('number', 'B002')->count());
    }

    public function test_reception_renames_a_room(): void
    {
        $room = Room::first();

        $this->actingAs($this->reception())
            ->put(route('admin.rooms.update', $room), [
                'number' => '9999',
                'room_category_id' => $room->room_category_id,
                'notes' => 'Vue jardin',
            ])
            ->assertRedirect();

        $room->refresh();
        $this->assertSame('9999', $room->number);
        $this->assertSame('Vue jardin', $room->notes);
    }

    public function test_deactivating_a_room_removes_it_from_the_sellable_count(): void
    {
        $room = Room::where('status', 'propre')->where('is_active', true)->firstOrFail();
        $category = $room->category;
        $before = $category->sellableRoomsCount();

        $this->actingAs($this->reception())
            ->post(route('admin.rooms.toggle', $room))
            ->assertRedirect();

        $room->refresh();
        $this->assertFalse($room->is_active);
        $this->assertSame('hors_service', $room->status);
        $this->assertSame($before - 1, $category->sellableRoomsCount());

        // Réactivation.
        $this->actingAs($this->reception())
            ->post(route('admin.rooms.toggle', $room))
            ->assertRedirect();
        $this->assertTrue($room->refresh()->is_active);
    }

    public function test_cannot_deactivate_an_occupied_room(): void
    {
        $room = Room::where('is_active', true)->firstOrFail();
        $room->update(['status' => 'occupee']);

        $this->actingAs($this->reception())
            ->post(route('admin.rooms.toggle', $room))
            ->assertSessionHasErrors('room');

        $this->assertTrue($room->refresh()->is_active);
    }

    public function test_cannot_delete_a_room_linked_to_reservations(): void
    {
        $room = Room::first();
        $category = $room->category;

        Reservation::create([
            'reference' => 'HRK-DEL01',
            'hotel_id' => $category->hotel_id,
            'guest_id' => Guest::create(['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.com'])->id,
            'room_category_id' => $category->id,
            'room_id' => $room->id,
            'status' => 'checked_in',
            'check_in' => Carbon::today()->toDateString(),
            'check_out' => Carbon::today()->addDay()->toDateString(),
            'adults' => 1, 'rooms_count' => 1,
            'room_total' => 50000, 'total' => 50000, 'deposit' => 0,
        ]);

        $this->actingAs($this->reception())
            ->delete(route('admin.rooms.destroy', $room))
            ->assertSessionHasErrors('room');

        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
    }

    public function test_deletes_an_unused_room(): void
    {
        $room = Room::create([
            'hotel_id' => Room::first()->hotel_id,
            'room_category_id' => RoomCategory::first()->id,
            'number' => 'TMP-1',
            'status' => 'propre',
            'is_active' => true,
        ]);

        $this->actingAs($this->reception())
            ->delete(route('admin.rooms.destroy', $room))
            ->assertRedirect();

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }
}
