<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\PaymentIntent;
use App\Models\Reservation;
use App\Services\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentsSeeder extends Seeder
{
    public function run(): void
    {
        if (PaymentIntent::count() > 0) {
            return;
        }

        // Une réservation en attente : lien d'acompte généré, non payé.
        $pending = Reservation::where('status', 'pending')->where('deposit', '>', 0)->first();
        if ($pending) {
            PaymentGateway::open($pending, 'reservation_deposit', null, [
                'hotel_id' => $pending->hotel_id,
                'payer_name' => $pending->guest->fullName(),
                'payer_email' => $pending->guest->email,
            ]);
        }

        // Une réservation confirmée : acompte payé en ligne (carte).
        $confirmed = Reservation::where('status', 'confirmed')->where('deposit', '>', 0)
            ->whereDoesntHave('payments')->first();
        if ($confirmed) {
            $intent = PaymentGateway::open($confirmed, 'reservation_deposit', null, [
                'hotel_id' => $confirmed->hotel_id,
                'payer_name' => $confirmed->guest->fullName(),
                'payer_email' => $confirmed->guest->email,
            ]);
            PaymentGateway::capture($intent, 'carte', 'SIM-DEMO0001');
        }

        // Une commande restaurant non réglée : payée en ligne via Wave.
        $order = Order::where('payment_status', 'unpaid')->where('total', '>', 0)->first();
        if ($order) {
            $intent = PaymentGateway::open($order, 'order', (int) $order->total, ['hotel_id' => $order->hotel_id]);
            PaymentGateway::capture($intent, 'wave', 'SIM-DEMO0002');
        }
    }
}
