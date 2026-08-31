<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\RoomCategory;
use App\Services\PaymentGateway;
use App\Services\Payments\CinetPayDriver;
use App\Services\Payments\PayDunyaDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentDriversTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function reservation(): Reservation
    {
        return Reservation::create([
            'reference' => 'HRK-'.strtoupper(Str::random(6)),
            'hotel_id' => 1,
            'guest_id' => Guest::create(['first_name' => 'Drv', 'last_name' => 'Test', 'email' => 'drv'.random_int(1000, 9999).'@example.com'])->id,
            'room_category_id' => RoomCategory::first()->id,
            'status' => 'pending',
            'check_in' => Carbon::tomorrow()->toDateString(),
            'check_out' => Carbon::tomorrow()->addDays(2)->toDateString(),
            'adults' => 2, 'rooms_count' => 1,
            'total' => 200000, 'deposit' => 60000,
        ]);
    }

    public function test_paydunya_start_redirects_to_the_invoice_url_and_stores_the_token(): void
    {
        config(['payments.driver' => 'paydunya', 'payments.providers.paydunya.private_key' => 'priv-key']);
        Http::fake([
            '*/checkout-invoice/create' => Http::response([
                'response_code' => '00',
                'response_text' => 'https://paydunya.com/checkout/invoice/TOKEN123',
                'token' => 'TOKEN123',
            ]),
        ]);

        $intent = PaymentGateway::open($this->reservation(), 'reservation_deposit');

        $this->assertSame('paydunya', $intent->provider);
        $this->assertSame('TOKEN123', $intent->provider_ref);
        $this->assertSame('https://paydunya.com/checkout/invoice/TOKEN123', PaymentGateway::checkoutUrl($intent));

        Http::assertSent(fn ($req) => $req->hasHeader('PAYDUNYA-PRIVATE-KEY', 'priv-key')
            && $req['invoice']['total_amount'] === 60000
            && $req['custom_data']['intent'] === $intent->reference);
    }

    public function test_open_fails_cleanly_when_the_provider_api_is_down(): void
    {
        config(['payments.driver' => 'paydunya']);
        Http::fake(['*' => Http::response(['response_code' => '1001', 'response_text' => 'boom'], 200)]);

        try {
            PaymentGateway::open($this->reservation(), 'reservation_deposit');
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('payment', $e->errors());
        }

        $this->assertDatabaseHas('payment_intents', ['provider' => 'paydunya', 'status' => 'failed']);
    }

    public function test_paydunya_native_webhook_captures_with_a_valid_hash(): void
    {
        config(['payments.driver' => 'paydunya', 'payments.providers.paydunya.private_key' => 'priv-key']);
        Http::fake(['*/checkout-invoice/create' => Http::response(['response_code' => '00', 'response_text' => 'https://x', 'token' => 'TK1'])]);

        $res = $this->reservation();
        $intent = PaymentGateway::open($res, 'reservation_deposit');

        $good = hash('sha512', 'priv-key');
        $this->postJson('/paiement/webhook/paydunya', [
            'data' => [
                'status' => 'completed',
                'hash' => $good,
                'custom_data' => ['intent' => $intent->reference],
                'invoice' => ['token' => 'TK1'],
            ],
        ])->assertOk();

        $this->assertSame('paid', $intent->fresh()->status);
        $this->assertSame('confirmed', $res->fresh()->status);

        // Hash faux → 401, aucune capture.
        $intent2 = PaymentGateway::open($this->reservation(), 'reservation_deposit');
        $this->postJson('/paiement/webhook/paydunya', [
            'data' => ['status' => 'completed', 'hash' => 'nope', 'custom_data' => ['intent' => $intent2->reference]],
        ])->assertStatus(401)->assertJsonPath('reason', 'bad_signature');
        $this->assertSame('pending', $intent2->fresh()->status);
    }

    public function test_cinetpay_start_and_verify(): void
    {
        config(['payments.driver' => 'cinetpay']);
        Http::fake([
            '*/v2/payment' => Http::response(['code' => '201', 'data' => [
                'payment_token' => 'CPT-9', 'payment_url' => 'https://checkout.cinetpay.com/CPT-9',
            ]]),
            '*/v2/payment/check' => Http::response(['code' => '00', 'data' => ['status' => 'ACCEPTED']]),
        ]);

        $res = $this->reservation();
        $intent = PaymentGateway::open($res, 'reservation_deposit');
        $this->assertSame('https://checkout.cinetpay.com/CPT-9', $intent->meta['checkout_url']);

        PaymentGateway::reconcile($intent->fresh());

        $this->assertSame('paid', $intent->fresh()->status);
        $this->assertSame('confirmed', $res->fresh()->status);
    }

    public function test_reconcile_marks_a_refused_cinetpay_intent_failed(): void
    {
        config(['payments.driver' => 'cinetpay']);
        Http::fake([
            '*/v2/payment' => Http::response(['code' => '201', 'data' => ['payment_token' => 'X', 'payment_url' => 'https://x']]),
            '*/v2/payment/check' => Http::response(['code' => '600', 'data' => ['status' => 'REFUSED']]),
        ]);

        $intent = PaymentGateway::open($this->reservation(), 'reservation_deposit');
        PaymentGateway::reconcile($intent->fresh());

        $this->assertSame('failed', $intent->fresh()->status);
    }

    public function test_simulator_flow_is_unchanged_by_the_driver_abstraction(): void
    {
        // Driver par défaut = simulator : aucune requête HTTP, page hébergée locale.
        Http::fake();
        $intent = PaymentGateway::open($this->reservation(), 'reservation_deposit');

        $this->assertSame('simulator', $intent->provider);
        $this->assertStringContainsString('/paiement/'.$intent->reference, PaymentGateway::checkoutUrl($intent));
        Http::assertNothingSent();
    }

    public function test_driver_factory_resolves_each_provider(): void
    {
        $this->assertInstanceOf(PayDunyaDriver::class, PaymentGateway::driver('paydunya'));
        $this->assertInstanceOf(CinetPayDriver::class, PaymentGateway::driver('cinetpay'));
    }
}
