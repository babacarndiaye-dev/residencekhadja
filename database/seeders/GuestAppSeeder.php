<?php

namespace Database\Seeders;

use App\Models\GuestRequest;
use App\Models\Reservation;
use Illuminate\Database\Seeder;

class GuestAppSeeder extends Seeder
{
    public function run(): void
    {
        if (GuestRequest::count() > 0) {
            return;
        }

        $stay = Reservation::where('status', 'checked_in')->whereNotNull('room_id')->first();
        if (! $stay) {
            return;
        }

        foreach ([
            ['type' => 'articles', 'note' => '2 oreillers supplémentaires, merci.', 'routed_to' => 'housekeeping', 'status' => 'done'],
            ['type' => 'depannage', 'note' => 'La climatisation fait du bruit.', 'routed_to' => 'maintenance', 'status' => 'acknowledged'],
            ['type' => 'transport', 'note' => 'Taxi pour l’aéroport demain 6h.', 'routed_to' => 'reception', 'status' => 'open'],
        ] as $r) {
            $stay->guestRequests()->create($r + [
                'hotel_id' => $stay->hotel_id,
                'room_id' => $stay->room_id,
                'acknowledged_at' => $r['status'] !== 'open' ? now()->subHours(2) : null,
                'resolved_at' => $r['status'] === 'done' ? now()->subHour() : null,
            ]);
        }
    }
}
