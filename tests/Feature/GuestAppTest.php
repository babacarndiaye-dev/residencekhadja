<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\GuestDevice;
use App\Models\Reservation;
use App\Models\RoomCategory;
use App\Models\User;
use App\Services\GuestApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class GuestAppTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function stay(string $status = 'checked_in', string $lastName = 'Diallo', int $total = 150000): Reservation
    {
        return Reservation::create([
            'reference' => 'HRK-'.strtoupper(Str::random(6)),
            'hotel_id' => 1,
            'guest_id' => Guest::create(['first_name' => 'Awa', 'last_name' => $lastName, 'email' => 'g'.random_int(1000, 9999).'@example.com'])->id,
            'room_category_id' => RoomCategory::first()->id,
            'status' => $status,
            'check_in' => Carbon::yesterday()->toDateString(),
            'check_out' => Carbon::tomorrow()->toDateString(),
            'adults' => 2, 'rooms_count' => 1, 'total' => $total,
        ]);
    }

    private function asGuest(Reservation $stay): self
    {
        $device = GuestDevice::create([
            'reservation_id' => $stay->id,
            'token' => Str::random(48),
            'expires_at' => now()->addWeek(),
        ]);

        return $this->withUnencryptedCookie(GuestApp::COOKIE, $device->token);
    }

    /* ------------------------------ Accès ------------------------------ */

    public function test_login_with_reference_and_name(): void
    {
        $stay = $this->stay(lastName: 'Ndiaye');

        $this->post(route('guest.login.submit'), ['reference' => strtolower($stay->reference), 'last_name' => 'NDIAYE '])
            ->assertRedirect(route('guest.home'))
            ->assertCookie(GuestApp::COOKIE);

        $this->assertDatabaseHas('guest_devices', ['reservation_id' => $stay->id]);
    }

    public function test_login_rejects_wrong_name(): void
    {
        $stay = $this->stay(lastName: 'Ndiaye');

        $this->post(route('guest.login.submit'), ['reference' => $stay->reference, 'last_name' => 'Autre'])
            ->assertSessionHasErrors('reference');

        $this->assertDatabaseCount('guest_devices', 0);
    }

    public function test_checked_out_stay_cannot_log_in(): void
    {
        $stay = $this->stay('checked_out', 'Sow');

        $this->post(route('guest.login.submit'), ['reference' => $stay->reference, 'last_name' => 'Sow'])
            ->assertSessionHasErrors('reference');
    }

    public function test_app_requires_a_valid_token(): void
    {
        $this->get(route('guest.home'))->assertRedirect(route('guest.login'));

        $this->asGuest($this->stay())->get(route('guest.home'))->assertOk()->assertSee('Bienvenue');
    }

    public function test_magic_link_grants_access_and_must_be_signed(): void
    {
        $stay = $this->stay();

        $this->get(URL::signedRoute('guest.magic', ['reference' => $stay->reference], null, false))
            ->assertRedirect(route('guest.home'))
            ->assertCookie(GuestApp::COOKIE);

        $this->get(route('guest.magic', ['reference' => $stay->reference]))->assertForbidden();
    }

    /* ------------------------------ Folio ----------------------------- */

    public function test_guest_can_open_a_balance_payment(): void
    {
        $stay = $this->stay(total: 200000);

        $this->asGuest($stay)->get(route('guest.stay'))->assertOk()->assertSee(money($stay->balance()));

        $res = $this->asGuest($stay)->post(route('guest.pay'));
        $res->assertRedirect();
        $this->assertStringContainsString('/paiement/', $res->headers->get('Location'));

        $this->assertDatabaseHas('payment_intents', [
            'purpose' => 'reservation_balance', 'payable_type' => (new Reservation)->getMorphClass(), 'payable_id' => $stay->id,
        ]);
    }

    /* ---------------------------- Demandes --------------------------- */

    public function test_request_is_created_and_routed(): void
    {
        $stay = $this->stay();

        $this->asGuest($stay)->post(route('guest.requests.store'), ['type' => 'depannage', 'note' => 'Fuite'])
            ->assertRedirect(route('guest.requests'));
        $this->assertDatabaseHas('guest_requests', ['reservation_id' => $stay->id, 'type' => 'depannage', 'routed_to' => 'maintenance']);

        $this->asGuest($stay)->post(route('guest.requests.store'), ['type' => 'menage'])
            ->assertRedirect();
        $this->assertDatabaseHas('guest_requests', ['reservation_id' => $stay->id, 'type' => 'menage', 'routed_to' => 'housekeeping']);

        $this->asGuest($stay)->post(route('guest.requests.store'), ['type' => 'transport'])->assertRedirect();
        $this->assertDatabaseHas('guest_requests', ['reservation_id' => $stay->id, 'type' => 'transport', 'routed_to' => 'reception']);

        $this->asGuest($stay)->get(route('guest.requests'))->assertOk()->assertSee('Fuite');
    }

    public function test_paid_service_request_charges_the_folio(): void
    {
        $stay = $this->stay(total: 100000);
        $before = $stay->balance();

        $this->asGuest($stay)->post(route('guest.requests.service'), [
            'slug' => 'plateau_repas', 'quantity' => 2, 'note' => 'Sans arachide',
        ])->assertRedirect(route('guest.requests'));

        $req = $stay->guestRequests()->firstWhere('service_slug', 'plateau_repas');
        $this->assertNotNull($req->charge_id);
        $this->assertSame(18000, $req->chargeAmount());               // 9000 × 2
        $this->assertSame('reception', $req->routed_to);
        $this->assertDatabaseHas('reservation_charges', ['id' => $req->charge_id, 'amount' => 18000]);
        $this->assertSame($before + 18000, $stay->fresh()->balance());
    }

    public function test_free_service_request_creates_no_charge(): void
    {
        $stay = $this->stay();

        $this->asGuest($stay)->post(route('guest.requests.service'), ['slug' => 'menage_chambre'])->assertRedirect();

        $req = $stay->guestRequests()->firstWhere('service_slug', 'menage_chambre');
        $this->assertNull($req->charge_id);
        $this->assertSame('housekeeping', $req->routed_to);
        $this->assertSame($stay->total, $stay->fresh()->balance());
    }

    public function test_cancelling_open_paid_service_reverses_the_charge(): void
    {
        $stay = $this->stay(total: 80000);

        $this->asGuest($stay)->post(route('guest.requests.service'), ['slug' => 'transfert_aeroport'])->assertRedirect();
        $req = $stay->guestRequests()->firstWhere('service_slug', 'transfert_aeroport');
        $chargeId = $req->charge_id;
        $this->assertSame(80000 + 30000, $stay->fresh()->balance());

        $this->asGuest($stay)->post(route('guest.requests.cancel', $req))->assertRedirect();
        $this->assertDatabaseMissing('reservation_charges', ['id' => $chargeId]);
        $this->assertSame('cancelled', $req->fresh()->status);
        $this->assertSame(80000, $stay->fresh()->balance());
    }

    public function test_service_catalogue_is_listed_on_the_requests_page(): void
    {
        $stay = $this->stay();
        $this->asGuest($stay)->get(route('guest.requests'))->assertOk()
            ->assertSee('Services en chambre')
            ->assertSee('Transfert aéroport (AIBD)');
    }

    public function test_guest_can_cancel_own_open_request(): void
    {
        $stay = $this->stay();
        $req = $stay->guestRequests()->create(['hotel_id' => 1, 'type' => 'autre', 'status' => 'open', 'routed_to' => 'reception']);

        $this->asGuest($stay)->post(route('guest.requests.cancel', $req))->assertRedirect();
        $this->assertSame('cancelled', $req->fresh()->status);

        $other = $this->stay(lastName: 'X');
        $foreign = $other->guestRequests()->create(['hotel_id' => 1, 'type' => 'autre', 'status' => 'open', 'routed_to' => 'reception']);
        $this->asGuest($stay)->post(route('guest.requests.cancel', $foreign))->assertNotFound();
    }

    /* ---------------------------- Fidélité -------------------------- */

    public function test_guest_can_enrol_in_loyalty(): void
    {
        $stay = $this->stay();
        $this->assertNull($stay->guest->loyaltyAccount);

        $this->asGuest($stay)->post(route('guest.loyalty.enrol'))->assertRedirect(route('guest.loyalty'));
        $this->assertNotNull($stay->guest->fresh()->loyaltyAccount);
    }

    /* ------------------------------ PWA ---------------------------- */

    public function test_pwa_assets_are_served(): void
    {
        $this->get(route('guest.manifest'))
            ->assertOk()
            ->assertHeader('content-type', 'application/manifest+json')
            ->assertJsonPath('scope', '/app');

        $sw = $this->get(route('guest.sw'));
        $sw->assertOk();
        $this->assertStringContainsString('application/javascript', $sw->headers->get('content-type'));
        $sw->assertSee('addEventListener');
    }

    /* ------------------------- Back-office ------------------------- */

    public function test_admin_guest_requests_rbac_and_actions(): void
    {
        $stay = $this->stay();
        $req = $stay->guestRequests()->create(['hotel_id' => 1, 'type' => 'menage', 'status' => 'open', 'routed_to' => 'housekeeping']);

        $reception = User::where('role', 'reception')->firstOrFail();
        $restaurant = User::where('role', 'restaurant')->firstOrFail();

        $this->actingAs($reception)->get(route('admin.guest_requests.index'))->assertOk();
        $this->actingAs($restaurant)->get(route('admin.guest_requests.index'))->assertForbidden();

        $this->actingAs($reception)->post(route('admin.guest_requests.ack', $req))->assertRedirect();
        $this->assertSame('acknowledged', $req->fresh()->status);

        $this->actingAs($reception)->post(route('admin.guest_requests.resolve', $req))->assertRedirect();
        $this->assertSame('done', $req->fresh()->status);
    }
}
