<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\QrLocation;
use App\Models\Reservation;
use App\Models\ServiceRequest;
use App\Services\CartPricer;
use App\Services\PaymentGateway;
use App\Services\RecipeConsumption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QrOrderController extends Controller
{
    public function store(Request $request, string $code, CartPricer $pricer)
    {
        $location = QrLocation::query()->with('venue', 'room')->where('code', $code)->firstOrFail();

        abort_unless($location->is_active && $location->venue?->accepts_qr_orders, 422, 'Commande indisponible sur ce point de vente.');

        $data = $request->validate([
            'idempotency_key' => ['required', 'string', 'size:36'],
            'guest_name' => ['nullable', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:300'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'items.*.options' => ['nullable', 'array'],
            'items.*.options.*' => ['integer'],
            'items.*.note' => ['nullable', 'string', 'max:180'],
            'charge_to_room' => ['nullable', 'boolean'],
            'room_number' => ['nullable', 'string', 'max:20'],
            'room_last_name' => ['nullable', 'string', 'max:80'],
        ]);

        // §99 — double soumission : renvoyer la commande déjà créée.
        if ($existing = Order::where('idempotency_key', $data['idempotency_key'])->first()) {
            return redirect()->route('qr.track', [$code, $existing->reference]);
        }

        $token = $request->cookie('qr_session') ?: bin2hex(random_bytes(20));
        $quote = $pricer->price($location, $data['items']);

        // Imputation chambre (§29).
        $reservation = $this->resolveReservation($location, $data);
        $chargeToRoom = ($data['charge_to_room'] ?? false) && $reservation;

        // Boissons / bar uniquement → pas d'étape cuisine, la commande est prête au bar.
        $needsKitchen = collect($quote['lines'])->contains(fn ($l) => $l['needs_kitchen'] ?? true);

        $order = DB::transaction(function () use ($location, $data, $quote, $token, $reservation, $chargeToRoom, $needsKitchen) {
            $order = Order::create([
                'reference' => $this->reference(),
                'hotel_id' => $location->hotel_id,
                'qr_location_id' => $location->id,
                'venue_id' => $location->venue_id,
                'room_id' => $location->room_id ?? $reservation?->room_id,
                'reservation_id' => $chargeToRoom ? $reservation->id : null,
                'type' => $location->isRoomService() ? 'room_service' : 'dine_in',
                'status' => $needsKitchen ? 'new' : 'ready',
                'payment_status' => $chargeToRoom ? 'charged_to_room' : 'unpaid',
                'payment_method' => $chargeToRoom ? 'chambre' : null,
                'guest_name' => $data['guest_name'] ?? $reservation?->guest?->fullName(),
                'session_token' => $token,
                'idempotency_key' => $data['idempotency_key'],
                'subtotal' => $quote['subtotal'],
                'service_charge' => $quote['service_charge'],
                'tax' => $quote['tax'],
                'total' => $quote['total'],
                'note' => $data['note'] ?? null,
                'placed_at' => now(),
            ]);

            foreach ($quote['lines'] as $line) {
                $order->items()->create($line);
            }

            RecipeConsumption::applyForOrder($order);

            if ($chargeToRoom) {
                $reservation->charges()->create([
                    'source_type' => $order->getMorphClass(),
                    'source_id' => $order->id,
                    'label' => "Commande {$order->reference} — ".($order->venue->name ?? 'Restaurant'),
                    'amount' => $order->total,
                ]);
            }

            return $order;
        });

        return redirect()
            ->route('qr.track', [$code, $order->reference])
            ->cookie('qr_session', $token, 60 * 24 * 7)
            ->with('status', 'Votre commande a été transmise en cuisine.');
    }

    public function track(Request $request, string $code, string $reference)
    {
        $location = QrLocation::where('code', $code)->firstOrFail();
        $order = Order::with('items', 'venue')
            ->where('reference', $reference)
            ->where('qr_location_id', $location->id)
            ->firstOrFail();

        // Ne montrer qu'au navigateur qui a passé la commande.
        abort_unless($request->cookie('qr_session') === $order->session_token, 403);

        return view('pages.qr.track', compact('location', 'order'));
    }

    /** Paiement en ligne de la commande (§25). */
    public function payOnline(Request $request, string $code, string $reference)
    {
        $location = QrLocation::where('code', $code)->firstOrFail();
        $order = Order::where('reference', $reference)
            ->where('qr_location_id', $location->id)
            ->firstOrFail();

        abort_unless($request->cookie('qr_session') === $order->session_token, 403);
        abort_if($order->isPaid(), 422, 'Commande déjà réglée.');

        $intent = PaymentGateway::open($order, 'order', (int) $order->total, [
            'hotel_id' => $order->hotel_id,
            'meta' => ['qr_code' => $code, 'order' => $reference],
        ]);

        return redirect()->to(PaymentGateway::checkoutUrl($intent));
    }

    public function bill(Request $request, string $code)
    {
        $location = QrLocation::where('code', $code)->firstOrFail();

        ServiceRequest::create([
            'hotel_id' => $location->hotel_id,
            'qr_location_id' => $location->id,
            'type' => 'bill',
            'status' => 'open',
        ]);

        return back()->with('status', "L'addition a été demandée. Un serveur arrive.");
    }

    /* ----------------------------------------------------------------- */

    private function resolveReservation(QrLocation $location, array $data): ?Reservation
    {
        if ($location->room_id) {
            return Reservation::with('guest')
                ->where('room_id', $location->room_id)
                ->where('status', 'checked_in')
                ->first();
        }

        if (! empty($data['room_number']) && ! empty($data['room_last_name'])) {
            return Reservation::with('guest')
                ->where('status', 'checked_in')
                ->whereHas('room', fn ($q) => $q->where('number', $data['room_number']))
                ->whereHas('guest', fn ($q) => $q->where('last_name', 'like', $data['room_last_name']))
                ->first();
        }

        return null;
    }

    private function reference(): string
    {
        do {
            $ref = 'C-'.strtoupper(Str::random(5));
        } while (Order::where('reference', $ref)->exists());

        return $ref;
    }
}
