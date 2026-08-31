<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ReservationCancelled;
use App\Mail\ReservationConfirmed;
use App\Mail\StayCompleted;
use App\Models\AuditLog;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\HousekeepingTask;
use App\Models\Payment;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Services\Availability;
use App\Services\BookingQuote;
use App\Services\GuestApp;
use App\Services\HousekeepingPlanner;
use App\Services\ReservationBooking;
use App\Services\Sms;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(Reservation::STATUSES))],
            'q' => ['nullable', 'string', 'max:80'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        // Une ligne par séjour : la 1re chambre d'un groupe représente tout le groupe.
        $reservations = Reservation::query()
            ->with(['guest', 'roomCategory', 'room'])
            ->where(function ($q) {
                $q->whereNull('group_reference')
                    ->orWhereRaw('id = (select min(id) from reservations r2 where r2.group_reference = reservations.group_reference)');
            })
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('check_in', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('check_in', '<=', $d))
            ->when($filters['q'] ?? null, function ($q, $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('reference', 'like', "%{$term}%")
                        ->orWhere('group_reference', 'like', "%{$term}%")
                        ->orWhereHas('guest', fn ($g) => $g
                            ->where('last_name', 'like', "%{$term}%")
                            ->orWhere('first_name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%"));
                });
            })
            ->latest('check_in')
            ->paginate(20)
            ->withQueryString();

        // Agrégats par groupe (nb chambres, total consolidé, statuts distincts).
        $groups = Reservation::query()
            ->whereIn('group_reference', $reservations->pluck('group_reference')->filter()->all())
            ->selectRaw('group_reference, count(*) as rooms, sum(total) as total, group_concat(distinct status) as statuses')
            ->groupBy('group_reference')
            ->get()
            ->keyBy('group_reference');

        return view('admin.reservations.index', [
            'reservations' => $reservations,
            'groups' => $groups,
            'filters' => $filters,
            'statuses' => Reservation::STATUSES,
        ]);
    }

    public function show(Reservation $reservation)
    {
        // Toutes les chambres du séjour ; la 1re porte le folio consolidé.
        $members = $reservation->groupSiblings()
            ->load(['roomCategory', 'room', 'charges']);
        $primary = $members->first();
        $primary->load(['guest', 'ratePlan', 'hotel', 'payments.recordedBy']);

        $assignableRooms = Room::whereIn('room_category_id', $members->pluck('room_category_id')->unique())
            ->where('is_active', true)
            ->whereIn('status', Room::ASSIGNABLE)
            ->orderBy('number')
            ->get()
            ->groupBy('room_category_id');

        return view('admin.reservations.show', [
            'reservation' => $primary,
            'members' => $members,
            'grouped' => $primary->isGrouped(),
            'assignableRooms' => $assignableRooms,
            'paymentMethods' => Payment::METHODS,
        ]);
    }

    public function confirm(Reservation $reservation)
    {
        $members = $reservation->groupSiblings();
        $pending = $members->where('status', 'pending');
        abort_if($pending->isEmpty(), 422);

        $pending->each->update(['status' => 'confirmed', 'confirmed_at' => now()]);

        $primary = $members->first();
        AuditLog::record('reservation.confirmed', $primary, ['rooms' => $pending->count()]);

        // Les notifications ne doivent jamais faire échouer la confirmation.
        try {
            if ($primary->guest->email) {
                Mail::to($primary->guest->email)->queue(new ReservationConfirmed($primary->fresh()));
            }
            Sms::queueTemplate($primary->guest->phone, 'reservation_confirmed', [
                'ref' => $primary->groupKey(),
                'in' => $primary->check_in->format('d/m/Y'),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('status', "Réservation {$primary->groupKey()} confirmée.");
    }

    public function cancel(Request $request, Reservation $reservation)
    {
        $members = $reservation->groupSiblings();
        $active = $members->reject(fn ($m) => in_array($m->status, ['checked_out', 'cancelled'], true));
        abort_if($active->isEmpty(), 422);

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        DB::transaction(function () use ($active, $data) {
            foreach ($active as $m) {
                if ($m->room) {
                    $m->room->update(['status' => 'sale']);
                }
                $m->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'room_id' => null,
                    'notes' => trim(($m->notes ? $m->notes."\n" : '').'Annulation : '.($data['reason'] ?? '—')),
                ]);
            }
        });

        $primary = $members->first();
        AuditLog::record('reservation.cancelled', $primary, ['reason' => $data['reason'] ?? null, 'rooms' => $active->count()]);

        if (filled($primary->guest->email)) {
            Mail::to($primary->guest->email)->queue(new ReservationCancelled($primary));
        }

        return back()->with('status', "Réservation {$primary->groupKey()} annulée.");
    }

    public function noShow(Reservation $reservation)
    {
        $members = $reservation->groupSiblings();
        $pending = $members->whereIn('status', ['pending', 'confirmed']);
        abort_if($pending->isEmpty(), 422);

        $pending->each->update(['status' => 'no_show']);

        $primary = $members->first();
        AuditLog::record('reservation.no_show', $primary, ['rooms' => $pending->count()]);

        return back()->with('status', "Réservation {$primary->groupKey()} marquée no-show.");
    }

    public function checkIn(Request $request, Reservation $reservation)
    {
        $members = $reservation->groupSiblings();
        $checkable = $members->filter->canCheckIn()->values();
        abort_if($checkable->isEmpty(), 422);

        // Compat mono-chambre : `room_id` → `assign[<id>]`.
        if ($request->filled('room_id') && ! $request->has('assign')) {
            $request->merge(['assign' => [$reservation->getKey() => $request->input('room_id')]]);
        }

        $data = $request->validate([
            'assign' => ['required', 'array'],
            'assign.*' => ['required', 'integer', 'distinct'],
        ], [], ['assign.*' => 'chambre']);

        DB::transaction(function () use ($checkable, $data) {
            foreach ($checkable as $m) {
                $roomId = $data['assign'][$m->getKey()] ?? null;
                abort_if(! $roomId, 422, "Chambre non renseignée pour {$m->reference}.");

                $room = Room::lockForUpdate()->findOrFail($roomId);
                abort_unless(
                    $room->room_category_id === $m->room_category_id && $room->isAssignable(),
                    422,
                    "La chambre {$room->number} n'est pas attribuable pour {$m->reference}."
                );

                $room->update(['status' => 'occupee']);
                $m->update([
                    'room_id' => $room->id,
                    'status' => 'checked_in',
                    'checked_in_at' => now(),
                    'confirmed_at' => $m->confirmed_at ?? now(),
                ]);
            }
        });

        $primary = $members->first();
        AuditLog::record('reservation.checked_in', $primary, ['rooms' => $checkable->count()]);

        return back()->with('status', "Check-in effectué pour {$primary->groupKey()}.");
    }

    public function checkOut(Request $request, Reservation $reservation)
    {
        $members = $reservation->groupSiblings();
        $inHouse = $members->filter->canCheckOut();
        abort_if($inHouse->isEmpty(), 422);

        $data = $request->validate([
            'settle_balance' => ['nullable', 'boolean'],
            'method' => ['nullable', Rule::in(array_keys(Payment::METHODS))],
        ]);

        $primary = $members->first();

        DB::transaction(function () use ($members, $inHouse, $primary, $data, $request) {
            // Solde consolidé du séjour, encaissé en une écriture sur la 1re chambre.
            $balance = (int) $members->sum(fn ($m) => $m->balance());
            if (($data['settle_balance'] ?? false) && $balance > 0) {
                $primary->payments()->create([
                    'recorded_by' => $request->user()->id,
                    'amount' => $balance,
                    'method' => $data['method'] ?? 'especes',
                    'type' => 'balance',
                    'received_at' => now(),
                ]);
            }

            // Un seul numéro de facture pour tout le séjour.
            $invoiceNumber = $primary->invoice_number ?? 'F-'.now()->format('Ymd').'-'.$primary->getKey();

            foreach ($inHouse as $m) {
                if ($m->room) {
                    $m->room->update(['status' => 'sale']);

                    // Housekeeping : tâche de recouche départ (§33).
                    if (! HousekeepingPlanner::taskExists($m->room_id, now(), 'departure')) {
                        HousekeepingTask::create([
                            'hotel_id' => $m->hotel_id,
                            'room_id' => $m->room_id,
                            'reservation_id' => $m->id,
                            'service_date' => today()->toDateString(),
                            'type' => 'departure',
                            'status' => 'pending',
                            'priority' => 'high',
                        ]);
                    }
                }

                $m->update([
                    'status' => 'checked_out',
                    'checked_out_at' => now(),
                    'invoice_number' => $m->invoice_number ?? $invoiceNumber,
                ]);
            }
        });

        AuditLog::record('reservation.checked_out', $primary, ['rooms' => $inHouse->count()]);

        if (filled($primary->guest->email)) {
            Mail::to($primary->guest->email)->queue(new StayCompleted($primary->fresh()));
        }

        return redirect()
            ->route('admin.reservations.invoice', $primary)
            ->with('status', "Check-out effectué pour {$primary->groupKey()}.");
    }

    public function storePayment(Request $request, Reservation $reservation)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'type' => ['required', Rule::in(array_keys(Payment::TYPES))],
            'reference' => ['nullable', 'string', 'max:60'],
        ]);

        // Folio consolidé : le paiement est porté par la 1re chambre du séjour.
        $target = $reservation->groupPrimary();

        $target->payments()->create([
            'recorded_by' => $request->user()->id,
            'amount' => $data['type'] === 'refund' ? -abs($data['amount']) : $data['amount'],
            'method' => $data['method'],
            'type' => $data['type'],
            'reference' => $data['reference'] ?? null,
            'received_at' => now(),
        ]);

        AuditLog::record('payment.recorded', $target, ['amount' => $data['amount'], 'type' => $data['type']]);

        return back()->with('status', 'Paiement enregistré.');
    }

    public function invoice(Reservation $reservation)
    {
        $members = $reservation->groupSiblings()
            ->load(['roomCategory', 'ratePlan', 'room', 'payments', 'charges']);
        $primary = $members->first();
        $primary->load(['guest', 'hotel']);

        return view('admin.reservations.invoice', [
            'reservation' => $primary,
            'members' => $members,
            'grouped' => $primary->isGrouped(),
        ]);
    }

    /* ------------------------ Réservation sur place (walk-in) ------------------------ */

    public function create(Request $request)
    {
        $checkIn = today();

        return view('admin.reservations.create', [
            'categories' => RoomCategory::active()->orderBy('sort_order')->get(),
            'ratePlans' => RatePlan::where('is_active', true)->orderBy('sort_order')->get(),
            'availability' => Availability::map($checkIn->toDateString(), $checkIn->copy()->addDay()->toDateString()),
            'checkIn' => $checkIn,
        ]);
    }

    public function store(Request $request)
    {
        // Compat mono-catégorie : room_category_id + rooms_count → lines[0].
        if (! $request->has('lines') && $request->filled('room_category_id')) {
            $request->merge(['lines' => [[
                'room_category_id' => $request->input('room_category_id'),
                'rooms_count' => $request->input('rooms_count', 1),
            ]]]);
        }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'country' => ['nullable', 'string', 'max:60'],
            'rate_plan_id' => ['nullable', 'exists:rate_plans,id'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1', 'max:30'],
            'children' => ['nullable', 'integer', 'min:0', 'max:20'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.room_category_id' => ['required', 'exists:room_categories,id'],
            'lines.*.rooms_count' => ['required', 'integer', 'min:1', 'max:10'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'check_in_now' => ['nullable', 'boolean'],
        ]);

        // Quantité voulue par catégorie (les doublons de lignes sont additionnés).
        $wanted = collect($data['lines'])
            ->groupBy('room_category_id')
            ->map(fn ($rows) => (int) collect($rows)->sum('rooms_count'));

        if ($wanted->sum() > 10) {
            return back()->withInput()->withErrors(['lines' => 'Maximum 10 chambres par réservation.']);
        }

        $categories = RoomCategory::whereIn('id', $wanted->keys())->get()->keyBy('id');
        $ratePlan = ! empty($data['rate_plan_id'])
            ? RatePlan::find($data['rate_plan_id'])
            : RatePlan::where('is_active', true)->orderBy('sort_order')->first();
        $multiplier = (float) ($ratePlan->multiplier ?? 1);
        $nights = max(1, nights_between($data['check_in'], $data['check_out']));

        try {
            $reservations = DB::transaction(function () use ($data, $wanted, $categories, $ratePlan, $multiplier, $nights, $request) {
                foreach ($wanted as $catId => $qty) {
                    if (! Availability::canBook($categories[$catId], $data['check_in'], $data['check_out'], $qty, lock: true)) {
                        throw new \RuntimeException('SOLD_OUT');
                    }
                }

                $quote = BookingQuote::for([
                    'check_in' => $data['check_in'],
                    'check_out' => $data['check_out'],
                    'adults' => $data['adults'],
                    'children' => $data['children'] ?? 0,
                    'room_lines' => $wanted->map(fn ($qty, $catId) => [
                        'slug' => $categories[$catId]->slug, 'qty' => $qty,
                    ])->values()->all(),
                    'rate_plan' => $ratePlan?->key ?? 'flexible',
                    'extras' => [],
                    'promo' => null,
                ]);

                $attrs = [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone' => $data['phone'] ?? null,
                    'country' => $data['country'] ?? null,
                    'acquisition_source' => 'walk_in',
                ];
                $match = match (true) {
                    ! empty($data['email']) => ['email' => $data['email']],
                    ! empty($data['phone']) => ['phone' => $data['phone'], 'last_name' => $data['last_name']],
                    default => null,
                };
                $guest = $match ? Guest::updateOrCreate($match, $attrs) : Guest::create($attrs);

                // Une entrée par chambre physique.
                $units = [];
                foreach ($wanted as $catId => $qty) {
                    $cat = $categories[$catId];
                    $nightly = (int) round($cat->price * $multiplier);
                    for ($k = 0; $k < $qty; $k++) {
                        $units[] = ['cat' => $cat, 'room_total' => $nightly * $nights];
                    }
                }

                $reservations = ReservationBooking::persist([
                    'hotel_id' => Hotel::current()->id,
                    'guest_id' => $guest->id,
                    'rate_plan_id' => $ratePlan?->id,
                    'status' => 'confirmed',
                    'channel' => 'walk_in',
                    'check_in' => $data['check_in'],
                    'check_out' => $data['check_out'],
                    'adults' => $data['adults'],
                    'children' => $data['children'] ?? 0,
                    'currency' => 'XOF',
                    'notes' => $data['notes'] ?? null,
                    'confirmed_at' => now(),
                    'source_ip' => $request->ip(),
                ], $units, $quote);

                // Installation immédiate : attribue une chambre propre à chaque ligne.
                if ($request->boolean('check_in_now')) {
                    $busy = Reservation::whereNotNull('room_id')->where('status', 'checked_in')->pluck('room_id')->all();
                    foreach ($reservations as $res) {
                        $room = Room::where('room_category_id', $res->room_category_id)
                            ->where('status', 'propre')->where('is_active', true)
                            ->whereNotIn('id', $busy)
                            ->lockForUpdate()->first();
                        if (! $room) {
                            continue;
                        }
                        $busy[] = $room->id;
                        $room->update(['status' => 'occupee']);
                        $res->update(['room_id' => $room->id, 'status' => 'checked_in', 'checked_in_at' => now()]);
                    }
                }

                return $reservations;
            });
        } catch (\RuntimeException) {
            return back()->withInput()->withErrors(['room_category_id' => 'Une catégorie sélectionnée est complète pour ces dates.']);
        }

        $primary = $reservations->first();
        AuditLog::record('reservation.walk_in', $primary, [
            'rooms' => $reservations->count(),
            'total' => (int) $reservations->sum('total'),
        ]);

        if ($primary->guest->email) {
            Mail::to($primary->guest->email)->queue(new ReservationConfirmed($primary));
        }

        Sms::queueTemplate($primary->guest->phone, 'reservation_confirmed', [
            'ref' => $primary->groupKey(),
            'in' => $primary->check_in->format('d/m/Y'),
        ]);

        $installed = $reservations->contains(fn ($r) => $r->status === 'checked_in');

        return redirect()->route('admin.reservations.show', $primary)
            ->with('status', "Réservation sur place {$primary->groupKey()} créée"
                .($installed ? ' — client installé.' : '.'));
    }

    /** QR du lien magique vers l'application invité (carte d'accueil). */
    public function appQr(Reservation $reservation)
    {
        $svg = (new Builder(
            writer: new SvgWriter,
            data: GuestApp::magicUrl($reservation),
            size: 220,
            margin: 4,
        ))->build();

        return response($svg->getString(), 200, ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'no-store']);
    }
}
