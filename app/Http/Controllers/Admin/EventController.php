<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventAgendaItem;
use App\Models\EventSpace;
use App\Models\EventSpaceBooking;
use App\Models\Hotel;
use App\Services\EventBooking;
use App\Services\FinanceLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $month = Carbon::parse($request->query('month', now()->format('Y-m')).'-01');

        $events = Event::query()
            ->with('spaceBookings.space')
            ->inMonth($month->year, $month->month)
            ->orderBy('starts_at')
            ->get();

        return view('admin.events.calendar', [
            'month' => $month,
            'events' => $events,
            'upcoming' => Event::whereIn('status', ['option', 'confirme'])
                ->where('starts_at', '>=', now()->startOfDay())
                ->orderBy('starts_at')->limit(12)->get(),
            'expiringOptions' => Event::where('status', 'option')
                ->whereNotNull('option_expires_on')
                ->where('option_expires_on', '<=', now()->addDays(3)->toDateString())
                ->orderBy('option_expires_on')->get(),
            'statuses' => Event::STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'event_type' => ['required', Rule::in(array_keys(config('events.event_types')))],
            'client_name' => ['required', 'string', 'max:150'],
            'pax' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'rooms_to_block' => ['nullable', 'integer', 'min:0', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $event = Event::create($data + [
            'hotel_id' => Hotel::current()->id,
            'reference' => 'EVT-'.strtoupper(Str::random(6)),
            'status' => 'option',
            'option_expires_on' => now()->addDays(config('events.option_hold_days', 7))->toDateString(),
            'created_by' => $request->user()->id,
        ]);
        AuditLog::record('event.created', $event);

        return redirect()->route('admin.events.show', $event)->with('status', 'Événement créé en option.');
    }

    public function show(Event $event)
    {
        $event->load(['spaceBookings.space', 'agenda', 'quote.items', 'lead', 'guest']);

        return view('admin.events.show', [
            'event' => $event,
            'spaces' => EventSpace::where('is_active', true)->orderBy('sort_order')->get(),
            'agendaCategories' => config('events.agenda_categories'),
            'layouts' => config('events.layouts'),
        ]);
    }

    public function addSpace(Request $request, Event $event)
    {
        $data = $request->validate([
            'event_space_id' => ['required', 'exists:event_spaces,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'layout' => ['nullable', Rule::in(config('events.layouts'))],
            'setup_notes' => ['nullable', 'string', 'max:180'],
        ]);

        EventBooking::book(
            $event,
            EventSpace::findOrFail($data['event_space_id']),
            Carbon::parse($data['starts_at']),
            Carbon::parse($data['ends_at']),
            $data['layout'] ?? null,
            $data['setup_notes'] ?? null,
        );

        return back()->with('status', 'Salle affectée à l’événement.');
    }

    public function removeSpace(Event $event, EventSpaceBooking $booking)
    {
        abort_unless($booking->event_id === $event->id, 404);
        $booking->delete();

        return back()->with('status', 'Salle retirée.');
    }

    public function confirm(Event $event)
    {
        EventBooking::confirm($event);
        AuditLog::record('event.confirmed', $event);

        return back()->with('status', "Événement {$event->reference} confirmé.");
    }

    public function cancel(Request $request, Event $event)
    {
        $event->update(['status' => 'annule']);
        AuditLog::record('event.cancelled', $event);

        return back()->with('status', 'Événement annulé — salles libérées.');
    }

    public function complete(Event $event)
    {
        abort_unless($event->status === 'confirme', 422);
        $event->update(['status' => 'realise']);

        return back()->with('status', 'Événement marqué réalisé.');
    }

    /* --------------------------- Feuille de fonction (BEO) --------------------------- */

    public function storeAgendaItem(Request $request, Event $event)
    {
        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'duration_min' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'area' => ['nullable', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:160'],
            'detail' => ['nullable', 'string', 'max:1000'],
            'responsible' => ['nullable', 'string', 'max:120'],
            'category' => ['required', Rule::in(array_keys(config('events.agenda_categories')))],
        ]);

        $event->agenda()->create($data);

        return back()->with('status', 'Ligne ajoutée à la feuille de fonction.');
    }

    public function deleteAgendaItem(Event $event, EventAgendaItem $item)
    {
        abort_unless($item->event_id === $event->id, 404);
        $item->delete();

        return back()->with('status', 'Ligne supprimée.');
    }

    /* ------------------------------- Finance ------------------------------- */

    public function payDeposit(Request $request, Event $event)
    {
        $amount = (int) ($event->quote?->deposit_amount ?? 0);
        abort_if($amount <= 0, 422, 'Aucun acompte à encaisser (devis manquant).');
        abort_if($event->deposit_paid, 422, 'Acompte déjà encaissé.');

        FinanceLedger::record([
            'direction' => 'income',
            'category' => 'evenements',
            'method' => $request->input('method', 'virement'),
            'amount' => $amount,
            'label' => "Acompte événement {$event->reference} — {$event->name}",
            'operation_date' => now(),
            'source' => $event,
        ]);

        $event->update(['deposit_invoiced' => true, 'deposit_paid' => true]);
        AuditLog::record('event.deposit.paid', $event, ['amount' => $amount]);

        return back()->with('status', 'Acompte encaissé et comptabilisé.');
    }

    public function paySettlement(Request $request, Event $event)
    {
        $balance = $event->balanceDue();
        abort_if($balance <= 0, 422, 'Aucun solde à encaisser.');

        FinanceLedger::record([
            'direction' => 'income',
            'category' => 'evenements',
            'method' => $request->input('method', 'virement'),
            'amount' => $balance,
            'label' => "Solde événement {$event->reference} — {$event->name}",
            'operation_date' => now(),
            'source' => $event,
        ]);

        $event->update(['settled' => true]);
        AuditLog::record('event.settled', $event, ['amount' => $balance]);

        return back()->with('status', 'Solde encaissé et comptabilisé.');
    }

    public function contract(Event $event)
    {
        $event->load('quote.items', 'spaceBookings.space', 'lead');

        return view('admin.events.contract', ['event' => $event]);
    }

    /* -------------------------------- Salles -------------------------------- */

    public function spaces()
    {
        return view('admin.events.spaces', [
            'spaces' => EventSpace::orderBy('sort_order')->get(),
            'layouts' => config('events.layouts'),
        ]);
    }

    public function storeSpace(Request $request)
    {
        $data = $this->validatedSpace($request);
        $data['slug'] = Str::slug($data['name']);
        $data['layouts'] = $this->layoutMap($request);

        EventSpace::create($data + ['hotel_id' => Hotel::current()->id, 'is_active' => true]);

        return back()->with('status', 'Salle créée.');
    }

    public function updateSpace(Request $request, EventSpace $space)
    {
        $data = $this->validatedSpace($request);
        $data['layouts'] = $this->layoutMap($request);
        $data['is_active'] = $request->boolean('is_active');

        $space->update($data);

        return back()->with('status', 'Salle mise à jour.');
    }

    private function validatedSpace(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'area' => ['nullable', 'integer', 'min:1'],
            'half_day_price' => ['nullable', 'integer', 'min:0'],
            'full_day_price' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:60'],
            'image' => ['nullable', 'url', 'max:300'],
        ]);
    }

    private function layoutMap(Request $request): array
    {
        $out = [];
        foreach ((array) $request->input('layout_capacity', []) as $layout => $cap) {
            if (in_array($layout, config('events.layouts'), true) && (int) $cap > 0) {
                $out[$layout] = (int) $cap;
            }
        }

        return $out;
    }
}
