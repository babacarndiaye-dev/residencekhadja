<?php

namespace Tests\Feature;

use App\Models\AvailabilityDay;
use App\Models\Channel;
use App\Models\ChannelReservation;
use App\Models\ChannelSyncLog;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\RoomCategory;
use App\Models\User;
use App\Services\Accounting;
use App\Services\Availability;
use App\Services\ChannelManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DistributionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function category(): RoomCategory
    {
        return RoomCategory::active()->orderBy('sort_order')->first();
    }

    private function channel(string $key = 'booking_com'): Channel
    {
        return Channel::where('key', $key)->firstOrFail();
    }

    private function payload(RoomCategory $cat, array $over = []): array
    {
        return array_merge([
            'external_ref' => 'REF-'.Str::random(8),
            'first_name' => 'Otto', 'last_name' => 'Aiste',
            'room_slug' => $cat->slug,
            'check_in' => Carbon::today()->addDays(60)->toDateString(),
            'check_out' => Carbon::today()->addDays(62)->toDateString(),
            'adults' => 2, 'gross_amount' => 200000,
        ], $over);
    }

    /* --------------------------- Restrictions ------------------------- */

    public function test_stop_sell_blocks_booking(): void
    {
        $cat = $this->category();
        $in = Carbon::today()->addDays(70);

        $this->assertTrue(Availability::canBook($cat, $in->toDateString(), $in->copy()->addDays(2)->toDateString(), 1));

        AvailabilityDay::create([
            'hotel_id' => 1, 'room_category_id' => $cat->id,
            'date' => $in->copy()->addDay()->toDateString(), 'stop_sell' => true,
        ]);

        $this->assertNotEmpty(Availability::stayRestrictions($cat, $in->toDateString(), $in->copy()->addDays(2)->toDateString()));
        $this->assertFalse(Availability::canBook($cat, $in->toDateString(), $in->copy()->addDays(2)->toDateString(), 1));
    }

    public function test_min_stay_blocks_short_stays(): void
    {
        $cat = $this->category();
        $in = Carbon::today()->addDays(80);

        AvailabilityDay::create([
            'hotel_id' => 1, 'room_category_id' => $cat->id,
            'date' => $in->toDateString(), 'min_stay' => 3,
        ]);

        $this->assertFalse(Availability::canBook($cat, $in->toDateString(), $in->copy()->addDays(2)->toDateString(), 1));
        $this->assertTrue(Availability::canBook($cat, $in->toDateString(), $in->copy()->addDays(3)->toDateString(), 1));
    }

    public function test_rooms_open_cap_reduces_remaining(): void
    {
        $cat = $this->category();
        $in = Carbon::today()->addDays(90);

        $full = Availability::remaining($cat, $in->toDateString(), $in->copy()->addDays(2)->toDateString());

        AvailabilityDay::create([
            'hotel_id' => 1, 'room_category_id' => $cat->id,
            'date' => $in->copy()->addDay()->toDateString(), 'rooms_open' => 1,
        ]);

        $this->assertSame(min($full, 1), Availability::remaining($cat, $in->toDateString(), $in->copy()->addDays(2)->toDateString()));
    }

    /* ----------------------- Réservations entrantes ------------------ */

    public function test_ingest_creates_reservation_with_commission_and_is_idempotent(): void
    {
        $cat = $this->category();
        $channel = $this->channel('booking_com');
        $payload = $this->payload($cat, ['external_ref' => 'FIXED-REF-1', 'gross_amount' => 200000]);

        $cr = ChannelManager::ingestReservation($channel, $payload);

        $this->assertSame('imported', $cr->status);
        $this->assertSame((int) round(200000 * $channel->commission_rate), $cr->commission_amount);
        $this->assertNotNull($cr->reservation);
        $this->assertSame($channel->key, $cr->reservation->channel);
        $this->assertSame('confirmed', $cr->reservation->status);

        // Rejeu : même référence externe → même ligne, pas de doublon.
        $again = ChannelManager::ingestReservation($channel, $payload);
        $this->assertSame($cr->id, $again->id);
        $this->assertSame(1, Reservation::where('channel', 'booking_com')->where('reference', $cr->reservation->reference)->count());
    }

    public function test_ingest_respects_stop_sell(): void
    {
        $cat = $this->category();
        $channel = $this->channel('expedia');
        $in = Carbon::today()->addDays(100);

        AvailabilityDay::create([
            'hotel_id' => 1, 'room_category_id' => $cat->id,
            'date' => $in->toDateString(), 'stop_sell' => true,
        ]);

        $this->expectException(ValidationException::class);
        try {
            ChannelManager::ingestReservation($channel, $this->payload($cat, [
                'external_ref' => 'BLOCKED-1',
                'check_in' => $in->toDateString(),
                'check_out' => $in->copy()->addDays(2)->toDateString(),
            ]));
        } finally {
            $this->assertDatabaseHas('channel_reservations', ['external_ref' => 'BLOCKED-1', 'status' => 'failed']);
        }
    }

    public function test_commission_is_posted_on_checkout(): void
    {
        $cat = $this->category();
        $channel = $this->channel('airbnb');
        $cr = ChannelManager::ingestReservation($channel, $this->payload($cat, [
            'external_ref' => 'CO-1', 'gross_amount' => 300000,
            'check_in' => Carbon::today()->subDays(3)->toDateString(),
            'check_out' => Carbon::today()->toDateString(),
        ]));

        $cr->reservation->update(['status' => 'checked_out', 'checked_out_at' => now()]);

        $this->assertTrue($cr->fresh()->commission_posted);
        $this->assertDatabaseHas('finance_transactions', [
            'direction' => 'expense', 'category' => 'commissions_ota',
            'amount' => (int) round(300000 * $channel->commission_rate),
        ]);

        $b = Accounting::trialBalance('2000-01-01', '2100-01-01');
        $this->assertSame($b->sum('debit'), $b->sum('credit'));
    }

    /* ----------------------------- Poussées -------------------------- */

    public function test_push_availability_logs_per_channel(): void
    {
        $before = ChannelSyncLog::where('action', 'push_availability')->count();

        $n = ChannelManager::pushAvailability(Carbon::today(), Carbon::today()->addDays(3));

        $this->assertGreaterThan(0, $n);
        $this->assertSame($before + $n, ChannelSyncLog::where('action', 'push_availability')->count());
        $this->assertNotNull($this->channel('booking_com')->fresh()->last_sync_at);
    }

    public function test_push_command_runs(): void
    {
        $this->artisan('channels:push', ['--days' => 5])->assertSuccessful();
        $this->assertTrue(ChannelSyncLog::where('action', 'push_availability')->exists());
    }

    /* ------------------------------- iCal ---------------------------- */

    public function test_ical_feed_exposes_busy_blocks(): void
    {
        $cat = $this->category();
        Reservation::create([
            'reference' => 'HRK-ICAL01', 'hotel_id' => 1,
            'guest_id' => Guest::create(['first_name' => 'I', 'last_name' => 'Cal', 'email' => 'ical@example.com'])->id,
            'room_category_id' => $cat->id, 'status' => 'confirmed',
            'check_in' => Carbon::today()->addDays(5)->toDateString(),
            'check_out' => Carbon::today()->addDays(8)->toDateString(),
            'adults' => 2, 'rooms_count' => 1, 'total' => 100000,
        ]);

        $res = $this->get(route('channel.ical', $cat->slug));
        $res->assertOk();
        $this->assertStringContainsString('text/calendar', $res->headers->get('content-type'));
        $res->assertSee('BEGIN:VEVENT', false);
        $res->assertSee('SUMMARY:Occupé', false);
    }

    public function test_webhook_is_idempotent(): void
    {
        $cat = $this->category();
        $body = $this->payload($cat, ['external_ref' => 'WH-IDEM-1', 'gross_amount' => 175000]);

        $a = $this->postJson('/distribution/webhook/expedia', $body)->assertOk()->json('reference');
        $b = $this->postJson('/distribution/webhook/expedia', $body)->assertOk()->json('reference');

        $this->assertSame($a, $b);
        $this->assertSame(1, ChannelReservation::where('external_ref', 'WH-IDEM-1')->count());
    }

    public function test_webhook_rejects_a_bad_signature_when_a_secret_is_configured(): void
    {
        config(['distribution.webhook_secret' => 'chan-secret']);
        $body = $this->payload($this->category(), ['external_ref' => 'WH-SIG-BAD']);

        $this->postJson('/distribution/webhook/expedia', $body, ['X-Signature' => 'not-the-signature'])
            ->assertStatus(401)
            ->assertJsonPath('reason', 'bad_signature');

        $this->assertSame(0, ChannelReservation::where('external_ref', 'WH-SIG-BAD')->count());
    }

    public function test_webhook_accepts_a_valid_signature(): void
    {
        config(['distribution.webhook_secret' => 'chan-secret']);
        $body = $this->payload($this->category(), ['external_ref' => 'WH-SIG-OK', 'gross_amount' => 150000]);
        $signature = hash_hmac('sha256', json_encode($body), 'chan-secret');

        $this->postJson('/distribution/webhook/expedia', $body, ['X-Signature' => $signature])->assertOk();

        $this->assertSame(1, ChannelReservation::where('external_ref', 'WH-SIG-OK')->count());
    }

    /* ------------------------------- RBAC --------------------------- */

    public function test_rbac_distribution_scope(): void
    {
        $direction = User::where('role', 'direction')->firstOrFail();
        $reception = User::where('role', 'reception')->firstOrFail();
        $housekeeping = User::where('role', 'housekeeping')->firstOrFail();

        $this->actingAs($direction)->get(route('admin.distribution.index'))->assertOk();
        $this->actingAs($reception)->get(route('admin.distribution.calendar'))->assertOk();
        $this->actingAs($housekeeping)->get(route('admin.distribution.index'))->assertForbidden();
    }
}
