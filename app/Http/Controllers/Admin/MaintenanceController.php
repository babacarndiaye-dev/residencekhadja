<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Equipment;
use App\Models\Hotel;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceTicket;
use App\Models\Room;
use App\Models\User;
use App\Services\PreventiveMaintenance;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaintenanceController extends Controller
{
    /* ------------------------------ Tickets ---------------------------- */

    public function tickets(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(config('maintenance.ticket_statuses')))],
            'priority' => ['nullable', Rule::in(array_keys(config('maintenance.ticket_priorities')))],
            'type' => ['nullable', Rule::in(['corrective', 'preventive'])],
            'q' => ['nullable', 'string', 'max:60'],
        ]);

        $tickets = MaintenanceTicket::query()
            ->with(['equipment', 'room', 'technician'])
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['priority'] ?? null, fn ($q, $p) => $q->where('priority', $p))
            ->when($filters['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->when($filters['q'] ?? null, fn ($q, $t) => $q->where(fn ($s) => $s
                ->where('reference', 'like', "%{$t}%")->orWhere('title', 'like', "%{$t}%")))
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 1 WHEN 'assigned' THEN 2 WHEN 'open' THEN 3 WHEN 'on_hold' THEN 4 WHEN 'resolved' THEN 5 ELSE 6 END")
            ->orderByRaw("CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END")
            ->paginate(25)->withQueryString();

        return view('admin.maintenance.tickets', [
            'tickets' => $tickets,
            'filters' => $filters,
            'statuses' => config('maintenance.ticket_statuses'),
            'priorities' => config('maintenance.ticket_priorities'),
            'summary' => [
                'open' => MaintenanceTicket::open()->count(),
                'critical' => MaintenanceTicket::open()->where('priority', 'critical')->count(),
                'preventive_due' => MaintenancePlan::due()->count(),
                'cost_month' => (int) MaintenanceTicket::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->selectRaw('sum(labor_cost + parts_cost) as c')->value('c'),
            ],
        ]);
    }

    public function createTicket()
    {
        return view('admin.maintenance.ticket', [
            'ticket' => new MaintenanceTicket(['priority' => 'normal', 'type' => 'corrective', 'status' => 'open']),
            'equipment' => Equipment::orderBy('name')->get(),
            'rooms' => Room::orderBy('number')->get(),
            'technicians' => User::where('role', 'maintenance')->where('is_active', true)->get(),
        ]);
    }

    public function storeTicket(Request $request)
    {
        $data = $this->validateTicket($request);

        $ticket = MaintenanceTicket::create($data + [
            'reference' => PreventiveMaintenance::reference(),
            'hotel_id' => Hotel::current()->id,
            'reported_by' => $request->user()->id,
            'type' => 'corrective',
            'status' => ($data['assigned_to'] ?? null) ? 'assigned' : 'open',
        ]);

        AuditLog::record('maintenance.ticket.created', $ticket);

        return redirect()->route('admin.maintenance.ticket', $ticket)->with('status', "Ticket {$ticket->reference} créé.");
    }

    public function showTicket(MaintenanceTicket $ticket)
    {
        $ticket->load(['equipment', 'room', 'technician', 'reporter', 'plan']);

        return view('admin.maintenance.ticket', [
            'ticket' => $ticket,
            'equipment' => Equipment::orderBy('name')->get(),
            'rooms' => Room::orderBy('number')->get(),
            'technicians' => User::where('role', 'maintenance')->where('is_active', true)->get(),
        ]);
    }

    public function updateTicket(Request $request, MaintenanceTicket $ticket)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'equipment_id' => ['nullable', 'exists:equipment,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('role', 'maintenance')],
            'priority' => ['required', Rule::in(array_keys(config('maintenance.ticket_priorities')))],
            'status' => ['required', Rule::in(array_keys(config('maintenance.ticket_statuses')))],
            'resolution' => ['nullable', 'string', 'max:2000'],
            'labor_cost' => ['nullable', 'integer', 'min:0'],
            'parts_cost' => ['nullable', 'integer', 'min:0'],
            'due_on' => ['nullable', 'date'],
        ]);

        $ticket->fill($data);
        $ticket->labor_cost = $data['labor_cost'] ?? 0;
        $ticket->parts_cost = $data['parts_cost'] ?? 0;

        $ticket->started_at ??= in_array($data['status'], ['in_progress', 'resolved', 'closed'], true) ? now() : null;
        $ticket->resolved_at = in_array($data['status'], ['resolved', 'closed'], true) ? ($ticket->resolved_at ?? now()) : null;
        $ticket->closed_at = $data['status'] === 'closed' ? ($ticket->closed_at ?? now()) : null;

        $ticket->save();

        // Répercussion sur l'état de l'équipement.
        if ($ticket->equipment) {
            $ticket->equipment->update(['status' => match ($data['status']) {
                'resolved', 'closed' => 'operational',
                'in_progress', 'on_hold' => $data['priority'] === 'critical' ? 'out_of_service' : 'degraded',
                default => $ticket->equipment->status,
            }]);
        }

        AuditLog::record('maintenance.ticket.updated', $ticket, ['status' => $data['status']]);

        return back()->with('status', "Ticket {$ticket->reference} mis à jour.");
    }

    /* ---------------------------- Équipements -------------------------- */

    public function equipment(Request $request)
    {
        return view('admin.maintenance.equipment', [
            'equipment' => Equipment::with('room')->withCount(['tickets as open_tickets_count' => fn ($q) => $q->open()])
                ->orderBy('category')->orderBy('name')->get()->groupBy('category'),
            'categories' => config('maintenance.equipment_categories'),
            'statuses' => Equipment::STATUSES,
            'rooms' => Room::orderBy('number')->get(),
        ]);
    }

    public function storeEquipment(Request $request)
    {
        $data = $this->validateEquipment($request);
        $data['hotel_id'] = Hotel::current()->id;

        Equipment::create($data);

        return back()->with('status', 'Équipement ajouté.');
    }

    public function updateEquipment(Request $request, Equipment $equipment)
    {
        $equipment->update($this->validateEquipment($request));

        return back()->with('status', "« {$equipment->name} » mis à jour.");
    }

    /* ------------------------- Plans préventifs ----------------------- */

    public function plans()
    {
        return view('admin.maintenance.plans', [
            'plans' => MaintenancePlan::with('equipment', 'assignee')->orderBy('next_due_on')->get(),
            'categories' => config('maintenance.equipment_categories'),
            'equipment' => Equipment::orderBy('name')->get(),
            'technicians' => User::where('role', 'maintenance')->where('is_active', true)->get(),
            'dueCount' => MaintenancePlan::due()->count(),
        ]);
    }

    public function storePlan(Request $request)
    {
        $data = $this->validatePlan($request);
        $data['hotel_id'] = Hotel::current()->id;
        $data['next_due_on'] = $data['next_due_on'] ?? now()->addDays($data['interval_days'])->toDateString();

        MaintenancePlan::create($data);

        return back()->with('status', 'Plan créé.');
    }

    public function updatePlan(Request $request, MaintenancePlan $plan)
    {
        $plan->update($this->validatePlan($request));

        return back()->with('status', "Plan « {$plan->name} » mis à jour.");
    }

    public function destroyPlan(MaintenancePlan $plan)
    {
        $plan->delete();

        return back()->with('status', 'Plan supprimé.');
    }

    public function runPlans()
    {
        $count = PreventiveMaintenance::run();

        return back()->with('status', "{$count} intervention(s) préventive(s) générée(s).");
    }

    /* ---------------------------- Helpers ---------------------------- */

    private function validateTicket(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'equipment_id' => ['nullable', 'exists:equipment,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('role', 'maintenance')],
            'priority' => ['required', Rule::in(array_keys(config('maintenance.ticket_priorities')))],
        ]);
    }

    private function validateEquipment(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::in(array_keys(config('maintenance.equipment_categories')))],
            'location' => ['nullable', 'string', 'max:120'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'brand' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:80'],
            'serial' => ['nullable', 'string', 'max:80'],
            'commissioned_on' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(Equipment::STATUSES))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function validatePlan(Request $request): array
    {
        $v = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'equipment_category' => ['nullable', Rule::in(array_keys(config('maintenance.equipment_categories')))],
            'equipment_id' => ['nullable', 'exists:equipment,id'],
            'interval_days' => ['required', 'integer', 'min:1', 'max:730'],
            'priority' => ['required', Rule::in(array_keys(config('maintenance.ticket_priorities')))],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('role', 'maintenance')],
            'checklist' => ['nullable', 'string', 'max:1500'],
            'next_due_on' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $v['checklist'] = collect(preg_split('/\r\n|\r|\n/', (string) ($v['checklist'] ?? '')))
            ->map(fn ($l) => trim($l))->filter()->values()->all();
        $v['is_active'] = $request->boolean('is_active');

        return $v;
    }
}
