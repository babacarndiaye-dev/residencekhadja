<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\EventLead;
use App\Models\EventSpace;
use App\Models\EventSpaceBooking;
use App\Models\Hotel;
use App\Services\EventBooking;
use App\Services\EventQuoteRequest;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class MeetingRoomController extends Controller
{
    /** Planning d'occupation des salles sur 7 jours + demandes de devis. */
    public function index(Request $request)
    {
        $hotelId = Hotel::current()->id;
        $from = ($request->date('from') ?? Carbon::today())->startOfDay();
        $days = collect(range(0, 6))->map(fn ($i) => $from->copy()->addDays($i));

        $bookings = EventSpaceBooking::with(['event', 'space'])
            ->whereBetween('starts_at', [$from, $from->copy()->addDays(7)])
            ->orderBy('starts_at')
            ->get()
            ->groupBy('event_space_id');

        $canQuote = $request->user()->hasRole('commercial', 'direction');

        $quoteRequests = $canQuote
            ? EventLead::query()
                ->where('hotel_id', $hotelId)
                ->where('source', 'salle')
                ->whereIn('status', EventLead::OPEN)
                ->with(['owner', 'quotes' => fn ($q) => $q->latest()])
                ->latest()
                ->limit(12)
                ->get()
            : collect();

        return view('admin.salles.index', [
            'from' => $from,
            'days' => $days,
            'spaces' => EventSpace::orderBy('sort_order')->orderBy('name')->get(),
            'bookings' => $bookings,
            'canQuote' => $canQuote,
            'quoteRequests' => $quoteRequests,
            'eventTypes' => config('events.event_types'),
            'layouts' => config('events.layouts'),
            'catering' => config('events.catering_packages'),
            'equipment' => config('events.av_equipment'),
        ]);
    }

    /**
     * Demande de devis salle : crée une affaire (pipeline) + un devis brouillon
     * pré-rempli (location + restauration + technique) et bascule vers l'éditeur
     * de devis du module Événements.
     */
    public function storeQuoteRequest(Request $request)
    {
        $cateringCodes = array_column(config('events.catering_packages'), 'code');
        $equipmentCodes = array_column(config('events.av_equipment'), 'code');

        $data = $request->validate([
            'company' => ['nullable', 'string', 'max:150'],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:180'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'event_type' => ['required', Rule::in(array_keys(config('events.event_types')))],
            'event_space_id' => ['nullable', Rule::exists('event_spaces', 'id')],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'duration' => ['required', Rule::in(['half_day', 'full_day'])],
            'pax' => ['required', 'integer', 'min:1', 'max:5000'],
            'layout' => ['nullable', Rule::in(config('events.layouts'))],
            'catering' => ['nullable', 'array'],
            'catering.*' => [Rule::in($cateringCodes)],
            'equipment' => ['nullable', 'array'],
            'equipment.*' => [Rule::in($equipmentCodes)],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $pax = (int) $data['pax'];
        $space = ! empty($data['event_space_id']) ? EventSpace::find($data['event_space_id']) : null;
        $start = Carbon::parse($data['date'].' '.($data['start_time'] ?? '09:00'));
        $end = Carbon::parse($data['date'].' '.($data['end_time'] ?? '17:00'));
        $typeLabel = config("events.event_types.{$data['event_type']}");

        $quote = EventQuoteRequest::create(
            $data,
            $request->user()->id,
            'Demande de devis créée depuis le planning des salles.',
        );

        // Alerte de conflit de salle (informative — la demande n'engage rien).
        $clash = $space && ! EventBooking::isFree($space, $start, $end)
            ? " ⚠️ {$space->name} est déjà occupée sur ce créneau."
            : '';

        Notify::roles(
            ['commercial', 'direction'],
            'Nouvelle demande de devis salle',
            "{$data['contact_name']} · {$typeLabel} · {$pax} pers. · ".Carbon::parse($data['date'])->translatedFormat('d M Y'),
            route('admin.events.quotes.edit', $quote, false),
            icon: '📋',
        );
        AuditLog::record('event.quote.request', $quote, [
            'space' => $space?->name,
            'lead' => $quote->lead->reference,
        ]);

        return redirect()
            ->route('admin.events.quotes.edit', $quote)
            ->with('status', "Demande enregistrée — devis {$quote->reference} créé en brouillon.".$clash);
    }
}
