<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Hotel;
use App\Models\SalaryAdvance;
use App\Models\SalaryComponent;
use App\Support\Notify;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'department' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(array_keys(config('hr.employment_statuses')))],
            'q' => ['nullable', 'string', 'max:60'],
        ]);

        $employees = Employee::query()
            ->with(['department', 'position'])
            ->when($filters['department'] ?? null, fn ($q, $v) => $q->where('department_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('employment_status', $v))
            ->when($filters['q'] ?? null, fn ($q, $t) => $q->where(fn ($s) => $s
                ->where('first_name', 'like', "%{$t}%")->orWhere('last_name', 'like', "%{$t}%")->orWhere('matricule', 'like', "%{$t}%")))
            ->orderBy('last_name')
            ->paginate(30)->withQueryString();

        return view('admin.hr.employees.index', [
            'employees' => $employees,
            'filters' => $filters,
            'departments' => Department::orderBy('name')->get(),
            'statuses' => config('hr.employment_statuses'),
        ]);
    }

    public function create()
    {
        return view('admin.hr.employees.form', [
            'employee' => new Employee(['employment_status' => 'active']),
            'departments' => Department::with('positions')->orderBy('name')->get(),
            'contractTypes' => config('hr.contract_types'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateEmployee($request);
        $data['hotel_id'] = Hotel::current()->id;
        $data['matricule'] = $data['matricule'] ?: 'EMP-'.Str::upper(Str::random(5));

        $employee = Employee::create($data + [
            'leave_balance_days' => 0,
        ]);

        // Contrat initial optionnel.
        if ($request->filled('base_salary')) {
            $contract = $employee->contracts()->create([
                'type' => $request->input('contract_type', 'cdi'),
                'start_date' => $request->input('contract_start', $employee->hire_date ?? now()),
                'end_date' => $request->input('contract_end'),
                'base_salary' => (int) $request->input('base_salary'),
                'weekly_hours' => (int) $request->input('weekly_hours', 40),
                'status' => 'active',
            ]);
            $this->applyDefaultComponents($employee, $contract->base_salary);
        }

        AuditLog::record('hr.employee.created', $employee);

        return redirect()->route('admin.hr.employees.show', $employee)->with('status', 'Employé créé.');
    }

    public function show(Employee $employee)
    {
        $employee->load([
            'department', 'position', 'user', 'contracts', 'documents',
            'salaryComponents.component', 'advances', 'leaveRequests',
            'payslips.run',
        ]);

        return view('admin.hr.employees.show', [
            'employee' => $employee,
            'departments' => Department::with('positions')->orderBy('name')->get(),
            'contractTypes' => config('hr.contract_types'),
            'documentCategories' => config('hr.document_categories'),
            'components' => SalaryComponent::orderBy('sort_order')->get(),
            'maritalStatuses' => config('hr.marital_statuses'),
            'terminationReasons' => config('hr.termination_reasons'),
            'attestationTypes' => ['travail' => 'Attestation de travail', 'salaire' => 'Attestation de salaire', 'fin_contrat' => 'Certificat de fin de contrat'],
        ]);
    }

    /** Sortie de salarié : clôt le contrat, les shifts à venir, désactive le compte. */
    public function terminate(Request $request, Employee $employee)
    {
        abort_if($employee->isTerminated(), 422, 'Ce salarié est déjà sorti des effectifs.');

        $data = $request->validate([
            'termination_date' => ['required', 'date'],
            'termination_type' => ['required', Rule::in(array_keys(config('hr.termination_reasons')))],
            'notice_end_date' => ['nullable', 'date', 'after_or_equal:termination_date'],
            'termination_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $termDate = Carbon::parse($data['termination_date'])->toDateString();

        DB::transaction(function () use ($employee, $data, $termDate) {
            $employee->update($data + ['employment_status' => 'terminated']);

            $employee->contracts()->where('status', 'active')->get()->each(fn ($c) => $c->update([
                'status' => 'ended',
                'end_date' => $c->end_date ?? $termDate,
            ]));

            $employee->shifts()->whereDate('work_date', '>', $termDate)
                ->update(['status' => 'cancelled']);

            $employee->leaveRequests()->where('status', 'pending')->update([
                'status' => 'cancelled',
                'decision_note' => 'Sortie du salarié',
            ]);

            $employee->user?->update(['is_active' => false]);
        });

        AuditLog::record('hr.employee.terminated', $employee, ['type' => $data['termination_type']]);
        Notify::roles(['rh', 'direction'], 'Sortie de salarié',
            $employee->fullName().' — '.($employee->terminationLabel() ?? $data['termination_type']),
            route('admin.hr.employees.show', $employee, false), icon: '👋');

        return back()->with('status', "{$employee->fullName()} est sorti(e) des effectifs.");
    }

    /** Attestation imprimable (travail / salaire / fin de contrat). */
    public function attestation(Employee $employee, string $type)
    {
        abort_unless(in_array($type, ['travail', 'salaire', 'fin_contrat'], true), 404);
        $employee->load(['department', 'position', 'contracts', 'payslips.run']);

        return view('admin.hr.attestations.document', [
            'employee' => $employee,
            'type' => $type,
            'contract' => $employee->activeContract() ?? $employee->contracts->first(),
            'lastPayslip' => $employee->payslips->sortByDesc(fn ($p) => $p->run?->period)->first(),
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $this->validateEmployee($request, $employee);
        $data['tracks_attendance'] = $request->boolean('tracks_attendance');

        $employee->update($data);
        AuditLog::record('hr.employee.updated', $employee);

        return back()->with('status', 'Fiche mise à jour.');
    }

    public function storeContract(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(config('hr.contract_types')))],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'base_salary' => ['required', 'integer', 'min:0'],
            'weekly_hours' => ['required', 'integer', 'min:1', 'max:60'],
            'trial_period_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'note' => ['nullable', 'string', 'max:400'],
        ]);

        $employee->contracts()->where('status', 'active')->update(['status' => 'ended']);
        $contract = $employee->contracts()->create($data + ['status' => 'active']);

        if ($employee->salaryComponents()->doesntExist()) {
            $this->applyDefaultComponents($employee, $contract->base_salary);
        }

        return back()->with('status', 'Contrat enregistré.');
    }

    public function uploadDocument(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(config('hr.document_categories')))],
            'title' => ['required', 'string', 'max:120'],
            'file' => ['required', 'file', 'max:8192', 'mimes:pdf,jpg,jpeg,png,webp'],
            'issued_on' => ['nullable', 'date'],
            'expires_on' => ['nullable', 'date'],
        ]);

        $path = $request->file('file')->store("hr/{$employee->id}", 'local');

        $employee->documents()->create([
            'uploaded_by' => $request->user()->id,
            'category' => $data['category'],
            'title' => $data['title'],
            'file_path' => $path,
            'issued_on' => $data['issued_on'] ?? null,
            'expires_on' => $data['expires_on'] ?? null,
        ]);

        return back()->with('status', 'Document ajouté.');
    }

    public function downloadDocument(EmployeeDocument $document)
    {
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->title);
    }

    /** QR code SVG du badge de pointage — chemin signé *relatif* : le badge reste
     *  valable quelle que soit l'adresse de la borne (IP WiFi, proxy HTTPS, port…). */
    public function qrSvg(Employee $employee)
    {
        $result = (new Builder(
            writer: new SvgWriter,
            data: URL::signedRoute('clock.scan', ['employee' => $employee->matricule], null, false),
            size: 240,
            margin: 4,
        ))->build();

        return response($result->getString(), 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-store',
        ]);
    }

    /** Carte professionnelle d'un seul agent, prête à imprimer (format CR80). */
    public function card(Employee $employee)
    {
        $employee->loadMissing(['department', 'position']);

        return view('admin.hr.badge-single', ['e' => $employee]);
    }

    /** Planche de badges QR à imprimer. */
    public function badges(Request $request)
    {
        $employees = Employee::active()
            ->with(['department', 'position'])
            ->when($request->integer('department'), fn ($q, $v) => $q->where('department_id', $v))
            ->orderBy('last_name')->get();

        return view('admin.hr.badges', [
            'employees' => $employees,
            'departments' => Department::orderBy('name')->get(),
            'activeDepartment' => $request->integer('department') ?: null,
        ]);
    }

    public function setPin(Request $request, Employee $employee)
    {
        $data = $request->validate(['pin' => ['required', 'digits_between:4,6']]);
        $employee->update(['pin_hash' => Hash::make($data['pin'])]);
        AuditLog::record('hr.employee.pin_set', $employee);

        return back()->with('status', "Code de pointage défini pour {$employee->fullName()}.");
    }

    public function uploadPhoto(Request $request, Employee $employee)
    {
        $request->validate(['photo' => ['required', 'image', 'max:4096']]);

        if ($employee->photo_path) {
            Storage::disk('local')->delete($employee->photo_path);
        }

        $employee->update([
            'photo_path' => $request->file('photo')->store("hr/{$employee->id}", 'local'),
        ]);

        return back()->with('status', 'Photo mise à jour.');
    }

    public function saveComponents(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'components' => ['array'],
            'components.*.enabled' => ['nullable', 'boolean'],
            'components.*.amount' => ['nullable', 'integer', 'min:0'],
            'components.*.rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        foreach (SalaryComponent::all() as $c) {
            $row = $data['components'][$c->id] ?? [];
            $enabled = ! empty($row['enabled']) || $c->applies_to_all || $c->system_role === 'base';

            if (! $enabled) {
                $employee->salaryComponents()->where('salary_component_id', $c->id)->delete();

                continue;
            }

            $employee->salaryComponents()->updateOrCreate(
                ['salary_component_id' => $c->id],
                [
                    'amount' => $c->calc === 'fixed' ? ($row['amount'] ?? $c->default_amount) : null,
                    'rate' => $c->calc === 'percent_base' ? ($row['rate'] ?? $c->default_rate) : null,
                ],
            );
        }

        return back()->with('status', 'Rémunération mise à jour.');
    }

    public function storeAdvance(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'granted_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        SalaryAdvance::create($data + [
            'hotel_id' => $employee->hotel_id,
            'employee_id' => $employee->id,
            'granted_by' => $request->user()->id,
            'status' => 'outstanding',
        ]);

        return back()->with('status', 'Avance enregistrée (sera retenue sur la prochaine paie).');
    }

    /* ---------------------------- Helpers ------------------------- */

    private function validateEmployee(Request $request, ?Employee $employee = null): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'civility' => ['nullable', 'in:Mme,M.,Autre'],
            'matricule' => ['nullable', 'string', 'max:30', Rule::unique('employees', 'matricule')->ignore($employee?->id)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'job_position_id' => ['nullable', 'exists:job_positions,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:F,M,Autre'],
            'marital_status' => ['nullable', Rule::in(array_keys(config('hr.marital_statuses')))],
            'dependents_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string', 'max:200'],
            'national_id' => ['nullable', 'string', 'max:40'],
            'hire_date' => ['nullable', 'date'],
            'employment_status' => ['required', Rule::in(array_keys(config('hr.employment_statuses')))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function applyDefaultComponents(Employee $employee, int $base): void
    {
        foreach (SalaryComponent::all() as $c) {
            if (! $c->applies_to_all && $c->system_role !== 'base') {
                continue;
            }
            $employee->salaryComponents()->firstOrCreate(
                ['salary_component_id' => $c->id],
                [
                    'amount' => $c->system_role === 'base' ? $base : ($c->calc === 'fixed' ? $c->default_amount : null),
                    'rate' => $c->calc === 'percent_base' ? $c->default_rate : null,
                ],
            );
        }
    }
}
