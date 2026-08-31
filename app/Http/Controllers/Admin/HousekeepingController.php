<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\HousekeepingIncident;
use App\Models\HousekeepingTask;
use App\Models\MaintenanceTicket;
use App\Models\Room;
use App\Models\User;
use App\Services\HousekeepingPlanner;
use App\Services\PreventiveMaintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class HousekeepingController extends Controller
{
    public function index(Request $request)
    {
        $date = Carbon::parse($request->query('date', today()));

        $tasks = HousekeepingTask::with(['room.category', 'assignee', 'reservation.guest'])
            ->forDate($date)
            ->get()
            ->sortBy(fn ($t) => $t->room->number)
            ->values();

        return view('admin.housekeeping.index', [
            'date' => $date,
            'tasks' => $tasks,
            'agents' => User::where('role', 'housekeeping')->where('is_active', true)->orderBy('name')->get(),
            'types' => config('housekeeping.task_types'),
            'statuses' => HousekeepingTask::STATUSES,
            'summary' => [
                'total' => $tasks->count(),
                'done' => $tasks->whereIn('status', ['done', 'inspected'])->count(),
                'unassigned' => $tasks->whereNull('assigned_to')->count(),
            ],
        ]);
    }

    /** Écran terrain simplifié (tablette / téléphone) pour les agents d'étage. */
    public function mobile(Request $request)
    {
        $mine = $request->boolean('mine', $request->user()->role === 'housekeeping');

        $tasks = HousekeepingTask::with(['room.category', 'reservation.guest'])
            ->forDate(today())
            ->when($mine && $request->user()->role === 'housekeeping',
                fn ($q) => $q->where('assigned_to', $request->user()->id))
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'pending' THEN 1 WHEN 'blocked' THEN 2 ELSE 3 END")
            ->get()
            ->sortBy(fn ($t) => [$t->status === 'in_progress' ? 0 : ($t->status === 'pending' ? 1 : ($t->status === 'blocked' ? 2 : 3)), $t->room->number])
            ->values();

        return view('admin.housekeeping.mobile', [
            'tasks' => $tasks,
            'mine' => $mine,
            'types' => config('housekeeping.task_types'),
            'incidentCategories' => config('housekeeping.incident_categories'),
            'done' => $tasks->whereIn('status', ['done', 'inspected'])->count(),
        ]);
    }

    public function generate(Request $request)
    {
        $date = Carbon::parse($request->input('date', today()));
        $count = HousekeepingPlanner::generateForDate(Hotel::current(), $date);

        return back()->with('status', "{$count} tâche(s) créée(s) pour le ".$date->format('d/m/Y').'.');
    }

    public function assign(Request $request, HousekeepingTask $task)
    {
        $data = $request->validate([
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('role', 'housekeeping')],
        ]);

        $task->update(['assigned_to' => $data['assigned_to'] ?: null]);
        AuditLog::record('housekeeping.assign', $task, ['assigned_to' => $data['assigned_to'] ?? null]);

        return back();
    }

    public function updateStatus(Request $request, HousekeepingTask $task)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'in_progress', 'done', 'blocked'])],
            'consumables_note' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $task->update([
            'status' => $data['status'],
            'consumables_note' => $data['consumables_note'] ?? $task->consumables_note,
            'notes' => $data['notes'] ?? $task->notes,
            'started_at' => $data['status'] === 'in_progress' ? ($task->started_at ?? now()) : $task->started_at,
            'completed_at' => $data['status'] === 'done' ? now() : null,
        ]);

        // Chambre sale marquée « en nettoyage » puis « à contrôler ».
        if ($task->room) {
            $task->room->update(['status' => match ($data['status']) {
                'in_progress' => 'en_nettoyage',
                'done' => 'controle',
                'blocked' => 'bloquee',
                default => $task->room->status,
            }]);
        }

        AuditLog::record('housekeeping.status', $task, ['status' => $data['status']]);

        return back()->with('status', "Chambre {$task->room->number} : {$task->statusLabel()}.");
    }

    public function inspect(HousekeepingTask $task)
    {
        $task->load(['checks', 'room']);

        if ($task->checks->isEmpty()) {
            foreach (config('housekeeping.qc_checklist') as $i => $label) {
                $task->checks()->create(['label' => $label, 'sort_order' => $i]);
            }
            $task->load('checks');
        }

        return view('admin.housekeeping.inspect', ['task' => $task]);
    }

    public function storeInspection(Request $request, HousekeepingTask $task)
    {
        $data = $request->validate([
            'checks' => ['required', 'array'],
            'checks.*.passed' => ['nullable', 'boolean'],
            'checks.*.comment' => ['nullable', 'string', 'max:200'],
        ]);

        $task->load('checks');
        $total = $task->checks->count();
        $passed = 0;

        foreach ($task->checks as $check) {
            $input = $data['checks'][$check->id] ?? [];
            $ok = (bool) ($input['passed'] ?? false);
            $check->update(['passed' => $ok, 'comment' => $input['comment'] ?? null]);
            $passed += $ok ? 1 : 0;
        }

        $score = $total > 0 ? (int) round($passed / $total * 100) : 0;
        $pass = $score >= config('housekeeping.qc_pass_score');

        $task->update([
            'status' => $pass ? 'inspected' : 'pending',
            'qc_score' => $score,
            'inspected_by' => $request->user()->id,
            'inspected_at' => now(),
        ]);

        if ($task->room) {
            $task->room->update(['status' => $pass ? 'propre' : 'sale']);
        }

        AuditLog::record('housekeeping.inspected', $task, ['score' => $score, 'pass' => $pass]);

        return redirect()->route('admin.housekeeping.index', ['date' => $task->service_date->toDateString()])
            ->with('status', $pass
                ? "Chambre {$task->room->number} validée ({$score}%)."
                : "Chambre {$task->room->number} renvoyée en nettoyage ({$score}%).");
    }

    /* --------------------------- Incidents --------------------------- */

    public function incidents(Request $request)
    {
        return view('admin.housekeeping.incidents', [
            'incidents' => HousekeepingIncident::with(['room', 'reporter', 'ticket'])
                ->orderByRaw("status = 'resolved'")->latest()->paginate(30),
            'rooms' => Room::orderBy('number')->get(),
            'categories' => config('housekeeping.incident_categories'),
        ]);
    }

    public function storeIncident(Request $request)
    {
        $data = $request->validate([
            'room_id' => ['nullable', 'exists:rooms,id'],
            'category' => ['required', Rule::in(array_keys(config('housekeeping.incident_categories')))],
            'description' => ['required', 'string', 'max:800'],
            'raise_ticket' => ['nullable', 'boolean'],
        ]);

        $incident = HousekeepingIncident::create([
            'hotel_id' => Hotel::current()->id,
            'room_id' => $data['room_id'] ?? null,
            'reported_by' => $request->user()->id,
            'category' => $data['category'],
            'description' => $data['description'],
            'status' => 'open',
        ]);

        // Un incident technique peut ouvrir un ticket de maintenance.
        if (($data['raise_ticket'] ?? false) || $data['category'] === 'maintenance') {
            $ticket = MaintenanceTicket::create([
                'reference' => PreventiveMaintenance::reference(),
                'hotel_id' => $incident->hotel_id,
                'room_id' => $incident->room_id,
                'reported_by' => $request->user()->id,
                'type' => 'corrective',
                'priority' => 'normal',
                'status' => 'open',
                'title' => 'Signalement housekeeping — '.$incident->categoryLabel(),
                'description' => $incident->description,
            ]);
            $incident->update(['maintenance_ticket_id' => $ticket->id]);
        }

        return back()->with('status', 'Incident enregistré.');
    }

    public function resolveIncident(HousekeepingIncident $incident)
    {
        $incident->update(['status' => 'resolved', 'resolved_at' => now()]);

        return back()->with('status', 'Incident clôturé.');
    }
}
