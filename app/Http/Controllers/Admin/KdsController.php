<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Venue;
use App\Support\Notify;
use Illuminate\Http\Request;

class KdsController extends Controller
{
    /** Kitchen Display System (§30). */
    public function index(Request $request)
    {
        $venueId = $request->integer('venue') ?: null;

        $orders = Order::query()
            ->with(['items', 'location', 'venue'])
            ->whereIn('status', ['new', 'preparing', 'ready'])
            ->when($venueId, fn ($q) => $q->where('venue_id', $venueId))
            // Au moins un article à préparer en cuisine (on ignore les commandes 100 % boissons).
            ->whereHas('items', fn ($q) => $q->where('needs_kitchen', true))
            ->orderBy('placed_at')
            ->get()
            ->each(fn (Order $o) => $o->setRelation('items', $o->items->where('needs_kitchen', true)->values()))
            ->groupBy('status');

        return view('admin.kds.index', [
            'orders' => $orders,
            'venues' => Venue::where('is_active', true)->orderBy('sort_order')->get(),
            'activeVenue' => $venueId,
            'served' => Order::with('venue')->where('status', 'served')
                ->whereHas('items', fn ($q) => $q->where('needs_kitchen', true))
                ->latest('served_at')->take(12)->get(),
        ]);
    }

    public function advance(Request $request, Order $order)
    {
        $next = $order->nextStatus();
        abort_unless($next, 422);

        $order->update([
            'status' => $next,
            'ready_at' => $next === 'ready' ? now() : $order->ready_at,
            'served_at' => $next === 'served' ? now() : $order->served_at,
            'out_for_delivery_at' => $next === 'out_for_delivery' ? now() : $order->out_for_delivery_at,
            'delivered_at' => $next === 'delivered' ? now() : $order->delivered_at,
            'completed_at' => $next === 'completed' ? now() : $order->completed_at,
        ]);

        AuditLog::record('order.status', $order, ['status' => $next]);

        // Room service prêt → prévenir la réception pour la livraison en chambre.
        if ($next === 'ready' && $order->type === 'room_service') {
            Notify::roles(
                ['reception', 'restaurant'],
                'Room service prêt',
                'Chambre '.($order->room->number ?? $order->table_label ?? '—').' — à livrer',
                route('admin.pos.room_service', absolute: false),
                level: 'info',
                icon: '🛎️',
            );
        }

        return back()->with('status', "Commande {$order->reference} : {$order->statusLabel()}.");
    }
}
