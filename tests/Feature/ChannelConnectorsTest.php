<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\ChannelReservation;
use App\Models\ChannelSyncLog;
use App\Models\Reservation;
use App\Models\RoomCategory;
use App\Models\User;
use App\Services\ChannelManager;
use App\Services\Distribution\IcalConnector;
use App\Services\Distribution\SimulatorConnector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChannelConnectorsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private const ICAL_URL = 'https://calendar.example/listing/abc.ics';

    private function icalChannel(): Channel
    {
        $cat = RoomCategory::active()->orderBy('sort_order')->first();
        $channel = Channel::where('key', 'airbnb')->firstOrFail();
        $channel->update([
            'connector' => 'ical',
            'credentials' => ['ical_url' => self::ICAL_URL, 'room_slug' => $cat->slug],
        ]);

        return $channel->fresh();
    }

    private function ics(): string
    {
        $future = Carbon::today()->addDays(90);
        $futureEnd = Carbon::today()->addDays(93);
        $past = Carbon::today()->subDays(30);
        $pastEnd = Carbon::today()->subDays(28);

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Airbnb//Hosting Calendar//EN',
            'BEGIN:VEVENT',
            'DTSTART;VALUE=DATE:'.$future->format('Ymd'),
            'DTEND;VALUE=DATE:'.$futureEnd->format('Ymd'),
            'UID:evt-future-001@airbnb.com',
            'SUMMARY:Airbnb (Not available) — long summary that should be trun',
            ' cated safely across folded lines',
            'END:VEVENT',
            'BEGIN:VEVENT',
            'DTSTART;VALUE=DATE:'.$past->format('Ymd'),
            'DTEND;VALUE=DATE:'.$pastEnd->format('Ymd'),
            'UID:evt-past-002@airbnb.com',
            'SUMMARY:Reserved',
            'END:VEVENT',
            'BEGIN:VEVENT',
            'DTSTART;VALUE=DATE:'.Carbon::today()->addDays(120)->format('Ymd'),
            'DTEND;VALUE=DATE:'.Carbon::today()->addDays(122)->format('Ymd'),
            'UID:evt-cancelled-003@airbnb.com',
            'STATUS:CANCELLED',
            'SUMMARY:Cancelled block',
            'END:VEVENT',
            'END:VCALENDAR',
        ]);
    }

    public function test_connector_factory_resolves_each_type(): void
    {
        $this->assertInstanceOf(IcalConnector::class, ChannelManager::connector('ical'));
        $this->assertInstanceOf(SimulatorConnector::class, ChannelManager::connector('simulator'));
        $this->assertInstanceOf(SimulatorConnector::class, ChannelManager::connector(Channel::where('key', 'booking_com')->firstOrFail()));
    }

    public function test_simulator_push_still_logs_per_channel(): void
    {
        $before = ChannelSyncLog::where('action', 'push_rates')->count();
        $n = ChannelManager::pushRates(Carbon::today(), Carbon::today()->addDays(2));

        $this->assertGreaterThan(0, $n);
        $this->assertSame($before + $n, ChannelSyncLog::where('action', 'push_rates')->count());
    }

    public function test_ical_pull_imports_future_blocks_and_holds_inventory(): void
    {
        Http::fake([self::ICAL_URL => Http::response($this->ics())]);
        $channel = $this->icalChannel();
        $before = ChannelReservation::where('channel_id', $channel->id)->count();

        $handled = ChannelManager::pullReservations($channel);

        $this->assertSame(1, $handled);

        $cr = ChannelReservation::where('external_ref', 'airbnb:evt-future-001@airbnb.com')->first();
        $this->assertNotNull($cr);
        $this->assertSame('imported', $cr->status);
        $this->assertSame(0, $cr->gross_amount);
        $this->assertSame(0, $cr->commission_amount);

        $stay = $cr->reservation;
        $this->assertSame('confirmed', $stay->status);
        $this->assertSame('airbnb', $stay->channel);
        $this->assertSame(Carbon::today()->addDays(90)->toDateString(), $stay->check_in->toDateString());

        // Passé + annulé ignorés → une seule ligne importée.
        $this->assertSame($before + 1, ChannelReservation::where('channel_id', $channel->id)->count());
        $this->assertDatabaseHas('channel_sync_logs', ['channel_id' => $channel->id, 'action' => 'pull_reservation', 'status' => 'ok']);

        // Rejeu : idempotent.
        ChannelManager::pullReservations($channel);
        $this->assertSame($before + 1, ChannelReservation::where('channel_id', $channel->id)->count());
    }

    public function test_ical_test_connection(): void
    {
        Http::fake([self::ICAL_URL => Http::response($this->ics())]);
        $channel = $this->icalChannel();

        $ok = ChannelManager::connector($channel)->testConnection($channel);
        $this->assertTrue($ok['ok']);

        $channel->update(['credentials' => ['room_slug' => 'x']]); // pas d'URL
        $ko = ChannelManager::connector($channel)->testConnection($channel->fresh());
        $this->assertFalse($ko['ok']);
    }

    public function test_pull_command_runs(): void
    {
        Http::fake([self::ICAL_URL => Http::response($this->ics())]);
        $this->icalChannel();

        $this->artisan('channels:pull')->assertSuccessful();
        $this->assertTrue(Reservation::where('channel', 'airbnb')->exists());
    }

    public function test_admin_can_test_and_pull_a_channel(): void
    {
        Http::fake([self::ICAL_URL => Http::response($this->ics())]);
        $channel = $this->icalChannel();
        $admin = User::where('role', 'direction')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.distribution.channels.test', $channel))
            ->assertRedirect();
        $this->actingAs($admin)->post(route('admin.distribution.channels.pull', $channel))
            ->assertRedirect();

        $this->assertDatabaseHas('channel_reservations', ['external_ref' => 'airbnb:evt-future-001@airbnb.com']);
    }
}
