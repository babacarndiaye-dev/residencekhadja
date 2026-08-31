<?php

namespace Tests\Feature;

use App\Mail\PreArrivalReminder;
use App\Mail\ReservationCancelled;
use App\Mail\StayCompleted;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\User;
use App\Services\Availability;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class PmsBackofficeTest extends TestCase
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

    public function test_login_screen_is_public_and_dashboard_is_guarded(): void
    {
        $this->get('/admin/login')->assertOk();
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_staff_can_log_in_and_see_dashboard(): void
    {
        $this->post('/admin/login', [
            'email' => 'reception@residence-khadija.sn',
            'password' => 'khadija',
        ])->assertRedirect(route('admin.dashboard'));

        $this->actingAs($this->reception())->get(route('admin.dashboard'))
            ->assertOk()->assertSee('Front Office');
    }

    public function test_housekeeping_cannot_reach_reservations_but_can_reach_rooms(): void
    {
        $hk = User::where('role', 'housekeeping')->firstOrFail();

        $this->actingAs($hk)->get(route('admin.reservations.index'))->assertForbidden();
        $this->actingAs($hk)->get(route('admin.rooms.index'))->assertOk();
    }

    public function test_reception_can_confirm_check_in_and_check_out(): void
    {
        $reception = $this->reception();
        $category = RoomCategory::where('slug', 'suite-junior')->first();

        $reservation = Reservation::create([
            'reference' => 'HRK-TEST01',
            'hotel_id' => $category->hotel_id,
            'guest_id' => Guest::create(['first_name' => 'Bou', 'last_name' => 'Test', 'email' => 'bou@example.com'])->id,
            'room_category_id' => $category->id,
            'status' => 'pending',
            'check_in' => Carbon::today()->toDateString(),
            'check_out' => Carbon::today()->addDays(2)->toDateString(),
            'adults' => 2, 'rooms_count' => 1,
            'room_total' => 164000, 'total' => 190000, 'deposit' => 57000,
        ]);

        $this->actingAs($reception)
            ->post(route('admin.reservations.confirm', $reservation))
            ->assertRedirect();
        $this->assertSame('confirmed', $reservation->refresh()->status);

        $room = Room::where('room_category_id', $category->id)->where('status', 'propre')->first();

        $this->actingAs($reception)
            ->post(route('admin.reservations.check_in', $reservation), ['room_id' => $room->id])
            ->assertRedirect();

        $reservation->refresh();
        $this->assertSame('checked_in', $reservation->status);
        $this->assertSame($room->id, $reservation->room_id);
        $this->assertSame('occupee', $room->refresh()->status);

        $this->actingAs($reception)
            ->post(route('admin.reservations.check_out', $reservation), ['settle_balance' => '1', 'method' => 'carte'])
            ->assertRedirect(route('admin.reservations.invoice', $reservation));

        $reservation->refresh();
        $this->assertSame('checked_out', $reservation->status);
        $this->assertNotNull($reservation->invoice_number);
        $this->assertSame('sale', $room->refresh()->status);
        $this->assertSame(0, $reservation->balance());
    }

    public function test_room_status_can_be_updated(): void
    {
        $room = Room::first();

        $this->actingAs($this->reception())
            ->post(route('admin.rooms.status', $room), ['status' => 'en_nettoyage'])
            ->assertRedirect();

        $this->assertSame('en_nettoyage', $room->refresh()->status);
    }

    /* --------------------------- E-mails invité --------------------------- */

    private function stay(string $status = 'confirmed'): Reservation
    {
        $category = RoomCategory::where('slug', 'suite-junior')->first();

        return Reservation::create([
            'reference' => 'HRK-'.strtoupper(Str::random(6)),
            'hotel_id' => $category->hotel_id,
            'guest_id' => Guest::create(['first_name' => 'Awa', 'last_name' => 'Sy', 'email' => 'awa.sy@example.com'])->id,
            'room_category_id' => $category->id,
            'status' => $status,
            'check_in' => Carbon::today()->addDays(2)->toDateString(),
            'check_out' => Carbon::today()->addDays(4)->toDateString(),
            'adults' => 2, 'rooms_count' => 1,
            'room_total' => 164000, 'total' => 190000, 'deposit' => 57000,
            'confirmed_at' => now(),
        ]);
    }

    public function test_cancelling_a_reservation_emails_the_guest(): void
    {
        Mail::fake();
        $reservation = $this->stay();

        $this->actingAs($this->reception())
            ->post(route('admin.reservations.cancel', $reservation), ['reason' => 'Changement de dates'])
            ->assertRedirect();

        Mail::assertQueued(ReservationCancelled::class, fn ($m) => $m->hasTo('awa.sy@example.com'));
    }

    public function test_check_out_emails_a_thank_you_with_the_invoice_number(): void
    {
        Mail::fake();
        $reservation = $this->stay('checked_in');
        $room = Room::where('room_category_id', $reservation->room_category_id)->where('status', 'propre')->first();
        $reservation->update(['room_id' => $room->id, 'checked_in_at' => now()]);
        $room->update(['status' => 'occupee']);

        $this->actingAs($this->reception())
            ->post(route('admin.reservations.check_out', $reservation), ['settle_balance' => '1', 'method' => 'especes'])
            ->assertRedirect();

        Mail::assertQueued(StayCompleted::class, fn ($m) => $m->hasTo('awa.sy@example.com'));
    }

    public function test_pre_arrival_command_emails_a_confirmed_stay_and_does_not_repeat(): void
    {
        Mail::fake();
        $reservation = $this->stay(); // check_in = today + 2

        $this->artisan('reservations:pre-arrival')->assertSuccessful();
        Mail::assertQueued(PreArrivalReminder::class, fn ($m) => $m->hasTo('awa.sy@example.com'));

        $sentAt = $reservation->fresh()->pre_arrival_sent_at;
        $this->assertNotNull($sentAt);

        // Rejeu le lendemain : la relance n'est pas renvoyée (garde-fou pre_arrival_sent_at).
        $this->artisan('reservations:pre-arrival')->assertSuccessful();
        $this->assertEquals($sentAt->timestamp, $reservation->fresh()->pre_arrival_sent_at->timestamp);
    }

    public function test_availability_blocks_overbooking(): void
    {
        $category = RoomCategory::where('slug', 'suite-teranga')->first(); // 4 chambres
        $in = Carbon::today()->addDays(40)->toDateString();
        $out = Carbon::today()->addDays(42)->toDateString();

        $this->assertSame(4, Availability::remaining($category, $in, $out));

        foreach (range(1, 4) as $i) {
            Reservation::create([
                'reference' => "HRK-FULL0{$i}",
                'hotel_id' => $category->hotel_id,
                'guest_id' => Guest::create(['first_name' => "G{$i}", 'last_name' => 'X', 'email' => "g{$i}@example.com"])->id,
                'room_category_id' => $category->id,
                'status' => 'confirmed',
                'check_in' => $in, 'check_out' => $out,
                'adults' => 2, 'rooms_count' => 1, 'total' => 1,
            ]);
        }

        $this->assertSame(0, Availability::remaining($category, $in, $out));
        $this->assertFalse(Availability::canBook($category, $in, $out, 1));
    }

    /* ----------------------- Réservation sur place (walk-in) ----------------------- */

    public function test_reception_creates_a_walk_in_reservation_with_immediate_check_in(): void
    {
        $category = RoomCategory::active()->firstOrFail();

        $res = $this->actingAs($this->reception())->post(route('admin.reservations.store'), [
            'first_name' => 'Moussa', 'last_name' => 'Faye', 'phone' => '+221771112233',
            'room_category_id' => $category->id,
            'check_in' => Carbon::today()->toDateString(),
            'check_out' => Carbon::today()->addDays(2)->toDateString(),
            'adults' => 2, 'rooms_count' => 1,
            'check_in_now' => 1,
        ]);

        $res->assertRedirect();
        $reservation = Reservation::where('channel', 'walk_in')->latest('id')->firstOrFail();

        $this->assertSame('checked_in', $reservation->status);
        $this->assertNotNull($reservation->room_id);
        $this->assertGreaterThan(0, $reservation->total);
        $this->assertSame('occupee', $reservation->room->fresh()->status);
        $this->assertDatabaseHas('guests', ['last_name' => 'Faye', 'acquisition_source' => 'walk_in']);
    }

    public function test_walk_in_without_immediate_check_in_stays_confirmed(): void
    {
        $category = RoomCategory::active()->firstOrFail();

        $this->actingAs($this->reception())->post(route('admin.reservations.store'), [
            'first_name' => 'Aida', 'last_name' => 'Ba', 'email' => 'aida.ba@example.com',
            'room_category_id' => $category->id,
            'check_in' => Carbon::today()->toDateString(),
            'check_out' => Carbon::today()->addDay()->toDateString(),
            'adults' => 1, 'rooms_count' => 1,
        ])->assertRedirect();

        $reservation = Reservation::where('channel', 'walk_in')->latest('id')->firstOrFail();
        $this->assertSame('confirmed', $reservation->status);
        $this->assertNull($reservation->room_id);
    }

    public function test_walk_in_rejects_a_sold_out_category(): void
    {
        $category = RoomCategory::active()->firstOrFail();
        $in = Carbon::today()->toDateString();
        $out = Carbon::today()->addDay()->toDateString();

        for ($i = 0; $i < $category->sellableRoomsCount(); $i++) {
            Reservation::create([
                'reference' => 'HRK-SOLD'.$i, 'hotel_id' => 1,
                'guest_id' => Guest::create(['first_name' => "S{$i}", 'last_name' => 'X', 'email' => "sold{$i}@example.com"])->id,
                'room_category_id' => $category->id, 'status' => 'confirmed',
                'check_in' => $in, 'check_out' => $out, 'adults' => 1, 'rooms_count' => 1, 'total' => 1,
            ]);
        }

        $this->actingAs($this->reception())->post(route('admin.reservations.store'), [
            'first_name' => 'Tard', 'last_name' => 'Arrivant',
            'room_category_id' => $category->id, 'check_in' => $in, 'check_out' => $out,
            'adults' => 1, 'rooms_count' => 1,
        ])->assertSessionHasErrors('room_category_id');
    }

    public function test_walk_in_form_is_reception_only(): void
    {
        $this->actingAs($this->reception())->get(route('admin.reservations.create'))->assertOk();
        $this->actingAs(User::where('role', 'housekeeping')->firstOrFail())
            ->get(route('admin.reservations.create'))->assertForbidden();
    }

    /* ------------------------- Mot de passe oublié (staff) ------------------------- */

    public function test_staff_password_reset_flow(): void
    {
        Notification::fake();
        $user = $this->reception();

        $this->get(route('admin.password.request'))->assertOk();

        $this->post(route('admin.password.email'), ['email' => $user->email])
            ->assertRedirect()->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use ($user) {
            $token = $n->token;

            $this->get(route('password.reset', ['token' => $token]))->assertOk();

            $this->post(route('admin.password.update'), [
                'token' => $token,
                'email' => $user->email,
                'password' => 'nouveau-mot-de-passe',
                'password_confirmation' => 'nouveau-mot-de-passe',
            ])->assertRedirect(route('admin.login'))->assertSessionHas('status');

            return true;
        });

        $this->assertTrue(Hash::check('nouveau-mot-de-passe', $user->fresh()->password));

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'nouveau-mot-de-passe'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_password_reset_rejects_a_bad_token(): void
    {
        $this->post(route('admin.password.update'), [
            'token' => 'jeton-bidon',
            'email' => $this->reception()->email,
            'password' => 'peu-importe-1',
            'password_confirmation' => 'peu-importe-1',
        ])->assertSessionHasErrors('email');
    }
}
