<?php

namespace Tests\Feature;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function make(string $status = 'new'): ContactMessage
    {
        return ContactMessage::create([
            'name' => 'Awa Cissé',
            'email' => 'awa.cisse@example.sn',
            'phone' => '+221 77 000 00 00',
            'subject' => 'Question sur le petit-déjeuner',
            'message' => "Bonjour,\nLe petit-déjeuner est-il inclus dans le tarif flexible ?\nMerci.",
            'status' => $status,
        ]);
    }

    public function test_public_submission_persists_and_queues_the_notification(): void
    {
        Mail::fake();

        $this->post('/contact', [
            'name' => 'Awa Test',
            'email' => 'awa@example.com',
            'phone' => '',
            'subject' => 'Demande d\'information',
            'message' => 'Bonjour, je voudrais connaître vos disponibilités en octobre.',
            'consent' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'awa@example.com',
            'subject' => 'Demande d\'information',
            'status' => 'new',
        ]);

        Mail::assertQueued(
            ContactMessageReceived::class,
            fn ($mail) => $mail->hasTo(config('hotel.contact.email')),
        );
    }

    public function test_submission_requires_consent(): void
    {
        Mail::fake();

        $this->post('/contact', [
            'name' => 'Sans Consentement',
            'email' => 'sans-consentement@example.com',
            'subject' => 'Test',
            'message' => 'Message sans la case cochée.',
        ])->assertSessionHasErrors('consent');

        $this->assertDatabaseMissing('contact_messages', ['email' => 'sans-consentement@example.com']);
        Mail::assertNothingQueued();
    }

    public function test_reception_reads_the_inbox_then_marks_a_message_handled(): void
    {
        $reception = User::where('role', 'reception')->firstOrFail();
        $message = $this->make();

        $this->actingAs($reception)->get(route('admin.messages.index'))
            ->assertOk()->assertSee('Awa Cissé');

        // L'ouverture passe « nouveau » → « lu ».
        $this->actingAs($reception)->get(route('admin.messages.show', $message))->assertOk();
        $this->assertSame('read', $message->fresh()->status);

        // Marquer traité.
        $this->actingAs($reception)->post(route('admin.messages.handle', $message))->assertRedirect();
        $message->refresh();
        $this->assertSame('handled', $message->status);
        $this->assertSame($reception->id, $message->handled_by);
        $this->assertNotNull($message->handled_at);

        // Rouvrir.
        $this->actingAs($reception)->post(route('admin.messages.handle', $message))->assertRedirect();
        $this->assertSame('read', $message->fresh()->status);
        $this->assertNull($message->fresh()->handled_by);
    }

    public function test_inbox_is_forbidden_for_housekeeping_and_serves_the_branded_403(): void
    {
        $hk = User::where('role', 'housekeeping')->firstOrFail();

        $this->actingAs($hk)->get(route('admin.messages.index'))
            ->assertForbidden()
            ->assertSee('Accès refusé');
    }
}
