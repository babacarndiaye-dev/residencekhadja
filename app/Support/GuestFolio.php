<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Reservation;

/**
 * Folio consolidé d'un séjour, ventilé par origine de consommation — pour la
 * recherche client hôtel de la caisse.
 */
class GuestFolio
{
    /** @return array<string,mixed> */
    public static function for(Reservation $reservation): array
    {
        $members = $reservation->groupSiblings()->load('charges');
        $charges = $members->flatMap(fn ($m) => $m->charges);

        $orderMorph = app(Order::class)->getMorphClass();
        $saleTypes = Order::whereIn(
            'id',
            $charges->where('source_type', $orderMorph)->pluck('source_id')->unique()->all()
        )->pluck('sale_type', 'id');

        $buckets = ['restaurant' => 0, 'bar' => 0, 'room_service' => 0, 'autres' => 0];
        foreach ($charges as $c) {
            $type = $c->source_type === $orderMorph ? ($saleTypes[$c->source_id] ?? null) : null;
            $key = isset($buckets[$type]) ? $type : 'autres';
            $buckets[$key] += (int) $c->amount;
        }

        $stay = (int) $members->sum('total');
        $consumptions = (int) $charges->sum('amount');
        $paid = (int) $members->sum(fn ($m) => $m->paidAmount());

        return [
            'reference' => $reservation->groupKey(),
            'restaurant' => $buckets['restaurant'],
            'bar' => $buckets['bar'],
            'room_service' => $buckets['room_service'],
            'autres' => $buckets['autres'],
            'consumptions' => $consumptions,
            'stay' => $stay,
            'total' => $stay + $consumptions,
            'paid' => $paid,
            'balance' => $stay + $consumptions - $paid,
        ];
    }
}
