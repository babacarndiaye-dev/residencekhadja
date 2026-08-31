<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\StaffAlert;
use App\Support\Notify;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        DatabaseNotification::query()->delete(); // repart d'une boîte vide (le seed en pose 2)
    }

    private function reception(): User
    {
        return User::where('role', 'reception')->firstOrFail();
    }

    public function test_notify_roles_reaches_the_targeted_roles_plus_admin_only(): void
    {
        User::query()->update(['is_active' => true]);

        Notify::roles(['reception'], 'Test', 'Corps', '/admin', icon: '🔔');

        $this->assertSame(1, $this->reception()->notifications()->count());
        $this->assertSame(1, User::where('role', 'admin')->firstOrFail()->notifications()->count());
        $this->assertSame(0, User::where('role', 'housekeeping')->firstOrFail()->notifications()->count());
    }

    public function test_inactive_users_are_skipped(): void
    {
        $r = $this->reception();
        $r->update(['is_active' => false]);

        Notify::roles(['reception'], 'Test', 'Corps');

        $this->assertSame(0, $r->notifications()->count());
    }

    public function test_the_bell_shows_the_unread_count_and_marks_all_read(): void
    {
        $reception = $this->reception();
        $reception->notify(new StaffAlert('Une alerte', 'détail', route('admin.dashboard', absolute: false)));

        $this->actingAs($reception)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Une alerte');

        $this->actingAs($reception)->post(route('admin.notifications.read_all'))->assertRedirect();
        $this->assertSame(0, $reception->unreadNotifications()->count());
    }

    public function test_opening_a_notification_marks_it_read_and_redirects_to_its_url(): void
    {
        $reception = $this->reception();
        $reception->notify(new StaffAlert('Voir la résa', 'HRK-XXXX', '/admin/reservations'));
        $id = $reception->notifications()->first()->id;

        $this->actingAs($reception)->post(route('admin.notifications.read', $id))
            ->assertRedirect('/admin/reservations');

        $this->assertNotNull($reception->notifications()->first()->read_at);
    }

    public function test_a_contact_message_notifies_reception(): void
    {
        $this->post('/contact', [
            'name' => 'Prospect', 'email' => 'p@example.com', 'subject' => 'Devis',
            'message' => 'Bonjour, un devis séminaire svp.', 'consent' => '1',
        ])->assertRedirect();

        $this->assertTrue(
            $this->reception()->notifications()
                ->where('data->title', 'Nouveau message de contact')->exists(),
        );
    }

    public function test_notifications_page_is_open_to_any_staff_role(): void
    {
        $housekeeping = User::where('role', 'housekeeping')->firstOrFail();

        $this->actingAs($housekeeping)->get(route('admin.notifications.index'))->assertOk();
    }
}
