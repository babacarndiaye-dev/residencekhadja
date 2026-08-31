<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\GuestRequest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class RoomDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }

    private function room(array $attrs = []): Room
    {
        $category = RoomCategory::first();

        return Room::create(array_merge([
            'hotel_id' => $category->hotel_id,
            'room_category_id' => $category->id,
            'number' => 'T'.random_int(100, 999),
            'floor' => '2',
            'status' => 'propre',
            'is_active' => true,
        ], $attrs));
    }

    private function stay(Room $room, array $attrs = [], array $guestAttrs = []): Reservation
    {
        $guest = Guest::create(array_merge([
            'civility' => 'M.',
            'first_name' => 'Amadou',
            'last_name' => 'Diop'.random_int(10, 99),
            'email' => 'diop'.random_int(1000, 9999).'@example.com',
            'phone' => '+221 77 123 45 67',
            'country' => 'Sénégal',
        ], $guestAttrs));

        return Reservation::create(array_merge([
            'reference' => 'HRK-'.Str::upper(Str::random(6)),
            'hotel_id' => $room->hotel_id,
            'guest_id' => $guest->id,
            'room_category_id' => $room->room_category_id,
            'room_id' => $room->id,
            'status' => 'checked_in',
            'channel' => 'direct',
            'check_in' => Carbon::yesterday()->toDateString(),
            'check_out' => Carbon::tomorrow()->toDateString(),
            'checked_in_at' => Carbon::yesterday()->setTime(14, 0),
            'adults' => 2,
            'children' => 0,
            'rooms_count' => 1,
            'total' => 200000,
        ], $attrs));
    }

    public function test_occupied_room_dashboard_aggregates_stay_client_folio_and_requests(): void
    {
        $room = $this->room();
        $stay = $this->stay($room);

        $stay->charges()->create(['label' => 'Restaurant — RS-014', 'amount' => 32500]);
        $stay->payments()->create(['amount' => 150000, 'method' => 'carte', 'type' => 'deposit', 'received_at' => now()]);

        GuestRequest::create([
            'hotel_id' => $room->hotel_id, 'reservation_id' => $stay->id, 'room_id' => $room->id,
            'type' => 'linge', 'routed_to' => 'housekeeping', 'status' => 'open',
        ]);

        // Séjour passé sur la même chambre.
        $past = $this->stay($room, [
            'status' => 'checked_out',
            'check_in' => Carbon::now()->subDays(20)->toDateString(),
            'check_out' => Carbon::now()->subDays(18)->toDateString(),
            'total' => 90000,
        ], ['last_name' => 'AncienClient']);

        $this->actingAs($this->user('reception'))
            ->get(route('admin.rooms.show', $room))
            ->assertOk()
            ->assertSee('Chambre '.$room->number)
            ->assertSee($stay->guest->last_name)
            ->assertSee($stay->reference)
            ->assertSee('Détails du séjour')
            ->assertSee('Restaurant — RS-014')
            ->assertSee('Change du linge / serviettes')   // libellé du type de demande
            ->assertSee('Folio du séjour')
            ->assertSee('Solde à payer')
            ->assertSee('Historique des séjours')
            ->assertSee('AncienClient');

        // Solde = (200000 + 32500) - 150000.
        $this->assertSame(82500, $stay->fresh()->balance());
    }

    public function test_vacant_room_shows_placeholder(): void
    {
        $room = $this->room(['status' => 'libre']);

        $this->actingAs($this->user('reception'))
            ->get(route('admin.rooms.show', $room))
            ->assertOk()
            ->assertSee('Chambre inoccupée')
            ->assertDontSee('Folio du séjour');
    }

    public function test_housekeeping_sees_state_but_not_folio_or_contact(): void
    {
        $room = $this->room();
        $stay = $this->stay($room);

        $this->actingAs($this->user('housekeeping'))
            ->get(route('admin.rooms.show', $room))
            ->assertOk()
            ->assertSee('État de la chambre')
            ->assertSee($stay->guest->last_name)
            ->assertDontSee('Folio du séjour')
            ->assertDontSee($stay->guest->email);
    }

    public function test_family_profile_badge_is_derived_from_the_stay(): void
    {
        $room = $this->room();
        $this->stay($room, ['children' => 2]);

        $this->actingAs($this->user('reception'))
            ->get(route('admin.rooms.show', $room))
            ->assertOk()
            ->assertSee('Famille');
    }

    public function test_board_links_to_each_room_dashboard(): void
    {
        $room = $this->room();

        $this->actingAs($this->user('reception'))
            ->get(route('admin.rooms.index'))
            ->assertOk()
            ->assertSee(route('admin.rooms.show', $room), false);
    }

    public function test_literal_gestion_route_is_not_captured_by_the_room_binding(): void
    {
        $this->actingAs($this->user('direction'))
            ->get(route('admin.rooms.manage'))
            ->assertOk();
    }

    public function test_dashboard_is_reserved_to_operational_roles(): void
    {
        $room = $this->room();

        $this->actingAs($this->user('stock'))
            ->get(route('admin.rooms.show', $room))
            ->assertForbidden();
    }
}
