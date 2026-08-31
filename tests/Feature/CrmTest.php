<?php

namespace Tests\Feature;

use App\Mail\CampaignMessage;
use App\Models\Guest;
use App\Models\LoyaltyTransaction;
use App\Models\MarketingCampaign;
use App\Models\MarketingSegment;
use App\Models\PromoCode;
use App\Models\Reservation;
use App\Models\RoomCategory;
use App\Models\User;
use App\Services\BookingQuote;
use App\Services\CampaignDispatcher;
use App\Services\LoyaltyProgram;
use App\Services\Segmentation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CrmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function guest(array $attrs = []): Guest
    {
        return Guest::create(array_merge([
            'first_name' => 'Test',
            'last_name' => 'Client'.random_int(1000, 9999),
            'email' => 'c'.random_int(10000, 99999).'@example.com',
            'country' => 'Sénégal',
        ], $attrs));
    }

    private function stay(Guest $guest, string $status, int $total, ?Carbon $out = null): Reservation
    {
        return Reservation::create([
            'reference' => 'HRK-'.strtoupper(Str::random(6)),
            'hotel_id' => 1,
            'guest_id' => $guest->id,
            'room_category_id' => RoomCategory::first()->id,
            'status' => $status,
            'check_in' => ($out ?? Carbon::now())->copy()->subDays(2)->toDateString(),
            'check_out' => ($out ?? Carbon::now())->toDateString(),
            'adults' => 1, 'rooms_count' => 1, 'total' => $total,
        ]);
    }

    /* ---------------------------- Fidélité ---------------------------- */

    public function test_payment_credits_loyalty_points_for_enrolled_guest(): void
    {
        $guest = $this->guest();
        $account = LoyaltyProgram::enroll($guest);
        $reservation = $this->stay($guest, 'checked_in', 400000);

        $reservation->payments()->create([
            'amount' => 400000, 'method' => 'carte', 'type' => 'balance', 'received_at' => now(),
        ]);

        // 1 point / 1 000 FCFA × taux palier Découverte (1.0) = 400 pts.
        $account->refresh();
        $this->assertSame(400, $account->points_balance);
        $this->assertSame(400, $account->lifetime_points);
        $this->assertDatabaseHas('loyalty_transactions', [
            'loyalty_account_id' => $account->id, 'type' => 'earn', 'points' => 400,
        ]);
    }

    public function test_no_points_when_guest_not_enrolled(): void
    {
        $before = LoyaltyTransaction::count();

        $guest = $this->guest();
        $reservation = $this->stay($guest, 'checked_in', 400000);
        $reservation->payments()->create([
            'amount' => 400000, 'method' => 'carte', 'type' => 'balance', 'received_at' => now(),
        ]);

        $this->assertNull($guest->fresh()->loyaltyAccount);
        $this->assertSame($before, LoyaltyTransaction::count());
    }

    public function test_earning_enough_points_upgrades_tier(): void
    {
        $account = LoyaltyProgram::enroll($this->guest());
        $this->assertSame('DECOUVERTE', $account->tier->code);

        LoyaltyProgram::earn($account, 3_000_000, 'Gros séjour'); // 3 000 pts

        $account->refresh();
        $this->assertSame(3000, $account->lifetime_points);
        $this->assertSame('PRIVILEGE', $account->tier->code);
    }

    public function test_redeem_reduces_balance_and_rejects_overdraw(): void
    {
        $account = LoyaltyProgram::enroll($this->guest());
        LoyaltyProgram::adjust($account, 1000, 'Bonus test');

        $tx = LoyaltyProgram::redeem($account, 600, 'Remise réception');
        $this->assertSame(-600, $tx->points);
        $this->assertSame(400, $account->fresh()->points_balance);

        $this->expectException(ValidationException::class);
        LoyaltyProgram::redeem($account, 5000, 'Trop');
    }

    public function test_redeem_below_minimum_is_rejected(): void
    {
        $account = LoyaltyProgram::enroll($this->guest());
        LoyaltyProgram::adjust($account, 1000, 'Bonus');

        $this->expectException(ValidationException::class);
        LoyaltyProgram::redeem($account, 100, 'Sous le minimum');
    }

    /* --------------------------- Segmentation ------------------------ */

    public function test_segmentation_rules_resolve_expected_guests(): void
    {
        $loyal = $this->guest(['marketing_opt_in' => true]);
        $this->stay($loyal, 'checked_out', 200000);
        $this->stay($loyal, 'checked_out', 200000);

        $newbie = $this->guest(['marketing_opt_in' => true]);

        $birthday = $this->guest([
            'marketing_opt_in' => true,
            'birthdate' => Carbon::now()->subYears(35)->startOfMonth()->addDays(3)->toDateString(),
        ]);

        $twoStays = Segmentation::query(['min_stays' => 2])->pluck('id');
        $this->assertTrue($twoStays->contains($loyal->id));
        $this->assertFalse($twoStays->contains($newbie->id));

        $never = Segmentation::query(['never_stayed' => true])->pluck('id');
        $this->assertTrue($never->contains($newbie->id));
        $this->assertFalse($never->contains($loyal->id));

        $bdays = Segmentation::query(['birthday_month' => 'current'])->pluck('id');
        $this->assertTrue($bdays->contains($birthday->id));
    }

    /* ---------------------------- Campagnes ------------------------- */

    public function test_campaign_build_and_send_respects_consent_and_address(): void
    {
        Mail::fake();
        Guest::query()->update(['marketing_opt_in' => false]);

        $ok = $this->guest(['marketing_opt_in' => true, 'email' => 'reachable@example.com']);
        $noAddress = $this->guest(['marketing_opt_in' => true, 'email' => null]);
        $this->guest(['marketing_opt_in' => false, 'email' => 'nope@example.com']);

        $segment = MarketingSegment::create([
            'hotel_id' => 1, 'name' => 'Test opt-in', 'definition' => ['opted_in' => true],
        ]);

        $campaign = MarketingCampaign::create([
            'hotel_id' => 1, 'name' => 'Test', 'channel' => 'email',
            'segment_id' => $segment->id, 'subject' => 'Coucou', 'body' => 'Bonjour {prenom}', 'status' => 'draft',
        ]);

        $queued = CampaignDispatcher::build($campaign);
        $this->assertSame(1, $queued);
        $this->assertSame(2, $campaign->recipients()->count());
        $this->assertDatabaseHas('campaign_recipients', ['guest_id' => $noAddress->id, 'status' => 'skipped']);

        // Rebuild : pas de doublon (contrainte unique campaign+guest).
        CampaignDispatcher::build($campaign);
        $this->assertSame(2, $campaign->recipients()->count());

        $campaign = CampaignDispatcher::send($campaign);
        $this->assertSame('sent', $campaign->status);
        $this->assertNotNull($campaign->sent_at);
        $this->assertSame(1, $campaign->stats['sent']);
        $this->assertDatabaseHas('campaign_recipients', ['guest_id' => $ok->id, 'status' => 'sent']);

        // L'unique destinataire joignable reçoit réellement un e-mail (Mailable en file).
        Mail::assertQueued(CampaignMessage::class, 1);
        Mail::assertQueued(CampaignMessage::class, fn ($m) => $m->hasTo('reachable@example.com'));
    }

    public function test_render_replaces_tokens(): void
    {
        $guest = $this->guest(['first_name' => 'Awa']);
        $promo = PromoCode::create(['hotel_id' => 1, 'code' => 'HELLO', 'type' => 'percent', 'value' => 10, 'label' => 'x']);
        $campaign = MarketingCampaign::create([
            'hotel_id' => 1, 'name' => 'T', 'channel' => 'sms', 'promo_code_id' => $promo->id,
            'body' => '{prenom}, code {code}', 'status' => 'draft',
        ]);

        $this->assertSame('Awa, code HELLO', CampaignDispatcher::render($campaign, $guest));
    }

    /* --------------------------- Codes promo ----------------------- */

    public function test_database_promo_code_applies_in_booking_quote(): void
    {
        PromoCode::create([
            'hotel_id' => 1, 'code' => 'CAMPAIGN20', 'type' => 'percent', 'value' => 20,
            'label' => 'Campagne -20 %', 'active' => true,
        ]);

        $quote = BookingQuote::for([
            'room_slug' => RoomCategory::first()->slug,
            'check_in' => Carbon::today()->toDateString(),
            'check_out' => Carbon::today()->addDays(2)->toDateString(),
            'rate_plan' => 'flexible',
            'adults' => 2,
            'promo' => 'campaign20',
        ]);

        $this->assertGreaterThan(0, $quote['discount_amount']);
        $this->assertSame('Campagne -20 %', $quote['discount']['label']);
    }

    public function test_inactive_promo_code_is_ignored(): void
    {
        PromoCode::create([
            'hotel_id' => 1, 'code' => 'OFFOFF', 'type' => 'percent', 'value' => 50,
            'label' => 'Inactif', 'active' => false,
        ]);

        $quote = BookingQuote::for([
            'room_slug' => RoomCategory::first()->slug,
            'check_in' => Carbon::today()->toDateString(),
            'check_out' => Carbon::today()->addDays(2)->toDateString(),
            'rate_plan' => 'flexible',
            'promo' => 'OFFOFF',
        ]);

        $this->assertSame(0, $quote['discount_amount']);
    }

    /* ------------------------------ RBAC --------------------------- */

    public function test_rbac_marketing_scope(): void
    {
        $marketing = User::where('role', 'marketing')->firstOrFail();
        $reception = User::where('role', 'reception')->firstOrFail();
        $housekeeping = User::where('role', 'housekeeping')->firstOrFail();

        $this->actingAs($marketing)->get(route('admin.marketing.index'))->assertOk();
        $this->actingAs($marketing)->get(route('admin.crm.dashboard'))->assertOk();

        // La réception gère le CRM mais pas les campagnes.
        $this->actingAs($reception)->get(route('admin.crm.dashboard'))->assertOk();
        $this->actingAs($reception)->get(route('admin.marketing.index'))->assertForbidden();

        // Le housekeeping n'a pas accès au CRM.
        $this->actingAs($housekeeping)->get(route('admin.crm.dashboard'))->assertForbidden();
    }

    public function test_enroll_and_consent_endpoints(): void
    {
        $reception = User::where('role', 'reception')->firstOrFail();
        $guest = $this->guest();

        $this->actingAs($reception)->post(route('admin.guests.enroll', $guest))->assertRedirect();
        $this->assertNotNull($guest->fresh()->loyaltyAccount);

        $this->actingAs($reception)
            ->post(route('admin.guests.consent', $guest), ['marketing_opt_in' => 1])
            ->assertRedirect();
        $this->assertTrue($guest->fresh()->marketing_opt_in);
        $this->assertNotNull($guest->fresh()->consent_updated_at);
    }
}
