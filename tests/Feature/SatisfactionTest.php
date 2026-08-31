<?php

namespace Tests\Feature;

use App\Mail\SatisfactionInvitation;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\RoomCategory;
use App\Models\SatisfactionSurvey;
use App\Models\User;
use App\Notifications\StaffAlert;
use App\Services\Satisfaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class SatisfactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function completedStay(string $email = 'awa.test@example.sn', int $checkOutDaysAgo = 1): Reservation
    {
        $category = RoomCategory::firstOrFail();
        $checkOut = Carbon::today()->subDays($checkOutDaysAgo);
        $guest = Guest::create([
            'civility' => 'Mme', 'first_name' => 'Awa', 'last_name' => 'Test',
            'email' => $email, 'phone' => '+221 77 111 22 33', 'country' => 'Sénégal', 'city' => 'Dakar',
        ]);

        return Reservation::create([
            'reference' => 'HRK-'.strtoupper(Str::random(6)),
            'hotel_id' => Hotel::query()->value('id'),
            'guest_id' => $guest->id,
            'room_category_id' => $category->id,
            'status' => 'checked_out',
            'channel' => 'direct',
            'check_in' => $checkOut->copy()->subDays(2),
            'check_out' => $checkOut,
            'adults' => 2, 'rooms_count' => 1,
            'room_total' => 120000, 'tax_amount' => 0, 'tourist_tax' => 0,
            'total' => 120000, 'deposit' => 36000,
            'checked_out_at' => $checkOut,
        ]);
    }

    private function pendingSurvey(array $overrides = []): SatisfactionSurvey
    {
        return SatisfactionSurvey::create(array_merge([
            'hotel_id' => Hotel::query()->value('id'),
            'token' => Str::lower(Str::random(40)),
            'channel' => 'post_stay',
            'sent_at' => now()->subDay(),
            'expires_on' => now()->addDays(20)->toDateString(),
            'status' => 'pending',
        ], $overrides));
    }

    public function test_invite_command_creates_one_survey_and_queues_the_mail(): void
    {
        Mail::fake();
        $stay = $this->completedStay();

        $this->artisan('satisfaction:invite')->assertSuccessful();

        Mail::assertQueued(SatisfactionInvitation::class, fn ($m) => $m->hasTo('awa.test@example.sn'));
        $this->assertSame(1, SatisfactionSurvey::where('reservation_id', $stay->id)->count());

        // Rejouer la commande ne recrée rien.
        Mail::fake();
        $this->artisan('satisfaction:invite')->assertSuccessful();
        Mail::assertNotQueued(SatisfactionInvitation::class);
        $this->assertSame(1, SatisfactionSurvey::where('reservation_id', $stay->id)->count());
    }

    public function test_public_form_records_a_response(): void
    {
        $survey = $this->pendingSurvey();

        $this->get(route('satisfaction.show', $survey->token))->assertOk()->assertSee('Note globale');

        $this->post(route('satisfaction.store', $survey->token), [
            'rating_overall' => 5,
            'nps_score' => 10,
            'categories' => ['room' => 5, 'staff' => 4],
            'comment' => 'Séjour mémorable, merci à toute l’équipe.',
            'consent_publish' => 1,
            'author_label' => 'Awa D., Dakar',
        ])->assertRedirect(route('satisfaction.done', $survey->token));

        $survey->refresh();
        $this->assertNotNull($survey->completed_at);
        $this->assertSame('received', $survey->status);
        $this->assertSame(5, $survey->rating_overall);
        $this->assertSame(10, $survey->nps_score);
        $this->assertSame(5, $survey->categoryRating('room'));
        $this->assertTrue($survey->consent_publish);
        $this->assertFalse($survey->is_published);

        $this->get(route('satisfaction.done', $survey->token))->assertOk();
    }

    public function test_a_detractor_alerts_reception_and_direction(): void
    {
        Notification::fake();
        $survey = $this->pendingSurvey();

        $this->post(route('satisfaction.store', $survey->token), [
            'rating_overall' => 2,
            'nps_score' => 3,
            'comment' => 'Beaucoup de bruit, check-in laborieux.',
        ])->assertRedirect();

        Notification::assertSentTo(
            User::whereIn('role', ['reception', 'direction'])->get(),
            StaffAlert::class,
        );
    }

    public function test_expired_or_completed_links(): void
    {
        $expired = $this->pendingSurvey(['expires_on' => now()->subDay()->toDateString()]);
        $this->get(route('satisfaction.show', $expired->token))->assertStatus(410);

        $done = $this->pendingSurvey(['completed_at' => now(), 'status' => 'received', 'rating_overall' => 4]);
        $this->get(route('satisfaction.show', $done->token))->assertRedirect(route('satisfaction.done', $done->token));
    }

    public function test_stats_compute_nps_and_average(): void
    {
        // 3 promoteurs (9-10), 1 passif (7-8), 1 détracteur (0-6) → NPS = (60 - 20) = 40.
        foreach ([[5, 10], [5, 9], [4, 10], [4, 8], [2, 3]] as [$overall, $nps]) {
            $this->pendingSurvey([
                'completed_at' => now(), 'status' => 'received',
                'rating_overall' => $overall, 'nps_score' => $nps,
            ]);
        }

        $stats = Satisfaction::stats(Carbon::today(), Carbon::today());

        $this->assertSame(5, $stats['completed']);
        $this->assertSame(40, $stats['nps']);
        $this->assertSame(4.0, $stats['avg_overall']);
    }

    public function test_admin_publishes_a_consented_review_and_it_reaches_the_homepage(): void
    {
        $comment = 'Un thiéboudieune inoubliable et un rooftop à couper le souffle — vérifié.';
        $survey = $this->pendingSurvey([
            'completed_at' => now(), 'status' => 'received', 'rating_overall' => 5, 'nps_score' => 10,
            'comment' => $comment, 'consent_publish' => true, 'author_label' => 'Test V., Thiès',
        ]);

        $admin = User::where('role', 'direction')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.satisfaction.publish', $survey->id))->assertRedirect();
        $this->assertTrue($survey->refresh()->is_published);
        $this->get(route('home'))->assertOk()->assertSee($comment);

        // Re-basculer retire l'avis du site.
        $this->actingAs($admin)->post(route('admin.satisfaction.publish', $survey->id))->assertRedirect();
        $this->assertFalse($survey->refresh()->is_published);
        $this->get(route('home'))->assertDontSee($comment);
    }

    public function test_publish_is_refused_without_consent(): void
    {
        $survey = $this->pendingSurvey([
            'completed_at' => now(), 'status' => 'received', 'rating_overall' => 5,
            'comment' => 'Bien mais je ne veux pas être cité.', 'consent_publish' => false,
        ]);

        $this->actingAs(User::where('role', 'direction')->firstOrFail())
            ->post(route('admin.satisfaction.publish', $survey->id))
            ->assertStatus(422);
    }

    public function test_dashboard_rbac(): void
    {
        $this->actingAs(User::where('role', 'housekeeping')->firstOrFail())
            ->get(route('admin.satisfaction.index'))->assertForbidden();

        $this->actingAs(User::where('role', 'direction')->firstOrFail())
            ->get(route('admin.satisfaction.index'))->assertOk()->assertSee('NPS');
    }

    public function test_manual_invite_from_the_back_office(): void
    {
        Mail::fake();
        $stay = $this->completedStay('manual.invite@example.sn');

        $this->actingAs(User::where('role', 'reception')->firstOrFail())
            ->post(route('admin.satisfaction.invite'), ['reference' => $stay->reference])
            ->assertRedirect();

        $this->assertDatabaseHas('satisfaction_surveys', [
            'reservation_id' => $stay->id,
            'channel' => 'manual',
        ]);

        $this->actingAs(User::where('role', 'reception')->firstOrFail())
            ->post(route('admin.satisfaction.invite'), ['reference' => 'HRK-INCONNU'])
            ->assertSessionHasErrors('reference');
    }
}
