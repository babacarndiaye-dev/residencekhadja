<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FinanceAccount;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\Venue;
use App\Services\FinanceLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(Order::STATUSES))],
            'venue' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:40'],
        ]);

        $orders = Order::query()
            ->with(['venue', 'location', 'items'])
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['venue'] ?? null, fn ($q, $v) => $q->where('venue_id', $v))
            ->when($filters['q'] ?? null, fn ($q, $t) => $q->where('reference', 'like', "%{$t}%")->orWhere('guest_name', 'like', "%{$t}%"))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => $filters,
            'statuses' => Order::STATUSES,
            'venues' => Venue::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items', 'venue', 'location', 'room', 'reservation.guest', 'serviceRequests']);

        return view('admin.orders.show', ['order' => $order]);
    }

    /** Encaissement direct (§29). */
    public function pay(Request $request, Order $order)
    {
        abort_if($order->isPaid(), 422, 'Commande déjà réglée.');

        $data = $request->validate([
            'method' => ['required', Rule::in(['especes', 'carte', 'mobile'])],
        ]);

        DB::transaction(function () use ($order, $data) {
            $order->update([
                'payment_status' => 'paid',
                'payment_method' => $data['method'],
            ]);

            if (Schema::hasTable('finance_accounts')
                && FinanceAccount::query()->exists()) {
                FinanceLedger::record([
                    'direction' => 'income',
                    'category' => 'restaurant',
                    'method' => $data['method'],
                    'amount' => $order->total,
                    'label' => "Encaissement commande {$order->reference}",
                    'source' => $order,
                ]);
            }
        });

        AuditLog::record('order.paid', $order, ['method' => $data['method'], 'total' => $order->total]);

        return back()->with('status', "Commande {$order->reference} encaissée ({$data['method']}).");
    }

    /** Imputer la commande sur une réservation (folio). */
    public function chargeToRoom(Request $request, Order $order)
    {
        abort_if($order->isPaid(), 422);

        $data = $request->validate([
            'reservation_id' => [
                'required',
                Rule::exists('reservations', 'id')->where('status', 'checked_in'),
            ],
        ]);

        DB::transaction(function () use ($order, $data, $request) {
            $reservation = Reservation::findOrFail($data['reservation_id']);

            $reservation->charges()->create([
                'source_type' => $order->getMorphClass(),
                'source_id' => $order->id,
                'label' => "Commande {$order->reference} — ".($order->venue->name ?? 'Restaurant'),
                'amount' => $order->total,
                'created_by' => $request->user()->id,
            ]);

            $order->update([
                'reservation_id' => $reservation->id,
                'room_id' => $reservation->room_id,
                'payment_status' => 'charged_to_room',
                'payment_method' => 'chambre',
            ]);
        });

        AuditLog::record('order.charged_to_room', $order, ['reservation_id' => $data['reservation_id']]);

        return back()->with('status', "Commande {$order->reference} imputée sur la chambre.");
    }

    public function cancel(Order $order)
    {
        abort_if(in_array($order->status, ['completed', 'cancelled'], true), 422);

        $order->update(['status' => 'cancelled']);

        // Retirer l'éventuelle imputation folio.
        $order->reservation?->charges()
            ->where('source_type', $order->getMorphClass())
            ->where('source_id', $order->id)
            ->delete();

        AuditLog::record('order.cancelled', $order);

        return back()->with('status', "Commande {$order->reference} annulée.");
    }
}
