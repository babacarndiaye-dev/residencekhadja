<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function direction(): User
    {
        return User::where('role', 'direction')->firstOrFail();
    }

    /** Charge utile complète (tous les champs requis) + surcharges. */
    private function payload(array $overrides = []): array
    {
        $f = [];
        foreach (SiteSettings::fields() as $field) {
            $f[str_replace('.', '~', $field['key'])] = SiteSettings::value($field);
        }
        foreach ($overrides as $key => $value) {
            $f[str_replace('.', '~', $key)] = $value;
        }

        return ['f' => $f];
    }

    public function test_direction_sees_every_settings_group(): void
    {
        $res = $this->actingAs($this->direction())->get(route('admin.site_settings.edit'))->assertOk();

        foreach (config('settings.groups') as $group) {
            $res->assertSee($group['label']);
        }
    }

    public function test_updating_the_hotel_name_changes_it_across_the_site(): void
    {
        $this->actingAs($this->direction())
            ->put(route('admin.site_settings.update'), $this->payload([
                'hotel.name' => 'Hôtel Résidence Khadija & Spa',
                'hotel.short_name' => 'Khadija & Spa',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('site_settings', ['key' => 'hotel.name', 'value' => 'Hôtel Résidence Khadija & Spa']);
        $this->assertSame('Hôtel Résidence Khadija & Spa', config('hotel.name'));
        $this->assertSame('Hôtel Résidence Khadija & Spa', config('hotel')['name']);

        // La vitrine (pied de page) reflète la nouvelle identité.
        $this->get(route('home'))->assertOk()->assertSee('Hôtel Résidence Khadija &amp; Spa', false);
    }

    public function test_numeric_settings_are_cast_when_applied(): void
    {
        $this->actingAs($this->direction())
            ->put(route('admin.site_settings.update'), $this->payload([
                'booking.min_nights' => '2',
                'payments.reservation_deposit_rate' => '0.4',
                'loyalty.point_value_fcfa' => '10',
            ]))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(2, config('booking.min_nights'));
        $this->assertSame(0.4, config('payments.reservation_deposit_rate'));
        $this->assertSame(10, config('loyalty.point_value_fcfa'));
    }

    public function test_invalid_values_are_rejected_and_nothing_is_saved(): void
    {
        $this->actingAs($this->direction())
            ->put(route('admin.site_settings.update'), $this->payload([
                'hotel.contact.email' => 'pas-un-email',
                'payments.reservation_deposit_rate' => '3',
            ]))
            ->assertSessionHasErrors(['f.hotel~contact~email', 'f.payments~reservation_deposit_rate']);

        $this->assertDatabaseCount('site_settings', 0);
    }

    public function test_settings_are_reserved_to_direction(): void
    {
        $reception = User::where('role', 'reception')->firstOrFail();

        $this->actingAs($reception)->get(route('admin.site_settings.edit'))->assertForbidden();
        $this->actingAs($reception)->put(route('admin.site_settings.update'), $this->payload())->assertForbidden();
    }
}
