<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceTicket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Génère les interventions de maintenance préventive dues (§36) et
 * fait avancer l'échéance suivante de chaque plan.
 */
class PreventiveMaintenance
{
    /** @return int Nombre de tickets créés. */
    public static function run(?Carbon $on = null): int
    {
        $on = $on ?: Carbon::today();
        $created = 0;

        MaintenancePlan::with('equipment')->due($on)->get()->each(function (MaintenancePlan $plan) use ($on, &$created) {
            DB::transaction(function () use ($plan, $on, &$created) {
                // Ne pas doublonner si un ticket préventif de ce plan est déjà ouvert.
                $alreadyOpen = MaintenanceTicket::where('maintenance_plan_id', $plan->id)
                    ->whereIn('status', MaintenanceTicket::OPEN_STATUSES)
                    ->exists();

                if (! $alreadyOpen) {
                    MaintenanceTicket::create([
                        'reference' => self::reference(),
                        'hotel_id' => $plan->hotel_id,
                        'equipment_id' => $plan->equipment_id,
                        'maintenance_plan_id' => $plan->id,
                        'assigned_to' => $plan->assigned_to,
                        'type' => 'preventive',
                        'priority' => $plan->priority,
                        'status' => $plan->assigned_to ? 'assigned' : 'open',
                        'title' => $plan->name,
                        'description' => "Maintenance préventive planifiée.\n\nPoints à contrôler :\n- "
                            .implode("\n- ", $plan->checklist ?? []),
                        'due_on' => $plan->next_due_on,
                    ]);
                    $created++;
                }

                $plan->update([
                    'last_run_on' => $on->toDateString(),
                    'next_due_on' => $on->copy()->addDays($plan->interval_days)->toDateString(),
                ]);
            });
        });

        if ($created > 0) {
            AuditLog::record('maintenance.preventive_run', null, ['tickets' => $created]);
        }

        return $created;
    }

    public static function reference(): string
    {
        do {
            $ref = 'M-'.strtoupper(Str::random(6));
        } while (MaintenanceTicket::where('reference', $ref)->exists());

        return $ref;
    }
}
