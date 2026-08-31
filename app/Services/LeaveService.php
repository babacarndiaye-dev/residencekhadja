<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\PublicHoliday;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeaveService
{
    /** Jours ouvrables entre deux dates incluses (hors dimanche et jours fériés). */
    public static function workingDays($start, $end): int
    {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay();

        if ($end->lt($start)) {
            return 0;
        }

        $holidays = PublicHoliday::map($start, $end);

        $days = 0;
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if (! $d->isSunday() && ! isset($holidays[$d->toDateString()])) {
                $days++;
            }
        }

        return $days;
    }

    public static function approve(LeaveRequest $leave): void
    {
        abort_unless($leave->status === 'pending', 422);

        DB::transaction(function () use ($leave) {
            $leave->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            if ($leave->typeConfig()['deducts_balance']) {
                $leave->employee->decrement('leave_balance_days', (float) $leave->days);
            }

            // Bloque le salarié en congé si la période couvre aujourd'hui.
            if (now()->between($leave->start_date, $leave->end_date->copy()->endOfDay())) {
                $leave->employee->update(['employment_status' => 'on_leave']);
            }
        });
    }

    public static function reject(LeaveRequest $leave, ?string $note = null): void
    {
        abort_unless($leave->status === 'pending', 422);

        $leave->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'decision_note' => $note,
        ]);
    }

    public static function cancel(LeaveRequest $leave): void
    {
        $wasApproved = $leave->status === 'approved';

        DB::transaction(function () use ($leave, $wasApproved) {
            $leave->update(['status' => 'cancelled']);

            if ($wasApproved && $leave->typeConfig()['deducts_balance']) {
                $leave->employee->increment('leave_balance_days', (float) $leave->days);
            }
        });
    }
}
