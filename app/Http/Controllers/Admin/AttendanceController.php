<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\Shift;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = Carbon::parse($request->query('date', now()));

        // Seuls les employés soumis au pointage apparaissent sur cet écran.
        $tracked = fn ($q) => $q->where('tracks_attendance', true);

        $shifts = Shift::whereDate('work_date', $date)->where('status', '!=', 'cancelled')
            ->whereHas('employee', $tracked)
            ->with('employee')->get()->keyBy('employee_id');

        $logs = AttendanceLog::forDate($date)->whereHas('employee', $tracked)
            ->with('employee', 'shift')->get()->keyBy('employee_id');

        // Employés attendus = ceux planifiés + ceux ayant déjà pointé.
        $employeeIds = $shifts->keys()->merge($logs->keys())->unique();
        $employees = Employee::whereIn('id', $employeeIds)->tracksAttendance()->orderBy('last_name')->get();

        return view('admin.hr.attendance', [
            'date' => $date,
            'employees' => $employees,
            'shifts' => $shifts,
            'logs' => $logs,
            'allEmployees' => Employee::active()->tracksAttendance()->orderBy('last_name')->get(),
            'summary' => [
                'present' => $logs->whereNotNull('clock_in')->count(),
                'absent' => $logs->where('status', 'absent')->count(),
                'late' => $logs->where('late_minutes', '>', 0)->count(),
                'planned' => $shifts->count(),
            ],
        ]);
    }

    public function clock(Request $request, Employee $employee)
    {
        $data = $request->validate(['at' => ['nullable', 'date']]);

        AttendanceService::clock($employee, 'admin', $data['at'] ?? null ? Carbon::parse($data['at']) : null);

        return back()->with('status', "Pointage enregistré pour {$employee->fullName()}.");
    }

    public function markAbsent(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:150'],
        ]);

        AttendanceService::markAbsent($employee, Carbon::parse($data['date'])->toDateString(), $data['note'] ?? null);

        return back()->with('status', "{$employee->fullName()} marqué absent.");
    }

    public function correct(Request $request, AttendanceLog $log)
    {
        $data = $request->validate([
            'clock_in' => ['nullable', 'date'],
            'clock_out' => ['nullable', 'date', 'after:clock_in'],
            'note' => ['nullable', 'string', 'max:150'],
        ]);

        $in = $data['clock_in'] ? Carbon::parse($data['clock_in']) : $log->clock_in;
        $out = $data['clock_out'] ? Carbon::parse($data['clock_out']) : $log->clock_out;

        $worked = $in && $out ? max(0, (int) $in->diffInMinutes($out) - ($log->shift->break_minutes ?? 0)) : 0;
        $expected = $log->shift?->plannedMinutes() ?? config('payroll.hours_per_day') * 60;

        $log->update([
            'clock_in' => $in,
            'clock_out' => $out,
            'status' => $out ? 'closed' : 'open',
            'worked_minutes' => $worked,
            'overtime_minutes' => max(0, $worked - (int) round($expected)),
            'late_minutes' => $log->shift && $in ? max(0, (int) $log->shift->start_at->diffInMinutes($in, false) - config('hr.clock.grace_minutes', 5)) : 0,
            'source' => 'admin',
            'corrected_by' => $request->user()->id,
            'note' => $data['note'] ?? $log->note,
        ]);

        return back()->with('status', 'Pointage corrigé.');
    }
}
