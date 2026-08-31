<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Hotel;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $leaves = LeaveRequest::query()
            ->with(['employee', 'approver'])
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest('start_date')
            ->paginate(30)->withQueryString();

        return view('admin.hr.leave', [
            'leaves' => $leaves,
            'status' => $status,
            'statuses' => LeaveRequest::STATUSES,
            'employees' => Employee::active()->orderBy('last_name')->get(),
            'types' => collect(config('hr.leave_types'))->map(fn ($t) => $t['label']),
            'pendingCount' => LeaveRequest::pending()->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'type' => ['required', Rule::in(array_keys(config('hr.leave_types')))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        LeaveRequest::create($data + [
            'hotel_id' => Hotel::current()->id,
            'days' => LeaveService::workingDays($data['start_date'], $data['end_date']),
            'status' => 'pending',
        ]);

        return back()->with('status', 'Demande de congé enregistrée.');
    }

    public function approve(LeaveRequest $leave)
    {
        LeaveService::approve($leave);
        AuditLog::record('hr.leave.approved', $leave);

        return back()->with('status', 'Congé approuvé.');
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        LeaveService::reject($leave, $request->input('note'));

        return back()->with('status', 'Demande refusée.');
    }

    public function cancel(LeaveRequest $leave)
    {
        LeaveService::cancel($leave);

        return back()->with('status', 'Congé annulé.');
    }
}
