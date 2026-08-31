<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SiteSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public static function publicRoutes(): array
    {
        return [
            'accueil' => ['home'],
            'l-hôtel' => ['about'],
            'chambres' => ['rooms.index'],
            'restaurant' => ['restaurant'],
            'espaces' => ['spaces'],
            'événements' => ['events'],
            'expériences' => ['experiences'],
            'galerie' => ['gallery'],
            'offres' => ['offers.index'],
            'contact' => ['contact'],
            'réservation' => ['booking.start'],
        ];
    }

    #[DataProvider('publicRoutes')]
    public function test_public_pages_render(string $route): void
    {
        $this->get(route($route))->assertOk();
    }

    public function test_room_detail_pages_render(): void
    {
        foreach (config('rooms.items') as $room) {
            $this->get(route('rooms.show', $room['slug']))
                ->assertOk()
                ->assertSee($room['name']);
        }
    }

    public function test_unknown_room_returns_404(): void
    {
        $this->get(route('rooms.show', 'chambre-inexistante'))->assertNotFound();
    }

    public function test_sitemap_is_valid_xml(): void
    {
        $res = $this->get('/sitemap.xml')->assertOk();
        $this->assertStringContainsString('<urlset', $res->getContent());
        $this->assertNotFalse(simplexml_load_string($res->getContent()));
    }

    public function test_contact_form_validates_and_accepts(): void
    {
        $this->post(route('contact.send'), [])->assertSessionHasErrors(['name', 'email', 'message']);

        $this->post(route('contact.send'), [
            'name' => 'Awa Ba', 'email' => 'awa@example.com', 'subject' => 'Bonjour',
            'message' => 'Une question sur les suites.', 'consent' => '1',
        ])->assertRedirect()->assertSessionHas('status');
    }
}
