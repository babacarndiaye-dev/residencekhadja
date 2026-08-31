<?php

namespace Database\Seeders;

use App\Models\AttendanceLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Hotel;
use App\Models\JobPosition;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\PublicHoliday;
use App\Models\SalaryComponent;
use App\Models\Shift;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Services\LeaveService;
use App\Services\PayrollEngine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class WorkforceSeeder extends Seeder
{
    public function run(): void
    {
        $hotel = Hotel::current();

        User::updateOrCreate(['email' => 'rh@residence-khadija.sn'], [
            'name' => 'Ressources humaines', 'role' => 'rh', 'job_title' => 'Responsable RH',
            'hotel_id' => $hotel->id, 'is_active' => true, 'password' => Hash::make('khadija'),
        ]);

        $depts = [];
        foreach (config('hr.departments') as $d) {
            $depts[$d['code']] = Department::updateOrCreate(
                ['hotel_id' => $hotel->id, 'code' => $d['code']],
                ['name' => $d['name'], 'is_active' => true],
            );
        }

        // Jours fériés mobiles (fêtes religieuses — dates indicatives à ajuster).
        $year = now()->year;
        foreach ([
            "$year-03-31" => 'Korité (Aïd el-Fitr)',
            "$year-05-29" => 'Ascension',
            "$year-06-07" => 'Tabaski (Aïd el-Kébir)',
            "$year-06-09" => 'Lundi de Pentecôte',
            "$year-09-05" => 'Maouloud',
        ] as $date => $name) {
            PublicHoliday::updateOrCreate(['hotel_id' => $hotel->id, 'date' => $date], ['name' => $name]);
        }

        $positions = [];
        foreach (config('hr.positions') as $deptCode => $titles) {
            foreach ($titles as $title) {
                $positions[$deptCode][$title] = JobPosition::updateOrCreate(
                    ['hotel_id' => $hotel->id, 'department_id' => $depts[$deptCode]->id, 'title' => $title],
                    ['is_active' => true],
                );
            }
        }

        foreach (config('hr.shift_templates') as $t) {
            ShiftTemplate::updateOrCreate(
                ['hotel_id' => $hotel->id, 'name' => $t['name']],
                ['start_time' => $t['start'], 'end_time' => $t['end'], 'break_minutes' => $t['break'], 'color' => $t['color']],
            );
        }

        foreach (config('payroll.components') as $order => $c) {
            SalaryComponent::updateOrCreate(
                ['hotel_id' => $hotel->id, 'code' => $c['code']],
                [
                    'name' => $c['name'], 'kind' => $c['kind'], 'calc' => $c['calc'],
                    'is_taxable' => $c['taxable'] ?? false,
                    'applies_to_all' => $c['all'] ?? false,
                    'system_role' => $c['system'] ?? null,
                    'default_amount' => $c['default_amount'] ?? null,
                    'default_rate' => $c['default_rate'] ?? null,
                    'sort_order' => $order,
                ],
            );
        }

        if (Employee::where('hotel_id', $hotel->id)->exists()) {
            return;
        }

        $roster = [
            // [prénom, nom, genre, deptCode, fonction, salaire de base, user role à lier]
            ['Awa', 'Diallo', 'F', 'DIR', 'Directeur général', 950000, null],
            ['Mamadou', 'Ndiaye', 'M', 'FIN', 'Chef comptable', 520000, 'finance'],
            ['Fatou', 'Sarr', 'F', 'FO', 'Chef de réception', 380000, 'reception'],
            ['Cheikh', 'Fall', 'M', 'FO', 'Réceptionniste', 240000, null],
            ['Bineta', 'Gueye', 'F', 'FO', 'Réceptionniste', 235000, null],
            ['Ousseynou', 'Ba', 'M', 'FO', 'Night auditor', 250000, null],
            ['Aïssatou', 'Diop', 'F', 'HK', 'Gouvernante générale', 320000, 'housekeeping'],
            ['Ndeye', 'Sow', 'F', 'HK', 'Gouvernante d’étage', 230000, null],
            ['Rokhaya', 'Mbaye', 'F', 'HK', 'Femme de chambre', 170000, null],
            ['Coumba', 'Faye', 'F', 'HK', 'Femme de chambre', 170000, null],
            ['Dieynaba', 'Cissé', 'F', 'HK', 'Femme de chambre', 170000, null],
            ['Astou', 'Ndour', 'F', 'HK', 'Lingère', 175000, null],
            ['Ibrahima', 'Diouf', 'M', 'FB', 'Chef de cuisine', 480000, null],
            ['Modou', 'Kane', 'M', 'FB', 'Sous-chef', 300000, null],
            ['Alioune', 'Seck', 'M', 'FB', 'Commis de cuisine', 165000, null],
            ['Pape', 'Thiam', 'M', 'FB', 'Commis de cuisine', 165000, null],
            ['Khady', 'Sy', 'F', 'FB', 'Chef de rang', 200000, 'restaurant'],
            ['Serigne', 'Lo', 'M', 'FB', 'Serveur', 160000, null],
            ['Moussa', 'Camara', 'M', 'FB', 'Barman', 190000, null],
            ['Babacar', 'Diagne', 'M', 'FB', 'Plongeur', 150000, null],
            ['Abdou', 'Dieng', 'M', 'TECH', 'Technicien polyvalent', 260000, 'maintenance'],
            ['Malick', 'Sène', 'M', 'TECH', 'Électricien', 240000, null],
            ['Oumar', 'Barry', 'M', 'ECO', 'Magasinier', 210000, 'stock'],
            ['Mareme', 'Wade', 'F', 'COM', 'Chargé de clientèle', 300000, null],
            ['Assane', 'Gomis', 'M', 'SEC', 'Chef de sécurité', 240000, null],
            ['Lamine', 'Toure', 'M', 'SEC', 'Agent de sécurité', 150000, null],
        ];

        $i = 100;
        foreach ($roster as [$first, $last, $gender, $dept, $posTitle, $base, $userRole]) {
            $matricule = 'RK'.str_pad((string) $i++, 4, '0', STR_PAD_LEFT);
            $user = $userRole ? User::where('role', $userRole)->first() : null;

            $employee = Employee::create([
                'hotel_id' => $hotel->id,
                'department_id' => $depts[$dept]->id,
                'job_position_id' => $positions[$dept][$posTitle]->id ?? null,
                'user_id' => $user?->id,
                'matricule' => $matricule,
                'civility' => $gender === 'F' ? 'Mme' : 'M.',
                'first_name' => $first,
                'last_name' => $last,
                'gender' => $gender,
                'marital_status' => ['celibataire', 'marie', 'marie', 'divorce'][$i % 4],
                'dependents_count' => $i % 3 === 0 ? random_int(1, 4) : 0,
                'phone' => '+221 77 '.random_int(100, 999).' '.random_int(10, 99).' '.random_int(10, 99),
                'hire_date' => now()->subMonths(random_int(3, 60)),
                'employment_status' => 'active',
                'pin_hash' => Hash::make(substr($matricule, -4)),
                // Le DG (cadre dirigeant) n'est pas soumis au pointage.
                'tracks_attendance' => $posTitle !== 'Directeur général',
                'leave_balance_days' => random_int(4, 26),
            ]);

            $contract = $employee->contracts()->create([
                'type' => $i % 4 === 0 ? 'cdd' : 'cdi',
                'start_date' => $employee->hire_date,
                'end_date' => $i % 4 === 0 ? now()->addMonths(random_int(1, 8)) : null,
                'base_salary' => $base,
                'weekly_hours' => 40,
                'status' => 'active',
            ]);

            foreach (SalaryComponent::where('hotel_id', $hotel->id)->get() as $c) {
                if (! $c->applies_to_all && $c->system_role !== 'base') {
                    continue;
                }
                $employee->salaryComponents()->create([
                    'salary_component_id' => $c->id,
                    'amount' => $c->system_role === 'base' ? $base : ($c->calc === 'fixed' ? $c->default_amount : null),
                    'rate' => $c->calc === 'percent_base' ? $c->default_rate : null,
                ]);
            }
        }

        $this->seedSchedule($hotel);
        $this->seedAttendanceAndPayroll($hotel);
        $this->seedLeave($hotel);
    }

    private function seedSchedule(Hotel $hotel): void
    {
        $monday = now()->startOfWeek(Carbon::MONDAY);
        $templates = ShiftTemplate::where('hotel_id', $hotel->id)->get()->keyBy('name');
        $employees = Employee::where('hotel_id', $hotel->id)->get();

        foreach ($employees as $emp) {
            $tpl = $templates[['Matin', 'Journée', 'Après-midi', 'Nuit'][$emp->id % 4]];
            foreach (range(0, 4) as $d) {
                $date = $monday->copy()->addDays($d);
                $start = $date->copy()->setTimeFromTimeString($tpl->start_time);
                $end = $date->copy()->setTimeFromTimeString($tpl->end_time);
                if ($end->lte($start)) {
                    $end->addDay();
                }
                Shift::create([
                    'hotel_id' => $hotel->id, 'employee_id' => $emp->id, 'department_id' => $emp->department_id,
                    'shift_template_id' => $tpl->id, 'work_date' => $date->toDateString(),
                    'start_at' => $start, 'end_at' => $end, 'break_minutes' => $tpl->break_minutes, 'status' => 'planned',
                ]);
            }
        }
    }

    private function seedAttendanceAndPayroll(Hotel $hotel): void
    {
        $period = now()->subMonthNoOverflow();
        $start = $period->copy()->startOfMonth();
        $end = $period->copy()->endOfMonth();
        $templates = ShiftTemplate::where('hotel_id', $hotel->id)->get()->keyBy('name');
        $employees = Employee::where('hotel_id', $hotel->id)->get();

        foreach ($employees as $emp) {
            $tpl = $templates[['Matin', 'Journée', 'Après-midi', 'Nuit'][$emp->id % 4]];
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                if ($d->isSunday()) {
                    continue;
                }
                // ~6 % d'absences
                if (random_int(1, 100) <= 6) {
                    AttendanceLog::create([
                        'hotel_id' => $hotel->id, 'employee_id' => $emp->id, 'work_date' => $d->toDateString(),
                        'status' => 'absent', 'source' => 'admin', 'worked_minutes' => 0,
                    ]);

                    continue;
                }
                $in = $d->copy()->setTimeFromTimeString($tpl->start_time)->addMinutes(random_int(-5, 25));
                $overtime = random_int(1, 100) <= 25 ? random_int(20, 90) : 0;
                $out = $in->copy()->addMinutes($tpl->durationMinutes() + $tpl->break_minutes + $overtime);
                $worked = max(0, (int) $in->diffInMinutes($out) - $tpl->break_minutes);

                AttendanceLog::create([
                    'hotel_id' => $hotel->id, 'employee_id' => $emp->id, 'work_date' => $d->toDateString(),
                    'clock_in' => $in, 'clock_out' => $out, 'source' => 'borne', 'status' => 'closed',
                    'worked_minutes' => $worked,
                    'late_minutes' => max(0, (int) $d->copy()->setTimeFromTimeString($tpl->start_time)->diffInMinutes($in, false) - 5),
                    'overtime_minutes' => $overtime,
                ]);
            }
        }

        $run = PayrollRun::create([
            'hotel_id' => $hotel->id,
            'period' => $period->format('Y-m'),
            'label' => 'Paie '.$period->locale('fr')->isoFormat('MMMM YYYY'),
            'status' => 'draft',
        ]);
        PayrollEngine::generate($run);
    }

    private function seedLeave(Hotel $hotel): void
    {
        $employees = Employee::where('hotel_id', $hotel->id)->inRandomOrder()->take(4)->get();

        foreach ($employees as $k => $emp) {
            $startsIn = [3, 10, -2, 20][$k];
            $s = now()->addDays($startsIn);
            $e = $s->copy()->addDays(random_int(2, 6));
            LeaveRequest::create([
                'hotel_id' => $hotel->id, 'employee_id' => $emp->id,
                'type' => ['conge_paye', 'maladie', 'conge_paye', 'sans_solde'][$k],
                'start_date' => $s->toDateString(), 'end_date' => $e->toDateString(),
                'days' => LeaveService::workingDays($s, $e),
                'reason' => 'Demande de démonstration',
                'status' => 'pending',
            ]);
        }
    }
}
