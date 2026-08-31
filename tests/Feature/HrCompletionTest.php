<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\EmployeeDocument;
use App\Models\PublicHoliday;
use App\Models\Shift;
use App\Models\User;
use App\Services\HrAlerts;
use App\Services\LeaveService;
use App\Services\PayrollEngine;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HrCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /* --------------------------- Barèmes paie --------------------------- */

    public function test_family_quotient_parts(): void
    {
        $e = new Employee(['marital_status' => 'marie', 'dependents_count' => 3]);
        $this->assertSame(3.0, PayrollEngine::parts($e));           // 1.5 + 3×0.5

        $e2 = new Employee(['marital_status' => 'marie', 'dependents_count' => 10]);
        $this->assertSame(5.0, PayrollEngine::parts($e2));          // plafonné
    }

    public function test_irpp_is_progressive_and_split_by_parts(): void
    {
        // 1 part : 250 000/mois → 3 000 000/an. 630k à 0 % + 1 500k−630k à 20 % + reste à 30 %.
        $expected = (int) round(0 + (1500000 - 630000) * 0.20 + (3000000 - 1500000) * 0.30);
        $this->assertSame((int) round($expected / 12), PayrollEngine::monthlyIrpp(250000, 1.0));

        // Plus de parts ⇒ moins d'impôt.
        $this->assertLessThan(
            PayrollEngine::monthlyIrpp(250000, 1.0),
            PayrollEngine::monthlyIrpp(250000, 2.5),
        );
    }

    public function test_seniority_rate_steps_and_caps(): void
    {
        $mk = fn (string $hire) => new Employee(['hire_date' => Carbon::parse($hire)]);
        $this->assertSame(0.0, PayrollEngine::seniorityRate($mk(now()->subMonths(18)->toDateString())));
        $this->assertSame(2.0, PayrollEngine::seniorityRate($mk(now()->subYears(2)->subDay()->toDateString())));
        $this->assertSame(5.0, PayrollEngine::seniorityRate($mk(now()->subYears(5)->subDay()->toDateString())));
        $this->assertSame(25.0, PayrollEngine::seniorityRate($mk(now()->subYears(40)->toDateString())));
    }

    private function settingsPayload(array $overrides = []): array
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

    public function test_setting_overrides_the_overtime_multiplier(): void
    {
        $this->actingAs(User::where('role', 'direction')->firstOrFail())
            ->put(route('admin.site_settings.update'), $this->settingsPayload([
                'payroll.overtime_multiplier' => '1.5',
            ]))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(1.5, (float) config('payroll.overtime_multiplier'));
    }

    /* ----------------------------- Jours fériés ------------------------- */

    public function test_working_days_excludes_holidays(): void
    {
        // Une semaine (lun→ven) contenant un férié fixe = 4 jours ouvrables.
        $ny = Carbon::create(now()->year + 1, 1, 1);      // Jour de l'An (fixe)
        $mon = $ny->copy()->startOfWeek();
        $fri = $mon->copy()->addDays(4);
        $full = LeaveService::workingDays($mon, $fri);

        PublicHoliday::create(['hotel_id' => 1, 'date' => $mon->copy()->addDay()->toDateString(), 'name' => 'Test férié']);
        $this->assertSame($full - 1, LeaveService::workingDays($mon, $fri));
    }

    public function test_admin_manages_public_holidays(): void
    {
        $rh = User::where('role', 'rh')->firstOrFail();

        $this->actingAs($rh)->post(route('admin.hr.holidays.store'), [
            'date' => now()->addMonth()->toDateString(), 'name' => 'Korité',
        ])->assertRedirect();

        $hol = PublicHoliday::where('name', 'Korité')->firstOrFail();
        $this->actingAs($rh)->delete(route('admin.hr.holidays.destroy', $hol))->assertRedirect();
        $this->assertModelMissing($hol);
    }

    /* --------------------------- Sortie de salarié --------------------- */

    public function test_direction_terminates_an_employee(): void
    {
        $emp = Employee::active()->has('user')->firstOrFail();
        Shift::create([
            'hotel_id' => $emp->hotel_id, 'employee_id' => $emp->id, 'work_date' => now()->addWeek()->toDateString(),
            'start_at' => now()->addWeek()->setTime(8, 0), 'end_at' => now()->addWeek()->setTime(16, 0), 'status' => 'planned',
        ]);

        $this->actingAs(User::where('role', 'direction')->firstOrFail())
            ->post(route('admin.hr.employees.terminate', $emp), [
                'termination_date' => now()->toDateString(),
                'termination_type' => 'demission',
                'termination_notes' => 'Départ volontaire.',
            ])->assertRedirect();

        $emp->refresh();
        $this->assertTrue($emp->isTerminated());
        $this->assertSame('demission', $emp->termination_type);
        $this->assertFalse((bool) $emp->user->is_active);
        $this->assertSame('ended', $emp->contracts()->first()?->status);
        $this->assertSame('cancelled', $emp->shifts()->latest('id')->first()->status);
        $this->assertFalse(Employee::active()->whereKey($emp->id)->exists());
    }

    public function test_rh_cannot_terminate(): void
    {
        $emp = Employee::active()->firstOrFail();
        $this->actingAs(User::where('role', 'rh')->firstOrFail())
            ->post(route('admin.hr.employees.terminate', $emp), [
                'termination_date' => now()->toDateString(), 'termination_type' => 'demission',
            ])->assertForbidden();
    }

    /* ----------------------------- Attestations ----------------------- */

    public function test_attestations_render(): void
    {
        $emp = Employee::active()->firstOrFail();
        $rh = User::where('role', 'rh')->firstOrFail();

        foreach (['travail', 'salaire', 'fin_contrat'] as $type) {
            $this->actingAs($rh)->get(route('admin.hr.employees.attestation', [$emp, $type]))
                ->assertOk()->assertSee($emp->fullName());
        }

        $this->actingAs($rh)->get(route('admin.hr.employees.attestation', [$emp, 'bidon']))->assertNotFound();
    }

    /* ------------------------------- Alertes -------------------------- */

    public function test_hr_alerts_flag_expiring_documents_and_trial_periods(): void
    {
        $emp = Employee::active()->firstOrFail();

        EmployeeDocument::create([
            'employee_id' => $emp->id, 'category' => 'medical', 'title' => 'Visite médicale',
            'file_path' => 'x.pdf', 'expires_on' => now()->subDay()->toDateString(),
        ]);
        EmployeeContract::where('employee_id', $emp->id)->update([
            'status' => 'active', 'start_date' => now()->subDays(85)->toDateString(), 'trial_period_days' => 90,
        ]);

        $alerts = HrAlerts::collect();
        $this->assertTrue($alerts->contains(fn ($a) => $a['type'] === 'document' && $a['level'] === 'critical'));
        $this->assertTrue($alerts->contains(fn ($a) => $a['type'] === 'periode_essai'));

        $this->actingAs(User::where('role', 'rh')->firstOrFail())
            ->get(route('admin.hr.dashboard'))->assertOk()->assertSee('Alertes RH');
    }
}
