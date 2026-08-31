<?php

namespace Tests\Feature;

use App\Jobs\SendSms;
use App\Models\Guest;
use App\Models\GuestRequest;
use App\Models\Reservation;
use App\Models\RoomCategory;
use App\Models\User;
use App\Services\Sms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class SmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Cache::flush();
    }

    /* ----------------------------- Normalisation ----------------------------- */

    public function test_numbers_are_normalised_to_e164(): void
    {
        $this->assertSame('+221771112233', Sms::e164('77 111 22 33'));
        $this->assertSame('+221771112233', Sms::e164('0771112233'));
        $this->assertSame('+221771112233', Sms::e164('00221771112233'));
        $this->assertSame('+221771112233', Sms::e164('+221 77 111 22 33'));
        $this->assertSame('+33612345678', Sms::e164('+33 6 12 34 56 78'));
        $this->assertSame('', Sms::e164('bonjour'));
        $this->assertSame('', Sms::e164('123'));
    }

    /* -------------------------------- Drivers ------------------------------- */

    public function test_log_driver_sends_nothing_and_returns_true(): void
    {
        config(['sms.driver' => 'log']);
        Http::fake();

        $this->assertTrue(Sms::send('+221771112233', 'Bonjour'));
        Http::assertNothingSent();
    }

    public function test_http_driver_posts_to_the_gateway_with_a_bearer_token(): void
    {
        config([
            'sms.driver' => 'http',
            'sms.from' => 'KHADIJA',
            'sms.http.endpoint' => 'https://gateway.test/send',
            'sms.http.token' => 'secret-token',
        ]);
        Http::fake(['gateway.test/*' => Http::response(['ok' => true], 200)]);

        $this->assertTrue(Sms::send('+221771112233', 'Bonjour'));

        Http::assertSent(fn ($req) => $req->url() === 'https://gateway.test/send'
            && $req->hasHeader('Authorization', 'Bearer secret-token')
            && $req['to'] === '+221771112233'
            && $req['from'] === 'KHADIJA'
            && $req['message'] === 'Bonjour');
    }

    public function test_twilio_driver_uses_basic_auth_and_the_messages_endpoint(): void
    {
        config([
            'sms.driver' => 'twilio',
            'sms.twilio.sid' => 'AC_TEST',
            'sms.twilio.token' => 'tw-token',
            'sms.twilio.from' => '+15550001111',
        ]);
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SMxxx'], 201)]);

        $this->assertTrue(Sms::send('77 111 22 33', 'Bonjour'));

        Http::assertSent(fn ($req) => str_contains($req->url(), '/Accounts/AC_TEST/Messages.json')
            && $req->hasHeader('Authorization')
            && $req['To'] === '+221771112233'
            && $req['From'] === '+15550001111'
            && $req['Body'] === 'Bonjour');
    }

    public function test_orange_driver_fetches_a_token_then_posts_the_message(): void
    {
        config([
            'sms.driver' => 'orange',
            'sms.orange.client_id' => 'cid',
            'sms.orange.client_secret' => 'csecret',
            'sms.orange.sender' => '+221770000000',
            'sms.orange.token_url' => 'https://api.orange.com/oauth/v3/token',
            'sms.orange.base_url' => 'https://api.orange.com/smsmessaging/v1/outbound',
        ]);
        Http::fake([
            'api.orange.com/oauth/*' => Http::response(['access_token' => 'tok-123', 'expires_in' => 3600]),
            'api.orange.com/smsmessaging/*' => Http::response('', 201),
        ]);

        $this->assertTrue(Sms::send('+221771112233', 'Bonjour'));

        Http::assertSent(fn ($req) => str_contains($req->url(), '/oauth/v3/token'));
        Http::assertSent(fn ($req) => str_contains($req->url(), '/smsmessaging/v1/outbound/tel:')
            && $req['outboundSMSMessageRequest']['address'] === 'tel:+221771112233');
    }

    public function test_send_swallows_a_gateway_exception(): void
    {
        config([
            'sms.driver' => 'http',
            'sms.http.endpoint' => 'https://gateway.test/send',
        ]);
        Http::fake(fn () => throw new \RuntimeException('gateway down'));

        $this->assertFalse(Sms::send('+221771112233', 'Bonjour'));
    }

    /* --------------------------- Modèles / file ---------------------------- */

    public function test_queue_template_pushes_a_job_when_enabled(): void
    {
        Queue::fake();
        config(['sms.transactional' => true]);

        Sms::queueTemplate('77 111 22 33', 'reservation_confirmed', ['ref' => 'HRK-ABC123', 'in' => '30/08/2026']);

        Queue::assertPushed(SendSms::class, fn (SendSms $job) => $job->to === '+221771112233'
            && str_contains($job->message, 'HRK-ABC123')
            && str_contains($job->message, '30/08/2026'));
    }

    public function test_queue_template_is_skipped_without_a_phone_or_when_disabled(): void
    {
        Queue::fake();

        Sms::queueTemplate(null, 'reservation_confirmed', ['ref' => 'X', 'in' => 'Y']);
        Sms::queueTemplate('   ', 'reservation_confirmed', ['ref' => 'X', 'in' => 'Y']);
        Queue::assertNothingPushed();

        config(['sms.transactional' => false]);
        Sms::queueTemplate('+221771112233', 'reservation_confirmed', ['ref' => 'X', 'in' => 'Y']);
        Queue::assertNothingPushed();
    }

    /* ----------------------- Câblage transactionnel ----------------------- */

    private function pendingReservation(?string $phone): Reservation
    {
        $cat = RoomCategory::first();
        $guest = Guest::create([
            'civility' => 'M.', 'first_name' => 'Test', 'last_name' => 'Client'.random_int(10, 99),
            'email' => null, 'phone' => $phone,
        ]);

        return Reservation::create([
            'reference' => 'HRK-'.Str::upper(Str::random(6)),
            'hotel_id' => $cat->hotel_id, 'guest_id' => $guest->id, 'room_category_id' => $cat->id,
            'status' => 'pending', 'channel' => 'direct',
            'check_in' => Carbon::tomorrow()->toDateString(),
            'check_out' => Carbon::tomorrow()->addDays(2)->toDateString(),
            'adults' => 1, 'rooms_count' => 1, 'total' => 120000,
        ]);
    }

    public function test_confirming_a_reservation_queues_a_guest_sms(): void
    {
        Queue::fake();
        $reservation = $this->pendingReservation('+221 77 555 44 33');

        $this->actingAs(User::where('role', 'reception')->firstOrFail())
            ->post(route('admin.reservations.confirm', $reservation))
            ->assertRedirect();

        Queue::assertPushed(SendSms::class, fn (SendSms $job) => $job->to === '+221775554433'
            && str_contains($job->message, $reservation->reference));
    }

    public function test_no_sms_is_queued_when_the_guest_has_no_phone(): void
    {
        Queue::fake();
        $reservation = $this->pendingReservation(null);

        $this->actingAs(User::where('role', 'reception')->firstOrFail())
            ->post(route('admin.reservations.confirm', $reservation))
            ->assertRedirect();

        Queue::assertNotPushed(SendSms::class);
    }

    public function test_resolving_a_guest_request_queues_a_status_sms(): void
    {
        Queue::fake();
        $reservation = $this->pendingReservation('+221 77 222 11 00');
        $gr = GuestRequest::create([
            'hotel_id' => $reservation->hotel_id, 'reservation_id' => $reservation->id,
            'type' => 'linge', 'routed_to' => 'housekeeping', 'status' => 'open',
        ]);

        $this->actingAs(User::where('role', 'housekeeping')->firstOrFail())
            ->post(route('admin.guest_requests.resolve', $gr))
            ->assertRedirect();

        Queue::assertPushed(SendSms::class, fn (SendSms $job) => $job->to === '+221772221100'
            && str_contains($job->message, 'traitee'));
    }
}
