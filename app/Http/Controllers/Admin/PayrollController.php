<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Hotel;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\SalaryComponent;
use App\Services\PayrollEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PayrollController extends Controller
{
    public function index()
    {
        return view('admin.hr.payroll.index', [
            'runs' => PayrollRun::withCount('payslips')->latest('period')->paginate(15),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
        ]);

        abort_if(PayrollRun::where('hotel_id', Hotel::current()->id)->where('period', $data['period'])->exists(), 422, 'Une paie existe déjà pour cette période.');

        $run = PayrollRun::create([
            'hotel_id' => Hotel::current()->id,
            'period' => $data['period'],
            'label' => 'Paie '.Carbon::createFromFormat('Y-m', $data['period'])->locale('fr')->isoFormat('MMMM YYYY'),
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.hr.payroll.show', $run);
    }

    public function show(PayrollRun $run)
    {
        $run->load(['payslips.employee.department', 'payslips.lines', 'adjustments.employee']);

        return view('admin.hr.payroll.show', [
            'run' => $run,
            'employees' => Employee::active()->orderBy('last_name')->get(),
            'components' => SalaryComponent::orderBy('sort_order')->get(),
        ]);
    }

    public function generate(PayrollRun $run)
    {
        abort_unless($run->status === 'draft', 422);

        PayrollEngine::generate($run);
        AuditLog::record('hr.payroll.generated', $run, ['payslips' => $run->payslips()->count()]);

        return back()->with('status', 'Bulletins générés.');
    }

    public function storeAdjustment(Request $request, PayrollRun $run)
    {
        abort_unless($run->status === 'draft', 422);

        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'kind' => ['required', 'in:earning,deduction'],
            'label' => ['required', 'string', 'max:80'],
            'amount' => ['required', 'integer', 'min:1'],
            'salary_component_id' => ['nullable', 'exists:salary_components,id'],
        ]);

        $run->adjustments()->create($data);

        return back()->with('status', 'Élément variable ajouté. Régénérez les bulletins.');
    }

    public function destroyAdjustment(PayrollRun $run, PayrollAdjustment $adjustment)
    {
        abort_unless($run->status === 'draft', 422);
        $adjustment->delete();

        return back()->with('status', 'Élément retiré.');
    }

    public function approve(Request $request, PayrollRun $run)
    {
        abort_unless($run->status === 'draft' && $run->payslips()->exists(), 422);
        abort_unless($request->user()->hasRole('direction'), 403);

        $run->update(['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()]);
        AuditLog::record('hr.payroll.approved', $run);

        return back()->with('status', 'Paie approuvée.');
    }

    public function markPaid(PayrollRun $run)
    {
        PayrollEngine::markPaid($run);
        AuditLog::record('hr.payroll.paid', $run, ['net' => $run->net_total]);

        return back()->with('status', 'Paie marquée payée. Trésorerie et comptabilité mises à jour.');
    }

    public function payslip(PayrollRun $run, Payslip $payslip)
    {
        abort_unless($payslip->payroll_run_id === $run->id, 404);
        $payslip->load(['employee.department', 'employee.position', 'contract', 'lines', 'run']);

        return view('admin.hr.payroll.payslip', ['slip' => $payslip]);
    }
}
