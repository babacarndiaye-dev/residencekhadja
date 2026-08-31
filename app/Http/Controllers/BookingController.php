<?php

namespace App\Http\Controllers;

use App\Mail\ReservationConfirmed;
use App\Models\AuditLog;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\RoomCategory;
use App\Services\Availability;
use App\Services\BookingQuote;
use App\Services\PaymentGateway;
use App\Services\ReservationBooking;
use App\Services\Sms;
use App\Support\Notify;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Tunnel de réservation — version vitrine (front only).
 *
 * Étapes : recherche → chambre → options → récapitulatif → confirmation.
 * L'état transite par la session sous la clé « booking ». Rien n'est
 * persisté : la confirmation produit une référence et une demande à
 * transmettre à la réception. Le branchement PMS + paiement + gestion
 * d'inventaire (anti-double réservation, transactions) relève d'une
 * phase ultérieure.
 */
class BookingController extends Controller
{
    private const KEY = 'booking';

    /** Étape 1 — recherche de disponibilité. */
    public function start(Request $request)
    {
        $booking = $request->session()->get(self::KEY, []);

        // Pré-remplissage possible depuis un lien (?check_in=...&adults=...).
        $booking = array_merge([
            'check_in' => $request->query('check_in', $booking['check_in'] ?? Carbon::tomorrow()->toDateString()),
            'check_out' => $request->query('check_out', $booking['check_out'] ?? Carbon::tomorrow()->addDays(2)->toDateString()),
            'adults' => (int) $request->query('adults', $booking['adults'] ?? 2),
            'children' => (int) $request->query('children', $booking['children'] ?? 0),
            'rooms' => (int) $request->query('rooms', $booking['rooms'] ?? 1),
            'promo' => $request->query('promo', $booking['promo'] ?? null),
        ], $booking);

        return view('pages.booking.search', [
            'booking' => $booking,
            'config' => config('booking'),
        ]);
    }

    /** Étape 1 (POST) — validation de la recherche. */
    public function storeSearch(Request $request)
    {
        $cfg = config('booking');

        $data = $request->validate([
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1', 'max:'.($cfg['max_guests_room'] * $cfg['max_rooms'])],
            'children' => ['nullable', 'integer', 'min:0', 'max:10'],
            'rooms' => ['required', 'integer', 'min:1', 'max:'.$cfg['max_rooms']],
            'promo' => ['nullable', 'string', 'max:30'],
        ], [], [
            'check_in' => "date d'arrivée",
            'check_out' => 'date de départ',
            'adults' => 'adultes',
            'children' => 'enfants',
            'rooms' => 'chambres',
        ]);

        $nights = nights_between($data['check_in'], $data['check_out']);
        if ($nights < $cfg['min_nights'] || $nights > $cfg['max_nights']) {
            return back()->withInput()->withErrors([
                'check_out' => "La durée du séjour doit être comprise entre {$cfg['min_nights']} et {$cfg['max_nights']} nuits.",
            ]);
        }

        $promoNote = null;
        if (! empty($data['promo'])) {
            $code = strtoupper(trim($data['promo']));
            $promoNote = isset($cfg['promo_codes'][$code])
                ? "Code « {$code} » appliqué : {$cfg['promo_codes'][$code]['label']}."
                : "Le code « {$code} » n'est pas reconnu et a été ignoré.";
        }

        $this->merge($request, [
            'check_in' => $data['check_in'],
            'check_out' => $data['check_out'],
            'adults' => (int) $data['adults'],
            'children' => (int) ($data['children'] ?? 0),
            'rooms' => (int) $data['rooms'],
            'promo' => ! empty($data['promo']) ? strtoupper(trim($data['promo'])) : null,
        ]);

        return redirect()->route('booking.rooms')->with('status', $promoNote);
    }

    /** Étape 2 — choix de la chambre et du tarif. */
    public function rooms(Request $request)
    {
        $booking = $this->guard($request, needsSearch: true);
        if ($booking instanceof RedirectResponse) {
            return $booking;
        }

        $roomsCount = max(1, (int) ($booking['rooms'] ?? 1));

        // Sélection courante : slug => quantité.
        $selected = collect(BookingQuote::normaliseLines($booking))->pluck('qty', 'slug')->all();

        return view('pages.booking.rooms', [
            'booking' => $booking,
            'quote' => BookingQuote::for($booking),
            'rooms' => RoomCategory::query()->active()->ordered()->get(),
            'availability' => Availability::map($booking['check_in'], $booking['check_out']),
            'roomsCount' => $roomsCount,
            'selected' => $selected,
            'ratePlan' => $booking['rate_plan'] ?? 'flexible',
            'ratePlans' => config('booking.rate_plans'),
        ]);
    }

