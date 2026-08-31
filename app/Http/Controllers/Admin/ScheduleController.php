<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Hotel;
use App\Models\Shift;
use App\Models\ShiftTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $monday = Carbon::parse($request->query('week', now()))->startOfWeek(Carbon::MONDAY);
        $departmentId = $request->integer('department') ?: null;

        $employees = Employee::active()
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->orderBy('last_name')->get();

        $shifts = Shift::forWeek($monday)
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->with('template', 'replacement')
            ->get()
            ->groupBy(fn ($s) => $s->employee_id.'|'.$s->work_date->toDateString());

        // Détection de conflits (chevauchement même employé même jour).
        $conflicts = [];
        foreach ($shifts as $key => $group) {
            if ($group->count() < 2) {
                continue;
            }
            foreach ($group as $a) {
                foreach ($group as $b) {
                    if ($a->overlaps($b)) {
                        $conflicts[$key] = true;
                    }
                }
            }
        }

        return view('admin.hr.schedule', [
            'monday' => $monday,
            'days' => collect(range(0, 6))->map(fn ($i) => $monday->copy()->addDays($i)),
            'employees' => $employees,
            'shifts' => $shifts,
            'conflicts' => $conflicts,
            'templates' => ShiftTemplate::orderBy('start_time')->get(),
            'departments' => Department::orderBy('name')->get(),
            'activeDepartment' => $departmentId,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'work_date' => ['required', 'date'],
            'shift_template_id' => ['nullable', 'exists:shift_templates,id'],
            'start' => ['required', 'date_format:H:i'],
            'end' => ['required', 'date_format:H:i'],
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:240'],
            'note' => ['nullable', 'string', 'max:120'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $date = Carbon::parse($data['work_date']);
        $startAt = $date->copy()->setTimeFromTimeString($data['start']);
        $endAt = $date->copy()->setTimeFromTimeString($data['end']);
        if ($endAt->lte($startAt)) {
            $endAt->addDay();
        }

        Shift::create([
            'hotel_id' => Hotel::current()->id,
            'employee_id' => $employee->id,
            'department_id' => $employee->department_id,
            'shift_template_id' => $data['shift_template_id'] ?? null,
            'work_date' => $date->toDateString(),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'break_minutes' => $data['break_minutes'] ?? 0,
            'status' => 'planned',
            'note' => $data['note'] ?? null,
        ]);

        return back()->with('status', 'Shift ajouté.');
    }

    public function destroy(Shift $shift)
    {
        $shift->delete();

        return back()->with('status', 'Shift supprimé.');
    }

    public function replace(Request $request, Shift $shift)
    {
        $data = $request->validate([
            'replacement_employee_id' => ['required', 'exists:employees,id', 'different:employee_id'],
        ]);

        $shift->update([
            'replacement_employee_id' => $data['replacement_employee_id'],
            'status' => 'swapped',
        ]);

        return back()->with('status', 'Remplacement enregistré.');
    }

    /** Duplique les shifts d'une semaine sur la suivante. */
    public function duplicateWeek(Request $request)
    {
        $from = Carbon::parse($request->input('week'))->startOfWeek(Carbon::MONDAY);
        $to = $from->copy()->addWeek();

        $count = 0;
        DB::transaction(function () use ($from, &$count) {
            foreach (Shift::forWeek($from)->where('status', '!=', 'cancelled')->get() as $s) {
                $shift = $s->replicate(['id', 'created_at', 'updated_at']);
                $shift->work_date = Carbon::parse($s->work_date)->addWeek()->toDateString();
                $shift->start_at = $s->start_at->copy()->addWeek();
                $shift->end_at = $s->end_at->copy()->addWeek();
                $shift->status = 'planned';
                $shift->replacement_employee_id = null;
                $shift->save();
                $count++;
            }
        });

        return redirect()->route('admin.hr.schedule', ['week' => $to->toDateString()])
            ->with('status', "{$count} shift(s) copié(s) sur la semaine du ".$to->format('d/m').'.');
    }
}
