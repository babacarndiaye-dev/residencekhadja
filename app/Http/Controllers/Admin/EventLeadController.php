<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\EventLead;
use App\Models\EventLeadActivity;
use App\Models\Hotel;
use App\Models\User;
use App\Services\SalesPipeline;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EventLeadController extends Controller
{
    public function index(Request $request)
    {
        $hotel = Hotel::current();

        return view('admin.events.pipeline', [
            'board' => SalesPipeline::board($hotel->id),
            'stages' => config('events.pipeline_stages'),
            'forecast' => SalesPipeline::weightedForecast($hotel->id),
            'wonValue' => EventLead::where('hotel_id', $hotel->id)->where('status', 'gagne')->sum('estimated_value'),
            'openTasks' => EventLeadActivity::where('type', 'task')->where('done', false)
                ->with('lead')->orderBy('due_at')->limit(10)->get(),
        ]);
    }

    public function create()
    {
        return view('admin.events.lead-form', [
            'lead' => new EventLead,
            'types' => config('events.event_types'),
            'sources' => config('events.lead_sources'),
            'owners' => User::whereIn('role', ['commercial', 'direction', 'admin'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $lead = EventLead::create($data + [
            'hotel_id' => Hotel::current()->id,
            'reference' => 'LEAD-'.now()->format('y').strtoupper(Str::random(4)),
            'status' => 'nouveau',
            'owner_id' => $data['owner_id'] ?? $request->user()->id,
        ]);
        AuditLog::record('event.lead.created', $lead);

        return redirect()->route('admin.events.leads.show', $lead)->with('status', 'Affaire créée.');
    }

    public function show(EventLead $lead)
    {
        $lead->load(['activities.user', 'quotes.items', 'events', 'owner', 'guest']);

        return view('admin.events.lead-show', [
            'lead' => $lead,
            'activityTypes' => config('events.activity_types'),
            'stages' => config('events.pipeline_stages'),
            'transitions' => SalesPipeline::TRANSITIONS[$lead->status] ?? [],
        ]);
    }

    public function update(Request $request, EventLead $lead)
    {
        $lead->update($this->validated($request));
        AuditLog::record('event.lead.updated', $lead);

        return back()->with('status', 'Affaire mise à jour.');
    }

    public function advance(Request $request, EventLead $lead)
    {
        $data = $request->validate([
            'status' => ['required', 'string'],
            'lost_reason' => ['nullable', 'string', 'max:180'],
        ]);

        SalesPipeline::advance($lead, $data['status'], ['lost_reason' => $data['lost_reason'] ?? null]);
        AuditLog::record('event.lead.stage', $lead, ['to' => $data['status']]);

        return back()->with('status', "Affaire déplacée vers « {$lead->statusLabel()} ».");
    }

    public function storeActivity(Request $request, EventLead $lead)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(config('events.activity_types')))],
            'subject' => ['required', 'string', 'max:160'],
            'body' => ['nullable', 'string', 'max:2000'],
            'due_at' => ['nullable', 'date'],
        ]);

        $lead->activities()->create($data + [
            'user_id' => $request->user()->id,
            'occurred_at' => now(),
            'done' => false,
        ]);

        return back()->with('status', 'Activité enregistrée.');
    }

    public function completeActivity(EventLead $lead, EventLeadActivity $activity)
    {
        abort_unless($activity->event_lead_id === $lead->id, 404);
        $activity->update(['done' => true]);

        return back()->with('status', 'Tâche terminée.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'company' => ['nullable', 'string', 'max:150'],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:180'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'event_type' => ['required', Rule::in(array_keys(config('events.event_types')))],
            'expected_start' => ['nullable', 'date'],
            'expected_end' => ['nullable', 'date', 'after_or_equal:expected_start'],
            'pax' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'estimated_value' => ['nullable', 'integer', 'min:0'],
            'source' => ['nullable', Rule::in(array_keys(config('events.lead_sources')))],
            'owner_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
