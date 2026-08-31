<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\AvailabilityDay;
use App\Models\Channel;
use App\Models\ChannelRatePlan;
use App\Models\ChannelReservation;
use App\Models\ChannelSyncLog;
use App\Models\Hotel;
use App\Models\RatePlan;
use App\Models\RoomCategory;
use App\Services\Availability;
use App\Services\ChannelManager;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class DistributionController extends Controller
{
    public function index()
    {
        return view('admin.distribution.index', [
            'channels' => Channel::withCount(['reservations', 'syncLogs'])->orderByDesc('type')->orderBy('name')->get(),
            'connectors' => config('distribution.connectors'),
            'roomCategories' => RoomCategory::active()->orderBy('name')->pluck('name', 'slug'),
            'commission30d' => ChannelReservation::where('commission_posted', true)
                ->whereHas('reservation', fn ($q) => $q->where('checked_out_at', '>=', now()->subDays(30)))
                ->sum('commission_amount'),
            'pendingCommission' => ChannelReservation::where('commission_posted', false)->sum('commission_amount'),
            'lastLogs' => ChannelSyncLog::with('channel')->latest('id')->limit(12)->get(),
        ]);
    }

    public function updateChannel(Request $request, Channel $channel)
    {
        $data = $request->validate([
            'connector' => ['required', Rule::in(array_keys(config('distribution.connectors')))],
            'commission_rate' => ['required', 'numeric', 'between:0,0.5'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
            'ical_url' => ['nullable', 'url', 'max:500'],
            'room_slug' => ['nullable', 'string', 'exists:room_categories,slug'],
        ]);

        $credentials = $channel->credentials ?? [];
        if ($data['connector'] === 'ical') {
            $credentials['ical_url'] = $data['ical_url'] ?? null;
            $credentials['room_slug'] = $data['room_slug'] ?? null;
        }

        $channel->update([
            'connector' => $data['connector'],
            'commission_rate' => $data['commission_rate'],
            'is_active' => $request->boolean('is_active'),
            'notes' => $data['notes'] ?? null,
            'credentials' => $credentials ?: null,
        ]);
        AuditLog::record('distribution.channel.updated', $channel);

        return back()->with('status', "Canal « {$channel->name} » mis à jour.");
    }

    public function push(Request $request, Channel $channel)
    {
        $from = Carbon::today();
        $to = Carbon::today()->addDays((int) config('distribution.push_horizon_days', 120));

        ChannelManager::pushAvailability($from, $to, $channel);
        ChannelManager::pushRates($from, $to, $channel);

        return back()->with('status', "Disponibilité et tarifs poussés vers {$channel->name}.");
    }

    /** Vérifie que le connecteur du canal répond (iCal joignable, format valide…). */
    public function testConnection(Channel $channel)
    {
        $res = ChannelManager::connector($channel)->testConnection($channel);

        return back()->with('status', ($res['ok'] ? '✅ ' : '⚠️ ')."{$channel->name} — {$res['message']}");
    }

    /** Importe les réservations / blocages entrants du canal (iCal). */
    public function pull(Channel $channel)
    {
        ChannelManager::pullReservations($channel);
        AuditLog::record('distribution.channel.pull', $channel);

        return back()->with('status', "Import lancé pour « {$channel->name} » — voir le journal de synchronisation.");
    }

    /* ----------------------------- Calendrier ARI ---------------------- */

    public function calendar(Request $request)
    {
        $category = $request->filled('category')
            ? RoomCategory::where('slug', $request->query('category'))->firstOrFail()
            : RoomCategory::active()->orderBy('sort_order')->first();

        $start = $request->filled('start') ? Carbon::parse($request->query('start')) : Carbon::today();
        $end = $start->copy()->addDays(29);

        $days = AvailabilityDay::where('room_category_id', $category->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString().' 23:59:59'])
            ->get()->keyBy(fn ($d) => $d->date->toDateString());

        $grid = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $iso = $d->toDateString();
            $row = $days->get($iso);
            $grid[] = [
                'date' => $d->copy(),
                'open' => Availability::remaining($category, $iso, $d->copy()->addDay()->toDateString()),
                'row' => $row,
            ];
        }

        return view('admin.distribution.calendar', [
            'categories' => RoomCategory::active()->orderBy('sort_order')->get(),
            'category' => $category,
            'start' => $start,
            'end' => $end,
            'grid' => $grid,
        ]);
    }

    public function updateCalendar(Request $request)
    {
        $data = $request->validate([
            'room_category_id' => ['required', 'exists:room_categories,id'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'action' => ['required', Rule::in(['open', 'close', 'restrict', 'clear'])],
            'rooms_open' => ['nullable', 'integer', 'min:0', 'max:200'],
            'min_stay' => ['nullable', 'integer', 'min:1', 'max:30'],
            'max_stay' => ['nullable', 'integer', 'min:1', 'max:60'],
            'cta' => ['nullable', 'boolean'],
            'ctd' => ['nullable', 'boolean'],
        ]);

        $from = Carbon::parse($data['from']);
        $to = Carbon::parse($data['to']);
        $count = 0;

        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $day = AvailabilityDay::where('room_category_id', $data['room_category_id'])
                ->whereDate('date', $d->toDateString())->first()
                ?? new AvailabilityDay(['room_category_id' => $data['room_category_id'], 'date' => $d->toDateString()]);

            $day->hotel_id = Hotel::current()->id;

            match ($data['action']) {
                'close' => $day->stop_sell = true,
                'open' => $day->stop_sell = false,
                'clear' => $day->forceFill(['stop_sell' => false, 'cta' => false, 'ctd' => false, 'min_stay' => 1, 'max_stay' => null, 'rooms_open' => null]),
                'restrict' => $day->forceFill([
                    'rooms_open' => $data['rooms_open'] ?? $day->rooms_open,
                    'min_stay' => $data['min_stay'] ?? 1,
                    'max_stay' => $data['max_stay'] ?? null,
                    'cta' => $request->boolean('cta'),
                    'ctd' => $request->boolean('ctd'),
                ]),
            };

            $day->save();
            $count++;
        }

        return back()->with('status', "{$count} jour(s) mis à jour. Pensez à pousser vers les canaux.");
    }

    /* ------------------------------- Tarifs -------------------------- */

    public function rates()
    {
        $channels = Channel::where('key', '!=', 'direct')->orderBy('name')->get();
        $plans = RatePlan::where('is_active', true)->get();

        $map = ChannelRatePlan::get()->groupBy('channel_id')->map(fn ($g) => $g->keyBy('rate_plan_id'));

        return view('admin.distribution.rates', compact('channels', 'plans', 'map'));
    }

    public function updateRates(Request $request, Channel $channel)
    {
        $data = $request->validate([
            'plan' => ['required', 'array'],
            'plan.*.markup' => ['nullable', 'numeric', 'between:-0.5,1'],
            'plan.*.active' => ['nullable', 'boolean'],
        ]);

        foreach ($data['plan'] as $planId => $row) {
            ChannelRatePlan::updateOrCreate(
                ['channel_id' => $channel->id, 'rate_plan_id' => $planId],
                ['markup_rate' => $row['markup'] ?? 0, 'is_active' => (bool) ($row['active'] ?? false)],
            );
        }

        return back()->with('status', "Grille tarifaire de {$channel->name} enregistrée.");
    }

    /* ------------------------ Réservations de canal ------------------- */

    public function reservations()
    {
        return view('admin.distribution.reservations', [
            'rows' => ChannelReservation::with('channel', 'reservation.guest')->latest()->paginate(30),
            'channels' => Channel::where('key', '!=', 'direct')->where('is_active', true)->orderBy('name')->get(),
            'categories' => RoomCategory::active()->orderBy('sort_order')->get(),
        ]);
    }

    public function simulateReservation(Request $request)
    {
        $data = $request->validate([
            'channel' => ['required', 'exists:channels,key'],
            'room_slug' => ['required', 'exists:room_categories,slug'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['nullable', 'email'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['nullable', 'integer', 'min:1', 'max:10'],
            'gross_amount' => ['required', 'integer', 'min:1000'],
        ]);

        $channel = Channel::where('key', $data['channel'])->firstOrFail();

        $cr = ChannelManager::ingestReservation($channel, [
            'external_ref' => strtoupper($channel->key).'-'.now()->format('ymdHis'),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'] ?? null,
            'room_slug' => $data['room_slug'],
            'check_in' => $data['check_in'],
            'check_out' => $data['check_out'],
            'adults' => $data['adults'] ?? 2,
            'gross_amount' => $data['gross_amount'],
        ]);

        return back()->with('status', "Réservation {$channel->name} importée : {$cr->reservation->reference}.");
    }

    public function log()
    {
        return view('admin.distribution.log', [
            'logs' => ChannelSyncLog::with('channel')->latest('id')->paginate(50),
        ]);
    }
}
