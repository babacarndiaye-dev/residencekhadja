<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\FinanceAccount;
use App\Models\FinanceTransaction;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\SalaryAdvance;
use App\Models\SalaryComponent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Moteur de paie (§45) — brut / net paramétrable.
 *
 * Rémunération = composants fixes de l'employé + éléments variables de la
 * période (heures supplémentaires issues du pointage, retenue absences,
 * remboursement d'avance, primes/retenues saisies) ; les retenues en
 * pourcentage s'appliquent sur la base imposable.
 */
class PayrollEngine
{
    public static function generate(PayrollRun $run): void
    {
        $cfg = config('payroll');
        $start = Carbon::createFromFormat('Y-m', $run->period)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $components = SalaryComponent::where('hotel_id', $run->hotel_id)->orderBy('sort_order')->get()->keyBy('code');

        DB::transaction(function () use ($run, $cfg, $start, $end, $components) {
            $run->payslips()->each(fn ($p) => $p->delete());

            $gross = $ded = $net = 0;

            foreach (Employee::where('hotel_id', $run->hotel_id)->active()->with('salaryComponents.component')->get() as $emp) {
                $contract = $emp->activeContract();
                if (! $contract) {
                    continue;
                }

                $base = $contract->base_salary;
                $dailyRate = (int) round($base / $cfg['working_days_per_month']);
                $hourlyRate = $base / ($cfg['working_days_per_month'] * $cfg['hours_per_day']);

                // Variables de période.
                $overtimeMin = (int) AttendanceLog::where('employee_id', $emp->id)
                    ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
                    ->sum('overtime_minutes');
                $overtimeHours = round($overtimeMin / 60, 2);

                $absenceDays = AttendanceLog::where('employee_id', $emp->id)->where('status', 'absent')
                    ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])->count();

                $unpaidLeaveDays = (int) LeaveRequest::where('employee_id', $emp->id)
                    ->where('status', 'approved')->where('type', 'sans_solde')
                    ->where('start_date', '<=', $end->toDateString())
                    ->where('end_date', '>=', $start->toDateString())
                    ->get()->sum(fn ($l) => LeaveService::workingDays(
                        max($l->start_date, $start), min($l->end_date, $end)
                    ));

                $unpaidDays = $absenceDays + $unpaidLeaveDays;

                $lines = [];
                $order = 0;
                $earn = 0;
                $taxable = 0;

                // Gains fixes de l'employé.
                foreach ($emp->salaryComponents as $esc) {
                    $c = $esc->component;
                    if (! $c || $c->kind !== 'earning' || $c->calc !== 'fixed') {
                        continue;
                    }
                    $amount = (int) ($esc->amount ?? $c->default_amount ?? 0);
                    if ($c->system_role === 'base') {
                        $amount = $base;
                    }
                    if ($amount <= 0) {
                        continue;
                    }
                    $lines[] = ['salary_component_id' => $c->id, 'code' => $c->code, 'label' => $c->name, 'kind' => 'earning', 'amount' => $amount, 'sort_order' => $order++];
                    $earn += $amount;
                    if ($c->is_taxable) {
                        $taxable += $amount;
                    }
                }

                // Filet de sécurité : salaire de base si aucun composant BASE.
                if (! collect($lines)->contains('code', 'BASE') && isset($components['BASE'])) {
                    array_unshift($lines, ['salary_component_id' => $components['BASE']->id, 'code' => 'BASE', 'label' => 'Salaire de base', 'kind' => 'earning', 'amount' => $base, 'sort_order' => -1]);
                    $earn += $base;
                    $taxable += $base;
                }

                // Prime d'ancienneté (% du salaire de base, barème conventionnel).
                if (isset($components['ANCIENNETE'])) {
                    $seniorityRate = self::seniorityRate($emp);
                    if ($seniorityRate > 0) {
                        $amount = (int) round($base * $seniorityRate / 100);
                        $lines[] = ['salary_component_id' => $components['ANCIENNETE']->id, 'code' => 'ANCIENNETE', 'label' => 'Prime d’ancienneté', 'kind' => 'earning', 'base' => $base, 'rate' => $seniorityRate, 'amount' => $amount, 'sort_order' => $order++];
                        $earn += $amount;
                        $taxable += $amount;
                    }
                }

                // Prime de fin d'année (13e mois) sur le run du mois configuré.
                $m13 = $cfg['thirteenth_month'] ?? [];
                if (($m13['enabled'] ?? false) && (int) $start->month === (int) ($m13['month'] ?? 12) && isset($components['M13'])) {
                    $amount = (int) round($base * (float) ($m13['fraction'] ?? 1));
                    $lines[] = ['salary_component_id' => $components['M13']->id, 'code' => 'M13', 'label' => 'Prime de fin d’année', 'kind' => 'earning', 'amount' => $amount, 'sort_order' => $order++];
                    $earn += $amount;
                    $taxable += $amount;
                }

                // Heures supplémentaires.
                if ($overtimeHours > 0 && isset($components['HSUP'])) {
                    $hsup = (int) round($overtimeHours * $hourlyRate * $cfg['overtime_multiplier']);
                    $lines[] = ['salary_component_id' => $components['HSUP']->id, 'code' => 'HSUP', 'label' => 'Heures supplémentaires', 'kind' => 'earning', 'base' => (int) round($hourlyRate), 'rate' => $cfg['overtime_multiplier'], 'amount' => $hsup, 'sort_order' => $order++];
                    $earn += $hsup;
                    $taxable += $hsup;
                }

                // Primes saisies (adjustments earning).
                foreach ($run->adjustments()->where('employee_id', $emp->id)->where('kind', 'earning')->get() as $adj) {
                    $lines[] = ['salary_component_id' => $adj->salary_component_id, 'code' => 'PRIME', 'label' => $adj->label, 'kind' => 'earning', 'amount' => $adj->amount, 'sort_order' => $order++];
                    $earn += $adj->amount;
                    $taxable += $adj->amount;
                }

                // --- Retenues ---
                $deductTotal = 0;
                $socialWithheld = 0;   // IPRES + CSS : déductibles de la base IRPP.

                // Cotisations sociales salariales (% de la base imposable).
                foreach ($components->where('kind', 'deduction')->where('calc', 'percent_base') as $c) {
                    $esc = $emp->salaryComponents->firstWhere('salary_component_id', $c->id);
                    if (! $c->applies_to_all && ! $esc) {
                        continue;
                    }
                    $rate = (float) ($esc->rate ?? $c->default_rate ?? 0);
                    if ($rate <= 0) {
                        continue;
                    }
                    $amount = (int) round($taxable * $rate / 100);
                    $lines[] = ['salary_component_id' => $c->id, 'code' => $c->code, 'label' => $c->name, 'kind' => 'deduction', 'base' => $taxable, 'rate' => $rate, 'amount' => $amount, 'sort_order' => $order++];
                    $deductTotal += $amount;
                    $socialWithheld += $amount;
                }

                // IRPP — barème progressif + quotient familial (base = imposable − cotisations).
                if (isset($components['IRPP'])) {
                    $parts = self::parts($emp);
                    $irppBase = max(0, $taxable - $socialWithheld);
                    $irpp = self::monthlyIrpp($irppBase, $parts);
                    if ($irpp > 0) {
                        $lines[] = ['salary_component_id' => $components['IRPP']->id, 'code' => 'IRPP', 'label' => 'IRPP ('.rtrim(rtrim(number_format($parts, 1), '0'), '.').' part'.($parts > 1 ? 's' : '').')', 'kind' => 'deduction', 'base' => $irppBase, 'amount' => $irpp, 'sort_order' => $order++];
                        $deductTotal += $irpp;
                    }
                }

                // TRIMF — montant forfaitaire par tranche.
                if (isset($components['TRIMF'])) {
                    $trimf = self::monthlyTrimf(max(0, $taxable - $socialWithheld));
                    if ($trimf > 0) {
                        $lines[] = ['salary_component_id' => $components['TRIMF']->id, 'code' => 'TRIMF', 'label' => 'TRIMF', 'kind' => 'deduction', 'amount' => $trimf, 'sort_order' => $order++];
                        $deductTotal += $trimf;
                    }
                }

                // Retenue absences.
                if ($unpaidDays > 0 && isset($components['ABSENCE'])) {
                    $amount = $unpaidDays * $dailyRate;
                    $lines[] = ['salary_component_id' => $components['ABSENCE']->id, 'code' => 'ABSENCE', 'label' => "Retenue absence ({$unpaidDays} j)", 'kind' => 'deduction', 'base' => $dailyRate, 'amount' => $amount, 'sort_order' => $order++];
                    $deductTotal += $amount;
                }

                // Remboursement d'avance.
                $advance = $emp->outstandingAdvances();
                if ($advance > 0 && isset($components['AVANCE'])) {
                    $lines[] = ['salary_component_id' => $components['AVANCE']->id, 'code' => 'AVANCE', 'label' => 'Remboursement avance', 'kind' => 'deduction', 'amount' => $advance, 'sort_order' => $order++];
                    $deductTotal += $advance;
                }

                // Retenues saisies (adjustments deduction).
                foreach ($run->adjustments()->where('employee_id', $emp->id)->where('kind', 'deduction')->get() as $adj) {
                    $lines[] = ['salary_component_id' => $adj->salary_component_id, 'code' => 'RETENUE', 'label' => $adj->label, 'kind' => 'deduction', 'amount' => $adj->amount, 'sort_order' => $order++];
                    $deductTotal += $adj->amount;
                }

                $slip = Payslip::create([
                    'payroll_run_id' => $run->id,
                    'employee_id' => $emp->id,
                    'employee_contract_id' => $contract->id,
                    'worked_days' => max(0, $cfg['working_days_per_month'] - $unpaidDays),
                    'overtime_hours' => $overtimeHours,
                    'absence_days' => $unpaidDays,
                    'gross' => $earn,
                    'taxable_gross' => $taxable,
                    'total_deductions' => $deductTotal,
                    'net' => $earn - $deductTotal,
                ]);
                $slip->lines()->createMany($lines);

                $gross += $earn;
                $ded += $deductTotal;
                $net += $earn - $deductTotal;
            }

            $run->update(['gross_total' => $gross, 'deduction_total' => $ded, 'net_total' => $net]);
        });
    }

    /** Passe la paie en « payée » : trésorerie, comptabilité, avances soldées. */
    public static function markPaid(PayrollRun $run): void
    {
        abort_unless($run->status === 'approved', 422);

        DB::transaction(function () use ($run) {
            $run->update(['status' => 'paid', 'paid_at' => now()]);

            $bank = FinanceAccount::where('hotel_id', $run->hotel_id)->where('type', 'bank')->first()
                ?? FinanceAccount::where('hotel_id', $run->hotel_id)->first();

            $treasury = config("accounting.posting.treasury.{$bank->type}", '521000');
            $acc = config('payroll.accounts');

            $entry = Accounting::post('OD', $run->paid_at, "Paie {$run->periodLabel()}", array_values(array_filter([
                ['account' => $acc['gross_expense'], 'debit' => $run->gross_total],
                $run->deduction_total > 0 ? ['account' => $acc['withholdings'], 'credit' => $run->deduction_total] : null,
                ['account' => $treasury, 'credit' => $run->net_total],
            ])), $run);

            FinanceTransaction::create([
                'hotel_id' => $run->hotel_id,
                'finance_account_id' => $bank->id,
                'user_id' => Auth::id(),
                'direction' => 'expense',
                'category' => 'salaires',
                'method' => 'virement',
                'amount' => $run->net_total,
                'label' => "Paie {$run->periodLabel()} — versements",
                'operation_date' => $run->paid_at->toDateString(),
                'source_type' => $run->getMorphClass(),
                'source_id' => $run->id,
                'journal_entry_id' => $entry->id,
            ]);

            // Soldes des avances remboursées via la paie.
            foreach ($run->payslips()->with('employee')->get() as $slip) {
                $repay = (int) $slip->lines()->where('code', 'AVANCE')->sum('amount');
                if ($repay <= 0) {
                    continue;
                }
                foreach (SalaryAdvance::where('employee_id', $slip->employee_id)->where('status', 'outstanding')->orderBy('granted_on')->get() as $adv) {
                    if ($repay <= 0) {
                        break;
                    }
                    $take = min($repay, $adv->balance());
                    $adv->increment('repaid_amount', $take);
                    if ($adv->fresh()->balance() <= 0) {
                        $adv->update(['status' => 'repaid']);
                    }
                    $repay -= $take;
                }
            }
        });
    }

    /* ------------------------- Barèmes paramétrables ------------------------- */

    /** Nombre de parts (quotient familial) d'un employé. */
    public static function parts(Employee $emp): float
    {
        $fq = config('payroll.family_quotient');
        $situation = (float) ($fq['situations'][$emp->marital_status] ?? 1.0);
        $parts = $situation + ((int) $emp->dependents_count) * (float) ($fq['per_dependent'] ?? 0.5);

        return min($parts, (float) ($fq['max_parts'] ?? 5.0));
    }

    /** IRPP mensuel : barème annuel appliqué au revenu par part, remis au mois. */
    public static function monthlyIrpp(int $monthlyBase, float $parts): int
    {
        $parts = max(1.0, $parts);
        $perPart = self::annualIrpp(($monthlyBase * 12) / $parts);

        return (int) round($perPart * $parts / 12);
    }

    /** IRPP annuel par tranches (config payroll.irpp_brackets). */
    public static function annualIrpp(float $annual): int
    {
        $annual = max(0.0, $annual);
        $tax = 0.0;
        $floor = 0.0;

        foreach (config('payroll.irpp_brackets', []) as $bracket) {
            $ceiling = $bracket['up_to'] === null ? INF : (float) $bracket['up_to'];
            $slice = max(0.0, min($annual, $ceiling) - $floor);
            $tax += $slice * (float) $bracket['rate'];
            $floor = $ceiling;
            if ($annual <= $ceiling) {
                break;
            }
        }

        return (int) round($tax);
    }

    /** TRIMF mensuel (montant annuel de la tranche / 12). */
    public static function monthlyTrimf(int $monthlyBase): int
    {
        $annual = $monthlyBase * 12;
        foreach (config('payroll.trimf_brackets', []) as $bracket) {
            if ($bracket['up_to'] === null || $annual <= $bracket['up_to']) {
                return (int) round($bracket['amount'] / 12);
            }
        }

        return 0;
    }

    /** Taux de prime d'ancienneté (%) selon les années de présence. */
    public static function seniorityRate(Employee $emp, ?Carbon $asOf = null): float
    {
        $cfg = config('payroll.seniority');
        if (! ($cfg['enabled'] ?? false) || ! $emp->hire_date) {
            return 0.0;
        }

        $years = (int) $emp->hire_date->diffInYears($asOf ?? now());
        $start = (int) ($cfg['start_years'] ?? 2);
        if ($years < $start) {
            return 0.0;
        }

        $rate = (float) ($cfg['start_percent'] ?? 2) + ($years - $start) * (float) ($cfg['step_percent'] ?? 1);

        return min($rate, (float) ($cfg['cap_percent'] ?? 25));
    }
}
