<?php

namespace Tests\Feature;

use App\Models\PoolAsset;
use App\Models\PoolReservation;
use App\Models\User;
use App\Services\PoolBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PoolTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        PoolReservation::query()->delete(); // repart d'un plan vide (le seed en pose 3)
    }

    private function reception(): User
    {
        return User::where('role', 'reception')->firstOrFail();
    }

    private function transat(): PoolAsset
    {
        return PoolAsset::where('kind', 'transat')->firstOrFail();
    }

    private function today(): string
    {
        return Carbon::today()->toDateString();
    }

    public function test_booking_stores_the_reservation_and_prices_it_from_the_slot(): void
    {
        $asset = $this->transat();

        $res = PoolBooking::book([
            'pool_asset_id' => $asset->id,
            'date' => Carbon::today()->addDay()->toDateString(),
            'slot' => 'full_day',
            'guest_name' => 'M. Test',
        ]);

        $this->assertSame('booked', $res->status);
        $this->assertSame($asset->full_day_price, $res->price);
    }

    public function test_full_day_blocks_a_half_day_on_the_same_asset(): void
    {
        $asset = $this->transat();
        PoolBooking::book(['pool_asset_id' => $asset->id, 'date' => $this->today(), 'slot' => 'full_day', 'guest_name' => 'A']);

        $this->assertFalse(PoolBooking::isFree($asset, $this->today(), 'morning'));

        $this->expectException(ValidationException::class);
        PoolBooking::book(['pool_asset_id' => $asset->id, 'date' => $this->today(), 'slot' => 'morning', 'guest_name' => 'B']);
    }

    public function test_morning_and_afternoon_can_share_an_asset(): void
    {
        $asset = $this->transat();
        PoolBooking::book(['pool_asset_id' => $asset->id, 'date' => $this->today(), 'slot' => 'morning', 'guest_name' => 'A']);
        PoolBooking::book(['pool_asset_id' => $asset->id, 'date' => $this->today(), 'slot' => 'afternoon', 'guest_name' => 'B']);

        $this->assertSame(2, PoolReservation::where('pool_asset_id', $asset->id)->whereDate('date', $this->today())->count());
    }

    public function test_a_cancelled_reservation_frees_the_slot(): void
    {
        $asset = $this->transat();
        $res = PoolBooking::book(['pool_asset_id' => $asset->id, 'date' => $this->today(), 'slot' => 'full_day', 'guest_name' => 'A']);

        $res->update(['status' => 'cancelled']);

        $this->assertTrue(PoolBooking::isFree($asset, $this->today(), 'full_day'));
    }

    public function test_guests_over_capacity_are_rejected(): void
    {
        $transat = $this->transat(); // capacity 1

        $this->expectException(ValidationException::class);
        PoolBooking::book(['pool_asset_id' => $transat->id, 'date' => $this->today(), 'slot' => 'morning', 'guest_name' => 'A', 'guests' => 3]);
    }

    public function test_reception_books_and_advances_status_via_the_screen(): void
    {
        $asset = PoolAsset::where('kind', 'cabana')->firstOrFail();

        $this->actingAs($this->reception())
            ->post(route('admin.pool.store'), [
                'pool_asset_id' => $asset->id,
                'date' => Carbon::today()->addDays(2)->toDateString(),
                'slot' => 'afternoon',
                'guest_name' => 'Groupe Faye',
                'guests' => 4,
            ])
            ->assertRedirect();

        $res = PoolReservation::where('guest_name', 'Groupe Faye')->firstOrFail();

        $this->actingAs($this->reception())
            ->post(route('admin.pool.status', $res), ['status' => 'checked_in'])
            ->assertRedirect();

        $this->assertSame('checked_in', $res->fresh()->status);
    }

    public function test_the_screen_is_forbidden_for_housekeeping(): void
    {
        $hk = User::where('role', 'housekeeping')->firstOrFail();

        $this->actingAs($hk)->get(route('admin.pool.index'))->assertForbidden();
    }

    public function test_reception_can_add_a_pool_asset(): void
    {
        $this->actingAs($this->reception())
            ->post(route('admin.pool.assets.store'), [
                'kind' => 'daybed', 'label' => 'Bain VIP 1', 'capacity' => 2,
                'half_day_price' => 8000, 'full_day_price' => 14000,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('pool_assets', ['label' => 'Bain VIP 1', 'kind' => 'daybed']);
    }
}
