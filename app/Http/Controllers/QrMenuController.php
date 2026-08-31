<?php

namespace App\Http\Controllers;

use App\Models\MenuCategory;
use App\Models\Order;
use App\Models\QrLocation;
use App\Models\Reservation;
use Illuminate\Http\Request;

class QrMenuController extends Controller
{
    /** Carte digitale consultable + commande (§22–24, §31–32). */
    public function show(Request $request, string $code)
    {
        $location = QrLocation::query()->with(['venue', 'room'])->where('code', $code)->first();

        // §99 — QR invalide / désactivé / table inactive.
        if (! $location) {
            return response()->view('pages.qr.invalid', ['reason' => 'inconnu'], 404);
        }
        if (! $location->is_active || ($location->venue && ! $location->venue->is_active)) {
            return response()->view('pages.qr.invalid', ['reason' => 'inactif'], 410);
        }

        $venue = $location->venue;
        $orderable = $venue?->accepts_qr_orders ?? false;

        $categories = MenuCategory::query()
            ->active()->ordered()
            ->when($location->isRoomService(),
                fn ($q) => $q->where('room_service', true),
                fn ($q) => $q->whereHas('venues', fn ($v) => $v->where('venues.id', $venue?->id)))
            ->with(['items' => fn ($q) => $q->available()->with('optionGroups.options')])
            ->get()
            ->filter(fn ($c) => $c->items->isNotEmpty())
            ->values();

        // Réservation active pour l'imputation chambre.
        $reservation = null;
        if ($location->room_id) {
            $reservation = Reservation::query()
                ->where('room_id', $location->room_id)
                ->where('status', 'checked_in')
                ->first();
        }

        // Commandes en cours suivies par ce navigateur.
        $token = $request->cookie('qr_session');
        $myOrders = $token
            ? Order::query()->where('session_token', $token)
                ->where('qr_location_id', $location->id)
                ->whereIn('status', [...Order::OPEN, 'served'])
                ->latest()->get()
            : collect();

        return response()
            ->view('pages.qr.menu', compact('location', 'venue', 'orderable', 'categories', 'reservation', 'myOrders'))
            ->cookie('qr_session', $token ?: bin2hex(random_bytes(20)), 60 * 24 * 7);
    }

    /** Menu public générique (marketing / consultation, sans localisation). */
    public function publicIndex(Request $request)
    {
        $categories = MenuCategory::query()
            ->active()->ordered()
            ->with(['items' => fn ($q) => $q->available()->with('optionGroups.options'), 'venues'])
            ->get()
            ->filter(fn ($c) => $c->items->isNotEmpty())
            ->values();

        return view('pages.qr.public', compact('categories'));
    }
}