    /** Étape 2 (POST) — enregistre le panier de chambres. */
    public function storeRoom(Request $request)
    {
        $booking = $this->guard($request, needsSearch: true);
        if ($booking instanceof RedirectResponse) {
            return $booking;
        }

        $data = $this->validateRoomLines($request);

        $issues = $this->availabilityIssues($booking, $data['lines']);
        if ($issues) {
            return back()->withErrors(['lines' => implode(' ', $issues)]);
        }

        $this->merge($request, [
            'rate_plan' => $data['rate_plan'],
            'room_lines' => $data['lines'],
            'rooms' => collect($data['lines'])->sum('qty'),
        ]);

        return redirect()->route('booking.extras');
    }

    /** Devis asynchrone (JSON) — recalcule sans persister, appelé par l'étape « Chambre ». */
    public function quote(Request $request)
    {
        $booking = $request->session()->get(self::KEY, []);
        if (empty($booking['check_in'])) {
            return response()->json(['ok' => false, 'message' => 'Séance expirée.'], 422);
        }

        $data = $this->validateRoomLines($request, requireOne: false);

        $transient = array_merge($booking, [
            'rate_plan' => $data['rate_plan'],
            'room_lines' => $data['lines'],
            'promo' => $request->string('promo')->trim()->value() ?: ($booking['promo'] ?? null),
        ]);

        $quote = BookingQuote::for($transient);
        $issues = $this->availabilityIssues($booking, $data['lines']);

        return response()->json([
            'ok' => $issues === [],
            'issues' => $issues,
            'quote' => [
                'nights' => $quote['nights'],
                'room_count' => $quote['room_count'],
                'room_lines' => $quote['room_lines'],
                'room_total' => $quote['room_total'],
                'extras_total' => $quote['extras_total'],
                'tourist_tax' => $quote['tourist_tax'],
                'discount' => $quote['discount'],
                'tax' => $quote['tax'],
                'total' => $quote['total'],
                'deposit' => $quote['deposit'],
            ],
        ]);
    }

    /**
     * Valide `rate_plan` + `lines[slug] => qty`.
     *
     * @return array{rate_plan:string, lines: array<int, array{slug:string, qty:int}>}
     */
    private function validateRoomLines(Request $request, bool $requireOne = true): array
    {
        $slugs = RoomCategory::query()->active()->pluck('slug')->all();
        $maxRooms = (int) config('booking.max_rooms', 5);

        $validated = $request->validate([
            'rate_plan' => ['required', 'string', 'in:'.implode(',', array_keys(config('booking.rate_plans')))],
            'lines' => ['array'],
            'lines.*' => ['integer', 'min:0', 'max:'.$maxRooms],
        ], [], ['rate_plan' => 'tarif']);

        $lines = collect($validated['lines'] ?? [])
            ->only($slugs)
            ->filter(fn ($qty) => (int) $qty > 0)
            ->map(fn ($qty, $slug) => ['slug' => $slug, 'qty' => (int) $qty])
            ->values()
            ->all();

        $total = collect($lines)->sum('qty');
        if ($requireOne && $total < 1) {
            throw ValidationException::withMessages(['lines' => 'Sélectionnez au moins une chambre.']);
        }
        if ($total > $maxRooms) {
            throw ValidationException::withMessages(['lines' => "Maximum {$maxRooms} chambres par réservation en ligne."]);
        }

        return ['rate_plan' => $validated['rate_plan'], 'lines' => $lines];
    }

    /** @return array<int,string> messages d'indisponibilité par catégorie */
    private function availabilityIssues(array $booking, array $lines): array
    {
        $issues = [];
        $cats = RoomCategory::query()->whereIn('slug', collect($lines)->pluck('slug'))->get()->keyBy('slug');
        foreach ($lines as $line) {
            $cat = $cats->get($line['slug']);
            if (! $cat) {
                continue;
            }
            if (! Availability::canBook($cat, $booking['check_in'], $booking['check_out'], $line['qty'])) {
                $issues[] = "« {$cat->name} » : plus assez de disponibilité pour vos dates.";
            }
        }

        return $issues;
    }

    /** Étape 3 — services additionnels & demandes spéciales. */
    public function extras(Request $request)
    {
        $booking = $this->guard($request, needsRoom: true);
        if ($booking instanceof RedirectResponse) {
            return $booking;
        }

        return view('pages.booking.extras', [
            'booking' => $booking,
            'quote' => BookingQuote::for($booking),
            'extras' => config('booking.extras'),
            'specialRequests' => config('booking.special_requests'),
        ]);
    }

