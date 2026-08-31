<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\Hotel;
use App\Models\JobPosition;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\PublicHoliday;
use App\Models\Training;
use App\Services\HrAlerts;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class HrController extends Controller
{
    public function dashboard()
    {
        return view('admin.hr.dashboard', [
            'headcount' => Employee::active()->count(),
            'byDepartment' => Employee::active()->selectRaw('department_id, count(*) as t')->groupBy('department_id')->with('department')->get(),
            'alerts' => HrAlerts::collect(),
            'pendingLeave' => LeaveRequest::pending()->with('employee')->latest()->get(),
            'lastRun' => PayrollRun::latest('period')->first(),
        ]);
    }

    /* ---------------------- Services & fonctions ------------------- */

    public function departments()
    {
        return view('admin.hr.departments', [
            'departments' => Department::with('positions')->withCount('employees')->orderBy('name')->get(),
            'holidays' => PublicHoliday::where('hotel_id', Hotel::current()->id)
                ->whereDate('date', '>=', now()->startOfYear())->orderBy('date')->get(),
        ]);
    }

    public function storeHoliday(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:120'],
        ]);

        PublicHoliday::updateOrCreate(
            ['hotel_id' => Hotel::current()->id, 'date' => Carbon::parse($data['date'])->toDateString()],
            ['name' => $data['name']],
        );

        return back()->with('status', 'Jour férié enregistré.');
    }

    public function destroyHoliday(PublicHoliday $holiday)
    {
        $holiday->delete();

        return back()->with('status', 'Jour férié supprimé.');
    }

    public function storeDepartment(Request $request)
    {
        Department::create($request->validate([
            'name' => ['required', 'string', 'max:80'],
            'code' => ['nullable', 'string', 'max:12'],
        ]) + ['hotel_id' => Hotel::current()->id]);

        return back()->with('status', 'Service créé.');
    }

    public function storePosition(Request $request)
    {
        $data = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'title' => ['required', 'string', 'max:80'],
        ]);

        JobPosition::create($data + ['hotel_id' => Hotel::current()->id]);

        return back()->with('status', 'Fonction ajoutée.');
    }

    /* --------------------------- Formations ----------------------- */

    public function trainings()
    {
        return view('admin.hr.trainings', [
            'trainings' => Training::with('participants')->latest('starts_on')->get(),
            'employees' => Employee::active()->orderBy('last_name')->get(),
        ]);
    }

    public function storeTraining(Request $request)
    {
        $t = Training::create($request->validate([
            'title' => ['required', 'string', 'max:120'],
            'provider' => ['nullable', 'string', 'max:120'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date'],
            'cost' => ['nullable', 'integer', 'min:0'],
        ]) + ['hotel_id' => Hotel::current()->id]);

        $t->participants()->sync($request->input('employees', []));

        return back()->with('status', 'Formation enregistrée.');
    }

    public function updateTraining(Request $request, Training $training)
    {
        $training->update($request->validate([
            'status' => ['required', Rule::in(['planned', 'done', 'cancelled'])],
        ]));
        $training->participants()->sync($request->input('employees', $training->participants->pluck('id')->all()));

        return back()->with('status', 'Formation mise à jour.');
    }

    /* -------------------------- Évaluations ----------------------- */

    public function storeEvaluation(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'period_label' => ['required', 'string', 'max:40'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'strengths' => ['nullable', 'string', 'max:1000'],
            'improvements' => ['nullable', 'string', 'max:1000'],
            'objectives' => ['nullable', 'string', 'max:1000'],
            'finalize' => ['nullable', 'boolean'],
        ]);

        Evaluation::create([
            'hotel_id' => $employee->hotel_id,
            'employee_id' => $employee->id,
            'evaluator_id' => $request->user()->id,
            'period_label' => $data['period_label'],
            'score' => $data['score'] ?? null,
            'strengths' => $data['strengths'] ?? null,
            'improvements' => $data['improvements'] ?? null,
            'objectives' => $data['objectives'] ?? null,
            'status' => $request->boolean('finalize') ? 'finalized' : 'draft',
            'finalized_at' => $request->boolean('finalize') ? now() : null,
        ]);

        return back()->with('status', 'Évaluation enregistrée.');
    }
}
