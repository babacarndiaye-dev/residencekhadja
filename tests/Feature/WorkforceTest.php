<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\SalaryAdvance;
use App\Models\SalaryComponent;
use App\Models\Shift;
use App\Models\User;
use App\Services\Accounting;
use App\Services\AttendanceService;
use App\Services\LeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkforceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function rh(): User
    {
        return User::where('role', 'rh')->firstOrFail();
    }

    private function employeeWithPin(string $pin = '4321'): Employee
    {
        $emp = Employee::active()->where('tracks_attendance', true)->firstOrFail();
        $emp->update(['pin_hash' => Hash::make($pin), 'employment_status' => 'active']);

        return $emp;
    }

    /* --------------------------- Pointage --------------------------- */

    public function test_borne_clock_in_then_out_computes_time(): void
    {
        $emp = $this->employeeWithPin();
        AttendanceLog::where('employee_id', $emp->id)->whereDate('work_date', today())->delete();

        // Shift 08:00–17:00 aujourd'hui.
        Shift::where('employee_id', $emp->id)->whereDate('work_date', today())->delete();
        $shift = Shift::create([
            'hotel_id' => $emp->hotel_id, 'employee_id' => $emp->id, 'department_id' => $emp->department_id,
            'work_date' => today()->toDateString(),
            'start_at' => today()->setTime(8, 0), 'end_at' => today()->setTime(17, 0),
            'break_minutes' => 60, 'status' => 'planned',
        ]);

        // Entrée 20 min en retard.
        AttendanceService::clock($emp, 'admin', today()->setTime(8, 20));
        $log = AttendanceLog::where('employee_id', $emp->id)->whereDate('work_date', today())->first();
        $this->assertNotNull($log->clock_in);
        $this->assertSame(15, $log->late_minutes); // 20 - grâce 5
        $this->assertSame($shift->id, $log->shift_id);

        // Sortie 18:00 : 08:20→18:00 = 580 min − 60 pause = 520 travaillées.
        // Attendu (shift) = 540 − 60 = 480 ⇒ 40 min supplémentaires.
        AttendanceService::clock($emp, 'admin', today()->setTime(18, 0));
        $log->refresh();
        $this->assertSame('closed', $log->status);
        $this->assertSame(520, $log->worked_minutes);
        $this->assertSame(40, $log->overtime_minutes);
    }

    public function test_borne_rejects_wrong_pin(): void
    {
        $this->employeeWithPin('1234');
        $this->expectException(ValidationException::class);
        AttendanceService::clockByPin($this->employeeWithPin('1234')->matricule, '0000');
    }

    public function test_borne_route_records_attendance(): void
    {
        $emp = $this->employeeWithPin('5678');
        AttendanceLog::where('employee_id', $emp->id)->whereDate('work_date', today())->delete();

        $this->post('/pointage', ['matricule' => $emp->matricule, 'pin' => '5678'])
            ->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('attendance_logs', ['employee_id' => $emp->id, 'status' => 'open']);
    }

    public function test_employee_not_subject_to_attendance_cannot_clock(): void
    {
        $emp = $this->employeeWithPin('2468');
        $emp->update(['tracks_attendance' => false]);
        AttendanceLog::where('employee_id', $emp->id)->whereDate('work_date', today())->delete();

        // Pointage borne (PIN) refusé avec un message explicite.
        try {
            AttendanceService::clockByPin($emp->matricule, '2468');
            $this->fail('Le pointage aurait dû être refusé.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('pointage', $e->validator->errors()->first());
        }

        // Scan du badge signé : ok=false, aucun log créé.
        $signed = URL::signedRoute('clock.scan', ['employee' => $emp->matricule], null, false);
        $this->getJson($signed)->assertOk()->assertJson(['ok' => false]);
        $this->assertDatabaseMissing('attendance_logs', [
            'employee_id' => $emp->id, 'work_date' => today()->toDateString(),
        ]);

        // Absent de l'écran /admin/rh/pointage même avec un shift planifié ce jour.
        Shift::create([
            'hotel_id' => $emp->hotel_id, 'employee_id' => $emp->id, 'department_id' => $emp->department_id,
            'work_date' => today()->toDateString(),
            'start_at' => today()->setTime(8, 0), 'end_at' => today()->setTime(17, 0), 'status' => 'planned',
        ]);
        $this->actingAs($this->rh())
            ->get(route('admin.hr.attendance', ['date' => today()->toDateString()]))
            ->assertOk()->assertDontSee($emp->matricule);
    }

    public function test_hr_can_toggle_attendance_tracking_from_employee_form(): void
    {
        $emp = Employee::active()->where('tracks_attendance', true)->firstOrFail();

        // Case décochée => seul le champ caché "0" est envoyé.
        $this->actingAs($this->rh())->put(route('admin.hr.employees.update', $emp), [
            'first_name' => $emp->first_name,
            'last_name' => $emp->last_name,
            'matricule' => $emp->matricule,
            'employment_status' => $emp->employment_status,
            'tracks_attendance' => '0',
        ])->assertRedirect();
        $this->assertFalse($emp->fresh()->tracks_attendance);

        // Case cochée => réactivé.
        $this->actingAs($this->rh())->put(route('admin.hr.employees.update', $emp), [
            'first_name' => $emp->first_name,
            'last_name' => $emp->last_name,
            'matricule' => $emp->matricule,
            'employment_status' => $emp->employment_status,
            'tracks_attendance' => '1',
        ])->assertRedirect();
        $this->assertTrue($emp->fresh()->tracks_attendance);
    }

    /* ---------------------------- Congés --------------------------- */

    public function test_paid_leave_approval_deducts_balance(): void
    {
        $emp = Employee::active()->firstOrFail();
        $emp->update(['leave_balance_days' => 20]);

        $leave = LeaveRequest::create([
            'hotel_id' => $emp->hotel_id, 'employee_id' => $emp->id, 'type' => 'conge_paye',
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeek()->addDays(4)->toDateString(),
            'days' => LeaveService::workingDays(now()->addWeek(), now()->addWeek()->addDays(4)),
            'status' => 'pending',
        ]);

        LeaveService::approve($leave);
        $this->assertSame('approved', $leave->fresh()->status);
        $this->assertEqualsWithDelta(20 - $leave->days, (float) $emp->fresh()->leave_balance_days, 0.01);

        LeaveService::cancel($leave->fresh());
        $this->assertEqualsWithDelta(20, (float) $emp->fresh()->leave_balance_days, 0.01);
    }

    public function test_sick_leave_does_not_touch_balance(): void
    {
        $emp = Employee::active()->firstOrFail();
        $emp->update(['leave_balance_days' => 12]);

        $leave = LeaveRequest::create([
            'hotel_id' => $emp->hotel_id, 'employee_id' => $emp->id, 'type' => 'maladie',
            'start_date' => now()->addDay()->toDateString(), 'end_date' => now()->addDays(3)->toDateString(),
            'days' => 3, 'status' => 'pending',
        ]);
        LeaveService::approve($leave);

        $this->assertEqualsWithDelta(12, (float) $emp->fresh()->leave_balance_days, 0.01);
    }

    /* --------------------------- Planning ------------------------- */

    public function test_schedule_flags_overlapping_shifts(): void
    {
        $emp = Employee::active()->firstOrFail();
        $date = today()->addDays(30);
        Shift::where('employee_id', $emp->id)->delete();

        $a = Shift::create(['hotel_id' => $emp->hotel_id, 'employee_id' => $emp->id, 'work_date' => $date->toDateString(), 'start_at' => $date->copy()->setTime(8, 0), 'end_at' => $date->copy()->setTime(16, 0), 'status' => 'planned']);
        $b = Shift::create(['hotel_id' => $emp->hotel_id, 'employee_id' => $emp->id, 'work_date' => $date->toDateString(), 'start_at' => $date->copy()->setTime(14, 0), 'end_at' => $date->copy()->setTime(22, 0), 'status' => 'planned']);

        $this->assertTrue($a->overlaps($b));

        $res = $this->actingAs($this->rh())->get(route('admin.hr.schedule', ['week' => $date->copy()->startOfWeek()->toDateString()]));
        $res->assertOk()->assertSee('conflit');
    }

    /* ----------------------------- Paie -------------------------- */

    public function test_payroll_generation_math_and_payment_posting(): void
    {
        $rh = $this->rh();
        $direction = User::where('role', 'direction')->first();
        $period = now()->subMonthsNoOverflow(2)->format('Y-m');

        // Employé propre pour un calcul déterministe.
        $emp = Employee::active()->first();
        $emp->update(['hire_date' => now()->subMonths(8), 'marital_status' => 'celibataire', 'dependents_count' => 0]);
        $emp->contracts()->update(['status' => 'ended']);
        $contract = $emp->contracts()->create([
            'type' => 'cdi', 'start_date' => now()->subYear()->toDateString(),
            'base_salary' => 260000, 'weekly_hours' => 40, 'status' => 'active',
        ]);
        $emp->salaryComponents()->delete();
        // BASE + IPRES 5.6% seulement.
        $base = SalaryComponent::where('code', 'BASE')->first();
        $ipres = SalaryComponent::where('code', 'IPRES')->first();
        $emp->salaryComponents()->create(['salary_component_id' => $base->id, 'amount' => 260000]);
        $emp->salaryComponents()->create(['salary_component_id' => $ipres->id, 'rate' => 5.6]);
        AttendanceLog::where('employee_id', $emp->id)->delete();
        SalaryAdvance::create(['hotel_id' => $emp->hotel_id, 'employee_id' => $emp->id, 'amount' => 30000, 'granted_on' => now()->toDateString(), 'status' => 'outstanding']);

        $run = $this->actingAs($rh)->post(route('admin.hr.payroll.store'), ['period' => $period]);
        $run = PayrollRun::where('period', $period)->firstOrFail();

        $this->actingAs($rh)->post(route('admin.hr.payroll.generate', $run))->assertRedirect();

        $slip = $run->payslips()->where('employee_id', $emp->id)->with('lines')->firstOrFail();
        $this->assertSame(260000, $slip->gross);
        $this->assertSame((int) round(260000 * 5.6 / 100), (int) $slip->lines->firstWhere('code', 'IPRES')->amount);
        $this->assertSame(30000, (int) $slip->lines->firstWhere('code', 'AVANCE')->amount);
        $this->assertSame($slip->gross - $slip->total_deductions, $slip->net);

        // Approbation direction puis paiement.
        $this->actingAs($direction)->post(route('admin.hr.payroll.approve', $run))->assertRedirect();
        $this->assertSame('approved', $run->fresh()->status);

        $this->actingAs($rh)->post(route('admin.hr.payroll.pay', $run))->assertRedirect();
        $run->refresh();
        $this->assertSame('paid', $run->status);

        $this->assertDatabaseHas('journal_entries', ['journal' => 'OD', 'label' => "Paie {$run->periodLabel()}"]);
        $this->assertDatabaseHas('finance_transactions', ['category' => 'salaires', 'amount' => $run->net_total]);
        $this->assertSame('repaid', SalaryAdvance::where('employee_id', $emp->id)->latest('id')->first()->status);

        $b = Accounting::trialBalance('2000-01-01', '2100-01-01');
        $this->assertSame($b->sum('debit'), $b->sum('credit'));
    }

    /* ----------------------------- RBAC -------------------------- */

    public function test_badge_scan_clocks_in_then_out_and_rejects_forged_url(): void
    {
        $emp = Employee::active()->where('tracks_attendance', true)->firstOrFail();
        AttendanceLog::where('employee_id', $emp->id)->whereDate('work_date', today())->delete();

        $signed = URL::signedRoute('clock.scan', ['employee' => $emp->matricule], null, false);

        // 1er scan = entrée.
        $this->getJson($signed)->assertOk()->assertJson(['ok' => true, 'action' => 'in', 'speech' => 'Bienvenue à l’hôtel !']);
        $this->assertDatabaseHas('attendance_logs', ['employee_id' => $emp->id, 'status' => 'open']);

        // 2e scan = sortie.
        $this->getJson($signed)->assertOk()->assertJson(['ok' => true, 'action' => 'out', 'speech' => 'À bientôt !']);

        // URL non signée / falsifiée rejetée.
        $this->get(route('clock.scan', ['employee' => $emp->matricule]))->assertForbidden();

        // Photo (avatar généré si aucune photo).
        $this->get(route('clock.photo', $emp->matricule))->assertOk();

        // QR SVG + planche de badges.
        $svg = $this->actingAs($this->rh())->get(route('admin.hr.employees.qr', $emp));
        $svg->assertOk();
        $this->assertStringContainsString('<svg', $svg->getContent());
        $this->actingAs($this->rh())->get(route('admin.hr.badges'))->assertOk()->assertSee($emp->matricule);
    }

    public function test_rbac_hr_scope(): void
    {
        $this->actingAs($this->rh())->get(route('admin.hr.dashboard'))->assertOk();
        $this->actingAs($this->rh())->get(route('admin.hr.employees.index'))->assertOk();
        $this->actingAs(User::where('role', 'housekeeping')->first())->get(route('admin.hr.dashboard'))->assertForbidden();
        // Un RH ne peut pas approuver une paie (réservé direction).
        $run = PayrollRun::first();
        $this->actingAs($this->rh())->post(route('admin.hr.payroll.approve', $run))->assertForbidden();
    }
}
