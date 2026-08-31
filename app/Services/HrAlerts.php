<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\EmployeeDocument;
use App\Models\Hotel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Alertes RH : fins de période d'essai / de CDD imminentes, pièces et visites
 * médicales qui expirent, anniversaires d'ancienneté du mois.
 * Alimente le tableau de bord RH et la commande `hr:alerts`.
 */
class HrAlerts
{
    /** @return Collection<int, array{level:string,type:string,label:string,date:?string,employee_id:int,url:string}> */
    public static function collect(?Carbon $today = null): Collection
    {
        $today = ($today ?? Carbon::today())->startOfDay();
        $hotelId = Hotel::current()->id;
        $cfg = config('hr.alerts');
        $out = collect();

        // Fin de période d'essai.
        $trialWindow = $today->copy()->addDays((int) ($cfg['trial_lead_days'] ?? 10));
        EmployeeContract::with('employee')
            ->whereHas('employee', fn ($q) => $q->where('hotel_id', $hotelId)->active())
            ->where('status', 'active')
            ->whereNotNull('trial_period_days')
            ->get()
            ->each(function (EmployeeContract $c) use ($out, $today, $trialWindow) {
                $end = $c->start_date->copy()->addDays((int) $c->trial_period_days);
                if ($end->gte($today) && $end->lte($trialWindow)) {
                    $out->push(self::row('warning', 'periode_essai',
                        "Fin de période d'essai — {$c->employee?->fullName()}", $end, $c->employee));
                }
            });

        // Fin de CDD.
        $cddWindow = $today->copy()->addDays((int) ($cfg['contract_lead_days'] ?? 45));
        EmployeeContract::with('employee')
            ->whereHas('employee', fn ($q) => $q->where('hotel_id', $hotelId)->active())
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$today, $cddWindow])
            ->get()
            ->each(fn (EmployeeContract $c) => $out->push(self::row(
                $c->end_date->lte($today->copy()->addWeek()) ? 'critical' : 'warning',
                'fin_contrat', "Fin de contrat — {$c->employee?->fullName()}", $c->end_date, $c->employee)));

        // Pièces / visites médicales qui expirent (ou expirées).
        $docWindow = $today->copy()->addDays((int) ($cfg['document_lead_days'] ?? 30));
        EmployeeDocument::with('employee')
            ->whereHas('employee', fn ($q) => $q->where('hotel_id', $hotelId)->active())
            ->whereNotNull('expires_on')
            ->where('expires_on', '<=', $docWindow)
            ->get()
            ->each(fn (EmployeeDocument $d) => $out->push(self::row(
                $d->expires_on->isPast() ? 'critical' : 'warning',
                'document', "{$d->title} — {$d->employee?->fullName()}", $d->expires_on, $d->employee)));

        // Anniversaires d'ancienneté du mois (1 an, 5 ans, 10 ans…).
        Employee::where('hotel_id', $hotelId)->active()->whereNotNull('hire_date')->get()
            ->each(function (Employee $e) use ($out, $today) {
                if ((int) $e->hire_date->month !== (int) $today->month) {
                    return;
                }
                $years = $today->year - $e->hire_date->year;
                if ($years >= 1 && ($years === 1 || $years % 5 === 0)) {
                    $out->push(self::row('info', 'anciennete',
                        "{$years} an".($years > 1 ? 's' : '')." d'ancienneté — {$e->fullName()}",
                        $e->hire_date->copy()->year($today->year), $e));
                }
            });

        return $out->sortBy(fn ($r) => [['critical' => 0, 'warning' => 1, 'info' => 2][$r['level']], $r['date']])->values();
    }

    private static function row(string $level, string $type, string $label, ?Carbon $date, ?Employee $employee): array
    {
        return [
            'level' => $level,
            'type' => $type,
            'label' => $label,
            'date' => $date?->toDateString(),
            'employee_id' => $employee?->id ?? 0,
            'url' => $employee ? route('admin.hr.employees.show', $employee, false) : route('admin.hr.dashboard', absolute: false),
        ];
    }
}
