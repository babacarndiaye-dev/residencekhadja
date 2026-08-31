<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\Shift;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Pointage entrée / sortie (§44). Le calcul des retards, heures
 * supplémentaires et du temps travaillé se fait par rapport au shift
 * planifié (Phase 9 planning) ou, à défaut, au contrat.
 */
class AttendanceService
{
    /** Pointage borne : matricule + code PIN. */
    public static function clockByPin(string $matricule, string $pin): array
    {
        $employee = Employee::active()->where('matricule', $matricule)->first();

        if (! $employee || ! $employee->pin_hash || ! Hash::check($pin, $employee->pin_hash)) {
            throw ValidationException::withMessages(['pin' => 'Matricule ou code incorrect.']);
        }

        return self::clock($employee, 'borne');
    }

    /** Pointage (borne ou correction admin). */
    public static function clock(Employee $employee, string $source = 'admin', ?Carbon $at = null): array
    {
        if (! $employee->tracks_attendance) {
            throw ValidationException::withMessages([
                'pin' => $employee->first_name.' n’est pas soumis au pointage.',
            ]);
        }

        $at = $at ?: now();
        $date = $at->copy()->toDateString();

        $log = AttendanceLog::where('employee_id', $employee->id)->whereDate('work_date', $date)->first()
            ?? new AttendanceLog(['employee_id' => $employee->id, 'work_date' => $date]);

        if (! $log->exists || $log->clock_in === null) {
            $shift = self::matchShift($employee, $at);

            $log->fill([
                'hotel_id' => $employee->hotel_id,
                'shift_id' => $shift?->id,
                'clock_in' => $at,
                'source' => $source,
                'status' => 'open',
                'corrected_by' => $source === 'admin' ? Auth::id() : null,
                'late_minutes' => $shift ? self::lateMinutes($shift, $at) : 0,
            ]);
            $log->save();

            return ['action' => 'in', 'employee' => $employee, 'log' => $log];
        }

        if ($log->clock_out === null) {
            $shift = $log->shift;
            $worked = max(0, (int) $log->clock_in->diffInMinutes($at) - ($shift->break_minutes ?? 0));
            $expected = $shift?->plannedMinutes()
                ?? ($employee->activeContract()?->weekly_hours ?? 40) / 5 * 60;

            $log->fill([
                'clock_out' => $at,
                'status' => 'closed',
                'worked_minutes' => $worked,
                'overtime_minutes' => max(0, $worked - (int) round($expected)),
                'corrected_by' => $source === 'admin' ? Auth::id() : $log->corrected_by,
            ]);
            $log->save();

            return ['action' => 'out', 'employee' => $employee, 'log' => $log];
        }

        throw ValidationException::withMessages(['pin' => 'Vous avez déjà pointé entrée et sortie aujourd’hui.']);
    }

    /** Marque un employé absent pour une date (aucun pointage sur un shift planifié). */
    public static function markAbsent(Employee $employee, string $date, ?string $note = null): AttendanceLog
    {
        if (! $employee->tracks_attendance) {
            throw ValidationException::withMessages([
                'date' => $employee->first_name.' n’est pas soumis au pointage.',
            ]);
        }

        $log = AttendanceLog::where('employee_id', $employee->id)->whereDate('work_date', $date)->first()
            ?? new AttendanceLog(['employee_id' => $employee->id, 'work_date' => $date]);
        $log->fill([
            'hotel_id' => $employee->hotel_id,
            'shift_id' => self::matchShift($employee, Carbon::parse($date))?->id,
            'status' => 'absent',
            'source' => 'admin',
            'worked_minutes' => 0,
            'corrected_by' => Auth::id(),
            'note' => $note,
        ]);
        $log->save();

        return $log;
    }

    private static function matchShift(Employee $employee, Carbon $at): ?Shift
    {
        return Shift::where('employee_id', $employee->id)
            ->whereDate('work_date', $at->toDateString())
            ->where('status', '!=', 'cancelled')
            ->get()
            ->sortBy(fn ($s) => abs($s->start_at->diffInMinutes($at, false)))
            ->first();
    }

    private static function lateMinutes(Shift $shift, Carbon $at): int
    {
        $grace = (int) config('hr.clock.grace_minutes', 5);
        $diff = (int) $shift->start_at->diffInMinutes($at, false);

        return max(0, $diff - $grace);
    }
}