    /** Étape 3 (POST). */
    public function storeExtras(Request $request)
    {
        $booking = $this->guard($request, needsRoom: true);
        if ($booking instanceof RedirectResponse) {
            return $booking;
        }

        $extraKeys = collect(config('booking.extras'))->pluck('key')->all();
        $requests = config('booking.special_requests');

        $data = $request->validate([
            'extras' => ['nullable', 'array'],
            'extras.*' => ['string', 'in:'.implode(',', $extraKeys)],
            'special_requests' => ['nullable', 'array'],
            'special_requests.*' => ['string', 'in:'.implode(',', $requests)],
        ]);

        $this->merge($request, [
            'extras' => array_values($data['extras'] ?? []),
            'special_requests' => array_values($data['special_requests'] ?? []),
        ]);

        return redirect()->route('booking.summary');
    }

    /** Étape 4 — récapitulatif & coordonnées. */
    public function summary(Request $request)
    {
        $booking = $this->guard($request, needsRoom: true);
        if ($booking instanceof RedirectResponse) {
            return $booking;
        }

        return view('pages.booking.summary', [
            'booking' => $booking,
            'quote' => BookingQuote::for($booking),
        ]);
    }

    /** Étape 4 (POST) — génère la référence et confirme. */
    public function confirm(Request $request)
    {
        $booking = $this->guard($request, needsRoom: true);
        if ($booking instanceof RedirectResponse) {
            return $booking;
        }

        $data = $request->validate([
            'civility' => ['required', 'in:Mme,M.,Autre'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['required', 'string', 'max:40'],
            'country' => ['required', 'string', 'max:80'],
            'company' => ['nullable', 'string', 'max:120'],
            'arrival_time' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment' => ['required', 'in:sur_place,virement,a_definir'],
            'terms' => ['accepted'],
        ], [], [
            'civility' => 'civilité',
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'phone' => 'téléphone',
            'country' => 'pays',
            'terms' => 'conditions',
        ]);

        $lines = BookingQuote::normaliseLines($booking);
        $group = BookingQuote::for($booking);

        // Création transactionnelle avec verrou : anti-double réservation (§98).
        // Une ligne `reservations` par chambre, reliées par `group_reference`.
        try {
            $reservations = DB::transaction(function () use ($booking, $data, $request, $lines, $group) {
                $hotel = Hotel::current();
                $cfg = config('booking');
                $nights = $group['nights'];
                $ratePlanKey = $booking['rate_plan'] ?? 'flexible';
                $multiplier = $cfg['rate_plans'][$ratePlanKey]['multiplier'] ?? 1;

                $categories = RoomCategory::query()
                    ->whereIn('slug', collect($lines)->pluck('slug'))
                    ->lockForUpdate()
                    ->get()->keyBy('slug');

                foreach ($lines as $line) {
                    $cat = $categories->get($line['slug']);
                    if (! $cat || ! Availability::canBook($cat, $booking['check_in'], $booking['check_out'], $line['qty'], lock: true)) {
                        throw new \RuntimeException('SOLD_OUT');
                    }
                }

                $guest = Guest::updateOrCreate(
                    ['email' => $data['email']],
                    [
                        'civility' => $data['civility'],
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'phone' => $data['phone'],
                        'country' => $data['country'],
                        'company' => $data['company'] ?? null,
                    ],
                );

                $ratePlan = RatePlan::query()
                    ->where('hotel_id', $hotel->id)->where('key', $ratePlanKey)->first();

                // Chambres à plat (une entrée par unité).
                $units = [];
                foreach ($lines as $line) {
                    $cat = $categories->get($line['slug']);
                    $nightly = (int) round($cat['price'] * $multiplier);
                    for ($k = 0; $k < $line['qty']; $k++) {
                        $units[] = ['cat' => $cat, 'room_total' => $nightly * $nights];
                    }
                }

                // Un seul traitement + une seule facture : la répartition
                // suppléments / remise / taxes / TVA vit dans ReservationBooking.
                $base = [
                    'hotel_id' => $hotel->id,
                    'guest_id' => $guest->id,
                    'rate_plan_id' => $ratePlan?->id,
                    'status' => 'pending',
                    'channel' => 'direct',
                    'check_in' => $booking['check_in'],
                    'check_out' => $booking['check_out'],
                    'adults' => $group['adults'],
                    'children' => $group['children'],
                    'currency' => 'XOF',
                    'special_requests' => $booking['special_requests'] ?? [],
                    'payment_method' => $data['payment'],
                    'notes' => $data['notes'] ?? null,
                    'arrival_time' => $data['arrival_time'] ?? null,
                    'source_ip' => $request->ip(),
                ];

                return ReservationBooking::persist($base, $units, $group, [
                    'promo' => $booking['promo'] ?? null,
                    'extras' => $booking['extras'] ?? [],
                ]);
            });
        } catch (\RuntimeException) {
            return redirect()->route('booking.rooms')->withErrors([
                'lines' => "Une des catégories vient d'afficher complet pour vos dates. Merci d'ajuster votre sélection.",
            ]);
        }

        $primary = $reservations->first();
        $groupRef = $primary->group_reference ?? $primary->reference;
        $groupTotal = (int) $reservations->sum('total');

        AuditLog::record('reservation.created', $primary, [
            'channel' => 'direct',
            'group_reference' => $groupRef,
            'rooms' => $reservations->count(),
            'total' => $groupTotal,
        ]);

        Notify::roles(
            ['reception', 'direction'],
            'Nouvelle réservation en ligne'.($reservations->count() > 1 ? ' ('.$reservations->count().' chambres)' : ''),
            $groupRef.' — '.$primary->guest->fullName()
                .' · '.$primary->check_in->format('d/m').' → '.$primary->check_out->format('d/m'),
            route('admin.reservations.show', $primary, false),
            level: 'success',
            icon: '📅',
        );

        // Notifications (file d'attente) — ne doivent jamais faire échouer la réservation.
        try {
            if (! empty($data['email'])) {
                Mail::to($data['email'])->queue(new ReservationConfirmed($primary));
            }
            Sms::queueTemplate($primary->guest->phone, 'reservation_confirmed', [
                'ref' => $groupRef,
                'in' => $primary->check_in->format('d/m/Y'),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        $request->session()->forget(self::KEY);
        $request->session()->flash('reservation_ref', $groupRef);

        return redirect()->route('booking.done');
    }

    /** Paiement en ligne de l'acompte (depuis la page de confirmation / e-mail). */
    public function payOnline(Request $request, string $reference)
    {
        // `$reference` peut être la référence de groupe (HRK-XXXXXX) ou d'une chambre (…-1).
        $group = Reservation::query()->with('guest')
            ->where('group_reference', $reference)->orderBy('id')->get();
        $reservation = $group->first() ?? Reservation::where('reference', $reference)->with('guest')->firstOrFail();
        $group = $group->isNotEmpty() ? $group : collect([$reservation]);

        abort_if(in_array($reservation->status, ['cancelled', 'no_show'], true), 422, 'Réservation clôturée.');

        $depositDue = (int) $group->sum('deposit');
        $alreadyPaid = (int) $group->sum(fn ($r) => $r->paidAmount());
        abort_if($depositDue > 0 && $alreadyPaid >= $depositDue, 422, 'Acompte déjà réglé.');

        // L'acompte du groupe est encaissé sur la 1re chambre (folio consolidé).
        $intent = PaymentGateway::open($reservation, 'reservation_deposit', max(1, $depositDue - $alreadyPaid), [
            'hotel_id' => $reservation->hotel_id,
            'payer_name' => $reservation->guest->fullName(),
            'payer_email' => $reservation->guest->email,
        ]);

        return redirect()->to(PaymentGateway::checkoutUrl($intent));
    }

    /** Étape 5 — page de confirmation. */
    public function done(Request $request)
    {
        $ref = $request->session()->get('reservation_ref');

        $reservations = $ref
            ? Reservation::query()->with(['guest', 'roomCategory', 'ratePlan', 'hotel'])
                ->where('group_reference', $ref)->orWhere('reference', $ref)
                ->orderBy('id')->get()
            : collect();

        if ($reservations->isEmpty()) {
            return redirect()->route('booking.start');
        }

        return view('pages.booking.done', [
            'reservation' => $reservations->first(),   // compat vue historique
            'reservations' => $reservations,
            'groupRef' => $ref,
            'groupTotal' => (int) $reservations->sum('total'),
            'groupDeposit' => (int) $reservations->sum('deposit'),
        ]);
    }

    /* ----------------------------------------------------------------- */

    /** Fusionne des valeurs dans l'état de session « booking ». */
    private function merge(Request $request, array $values): void
    {
        $request->session()->put(
            self::KEY,
            array_merge($request->session()->get(self::KEY, []), $values)
        );
    }

    /**
     * Vérifie que les étapes précédentes sont remplies, sinon redirige.
     *
     * @return array<string,mixed>|RedirectResponse
     */
    private function guard(Request $request, bool $needsSearch = false, bool $needsRoom = false)
    {
        $booking = $request->session()->get(self::KEY, []);

        if (($needsSearch || $needsRoom) && empty($booking['check_in'])) {
            return redirect()->route('booking.start')
                ->with('status', 'Merci de préciser vos dates de séjour.');
        }

        if ($needsRoom && ! BookingQuote::normaliseLines($booking)) {
            return redirect()->route('booking.rooms')
                ->with('status', 'Merci de sélectionner au moins une chambre.');
        }

        return $booking;
    }
}
