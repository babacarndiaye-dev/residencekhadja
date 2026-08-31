<?php

namespace Tests\Feature;

use App\Mail\PaymentReceipt;
use App\Models\EventLead;
use App\Models\EventQuote;
use App\Models\Guest;
use App\Models\Order;
use App\Models\QrLocation;
use App\Models\Reservation;
use App\Models\RoomCategory;
use App\Models\User;
use App\Services\Accounting;
use App\Services\EventQuoteBuilder;
use App\Services\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function reservation(string $status = 'pending', int $total = 200000): Reservation
    {
        return Reservation::create([
            'reference' => 'HRK-'.strtoupper(Str::random(6)),
            'hotel_id' => 1,
            'guest_id' => Guest::create(['first_name' => 'Pay', 'last_name' => 'Test', 'email' => 'pay'.random_int(1000, 9999).'@example.com'])->id,
            'room_category_id' => RoomCategory::first()->id,
            'status' => $status,
            'check_in' => Carbon::tomorrow()->toDateString(),
            'check_out' => Carbon::tomorrow()->addDays(2)->toDateString(),
            'adults' => 2, 'rooms_count' => 1,
            'total' => $total, 'deposit' => (int) round($total * 0.30),
        ]);
    }

    /* ---------------------------- Intentions ---------------------------- */

    public function test_open_is_idempotent_for_the_same_purpose(): void
    {
        $res = $this->reservation();

        $a = PaymentGateway::open($res, 'reservation_deposit');
        $b = PaymentGateway::open($res, 'reservation_deposit');

        $this->assertSame($a->id, $b->id);
        $this->assertSame($res->deposit, $a->amount);
        $this->assertSame('pending', $a->status);
    }

    public function test_capture_settles_the_reservation_and_confirms_it(): void
    {
        $res = $this->reservation('pending');
        $intent = PaymentGateway::open($res, 'reservation_deposit');

        PaymentGateway::capture($intent, 'carte', 'SIM-ABC123');

        $res->refresh();
        $this->assertSame('confirmed', $res->status);
        $this->assertSame($res->deposit, $res->paidAmount());
        $this->assertDatabaseHas('payments', ['reservation_id' => $res->id, 'type' => 'deposit', 'reference' => $intent->reference]);
        $this->assertDatabaseHas('finance_transactions', ['direction' => 'income', 'category' => 'hebergement', 'amount' => $res->deposit]);
    }

    public function test_capture_is_idempotent(): void
    {
        $res = $this->reservation();
        $intent = PaymentGateway::open($res, 'reservation_deposit');

        PaymentGateway::capture($intent, 'carte', 'SIM-DUP');
        PaymentGateway::capture($intent->fresh(), 'carte', 'SIM-DUP');   // rejeu webhook

        $this->assertSame(1, $res->payments()->count());
        $this->assertSame('paid', $intent->fresh()->status);
    }

    public function test_failed_intent_creates_no_payment(): void
    {
        $res = $this->reservation();
        $intent = PaymentGateway::open($res, 'reservation_deposit');

        PaymentGateway::fail($intent, 'Carte refusée');

        $this->assertSame('failed', $intent->fresh()->status);
        $this->assertSame(0, $res->payments()->count());
    }

    public function test_refund_reverses_a_paid_reservation_intent(): void
    {
        $res = $this->reservation();
        $intent = PaymentGateway::open($res, 'reservation_deposit');
        PaymentGateway::capture($intent, 'carte', 'SIM-REF');

        PaymentGateway::refund($intent->fresh());

        $this->assertSame('refunded', $intent->fresh()->status);
        $this->assertDatabaseHas('payments', ['reservation_id' => $res->id, 'type' => 'refund', 'amount' => -$res->deposit]);
        $this->assertSame(0, $res->paidAmount());
    }

    /* ------------------------------ Commande --------------------------- */

    public function test_order_intent_capture_marks_order_paid_and_books_revenue(): void
    {
        $location = QrLocation::first();
        $order = Order::create([
            'hotel_id' => 1, 'qr_location_id' => $location->id, 'venue_id' => $location->venue_id,
            'reference' => 'CMD-'.strtoupper(Str::random(5)),
            'status' => 'new', 'payment_status' => 'unpaid',
            'subtotal' => 12000, 'service_charge' => 0, 'total' => 12000,
            'session_token' => Str::random(20), 'idempotency_key' => Str::uuid(),
        ]);

        $intent = PaymentGateway::open($order, 'order', 12000);
        PaymentGateway::capture($intent, 'wave', 'SIM-ORD');

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertDatabaseHas('finance_transactions', ['direction' => 'income', 'category' => 'restaurant', 'amount' => 12000]);
    }

    /* ------------------------------ Événement -------------------------- */

    public function test_event_deposit_intent_capture_posts_to_accounting(): void
    {
        $lead = EventLead::create([
            'hotel_id' => 1, 'reference' => 'LEAD-'.Str::random(5),
            'contact_name' => 'Mme X', 'event_type' => 'seminaire', 'status' => 'devis', 'pax' => 80,
        ]);
        $quote = EventQuote::create(EventQuoteBuilder::defaults() + [
            'reference' => EventQuoteBuilder::nextReference(), 'title' => 'T', 'pax' => 80,
            'discount_amount' => 0, 'event_lead_id' => $lead->id,
        ]);
        $quote->items()->create(['category' => 'location', 'label' => 'Salle', 'quantity' => 1, 'unit' => 'jour', 'unit_price' => 500000, 'total' => 500000]);
        EventQuoteBuilder::recalculate($quote);
        $event = EventQuoteBuilder::accept($quote->fresh());

        $intent = PaymentGateway::open($event, 'event_deposit');
        $this->assertSame($quote->fresh()->deposit_amount, $intent->amount);

        PaymentGateway::capture($intent, 'orange_money', 'SIM-EVT');

        $this->assertTrue($event->fresh()->deposit_paid);
        $this->assertDatabaseHas('finance_transactions', ['category' => 'evenements', 'amount' => $quote->fresh()->deposit_amount]);

        $b = Accounting::trialBalance('2000-01-01', '2100-01-01');
        $this->assertSame($b->sum('debit'), $b->sum('credit'));
    }

    /* ------------------------- Pages hébergées ------------------------- */

    public function test_checkout_page_renders_then_redirects_once_paid(): void
    {
        $res = $this->reservation();
        $intent = PaymentGateway::open($res, 'reservation_deposit');

        $this->get(route('pay.checkout', $intent->reference))
            ->assertOk()
            ->assertSee($intent->reference);

        PaymentGateway::capture($intent, 'carte', 'SIM-PG');

        $this->get(route('pay.checkout', $intent->reference))
            ->assertRedirect(route('pay.receipt', $intent->reference));
    }

    public function test_process_route_captures_or_fails(): void
    {
        $res = $this->reservation();
        $intent = PaymentGateway::open($res, 'reservation_deposit');

        $this->post(route('pay.process', $intent->reference), ['method' => 'carte', 'action' => 'fail'])
            ->assertRedirect();
        $this->assertSame('failed', $intent->fresh()->status);

        $intent2 = PaymentGateway::open($res, 'reservation_deposit');
        $this->post(route('pay.process', $intent2->reference), ['method' => 'carte', 'card' => '4111111111111111'])
            ->assertRedirect(route('pay.receipt', $intent2->reference));
        $this->assertSame('paid', $intent2->fresh()->status);
    }

    public function test_capture_emails_a_receipt_to_the_payer(): void
    {
        Mail::fake();
        $intent = PaymentGateway::open($this->reservation(), 'reservation_deposit');
        $intent->update(['payer_email' => 'payer@example.com', 'payer_name' => 'Payeur Test']);

        PaymentGateway::capture($intent, 'carte', 'SIM-RCPT');

        Mail::assertQueued(PaymentReceipt::class, fn ($m) => $m->hasTo('payer@example.com'));
    }

    public function test_receipt_falls_back_to_the_reservation_guest_email(): void
    {
        Mail::fake();
        $res = $this->reservation();
        $intent = PaymentGateway::open($res, 'reservation_deposit');

        PaymentGateway::capture($intent, 'carte', 'SIM-RCPT2');

        Mail::assertQueued(PaymentReceipt::class, fn ($m) => $m->hasTo($res->guest->email));
    }

    public function test_webhook_is_idempotent(): void
    {
        $res = $this->reservation();
        $intent = PaymentGateway::open($res, 'reservation_deposit');

        $payload = ['reference' => $intent->reference, 'status' => 'paid', 'provider_ref' => 'PD-777'];
        $this->postJson('/paiement/webhook/paydunya', $payload)->assertOk();
        $this->postJson('/paiement/webhook/paydunya', $payload)->assertOk();

        $this->assertSame('paid', $intent->fresh()->status);
        $this->assertSame(1, $res->payments()->count());
    }

    public function test_webhook_rejects_a_bad_signature_when_a_secret_is_configured(): void
    {
        config(['payments.providers.paydunya.webhook_secret' => 'shh-secret']);
        $intent = PaymentGateway::open($this->reservation(), 'reservation_deposit');

        $this->postJson('/paiement/webhook/paydunya',
            ['reference' => $intent->reference, 'status' => 'paid'],
            ['X-Signature' => 'forged'],
        )->assertStatus(401)->assertJsonPath('reason', 'bad_signature');

        $this->assertSame('pending', $intent->fresh()->status);
    }

    public function test_webhook_accepts_a_valid_signature(): void
    {
        config(['payments.providers.paydunya.webhook_secret' => 'shh-secret']);
        $intent = PaymentGateway::open($this->reservation(), 'reservation_deposit');

        $payload = ['reference' => $intent->reference, 'status' => 'paid', 'provider_ref' => 'PD-SIG'];
        $signature = hash_hmac('sha256', json_encode($payload), 'shh-secret');

        $this->postJson('/paiement/webhook/paydunya', $payload, ['X-Signature' => $signature])->assertOk();

        $this->assertSame('paid', $intent->fresh()->status);
    }

    public function test_simulator_decline_card_is_rejected(): void
    {
        $res = $this->reservation();
        $intent = PaymentGateway::open($res, 'reservation_deposit');

        $this->post(route('pay.process', $intent->reference), [
            'method' => 'carte',
            'card' => config('payments.simulator_decline_trigger'),
        ])->assertSessionHasErrors('card');

        $this->assertSame('failed', $intent->fresh()->status);
    }

    /* --------------------------------- RBAC --------------------------- */

    public function test_rbac_payments_admin(): void
    {
        $finance = User::where('role', 'finance')->firstOrFail();
        $housekeeping = User::where('role', 'housekeeping')->firstOrFail();

        $this->actingAs($finance)->get(route('admin.payments.index'))->assertOk();
        $this->actingAs($housekeeping)->get(route('admin.payments.index'))->assertForbidden();
    }

    public function test_default_amount_for_balance(): void
    {
        $res = $this->reservation('confirmed', 300000);
        $this->assertSame($res->balance(), PaymentGateway::defaultAmount($res, 'reservation_balance'));
    }
}
