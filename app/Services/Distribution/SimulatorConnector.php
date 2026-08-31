<?php

namespace App\Services\Distribution;

use App\Models\Channel;
use App\Models\RatePlan;
use App\Models\RoomCategory;
use App\Services\Availability;
use App\Services\ChannelManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Connecteur de démonstration : les poussées ARI sont journalisées
 * (channel_sync_logs via ChannelManager) et tracées dans les logs applicatifs.
 * Aucune API externe, aucune réservation entrante.
 */
class SimulatorConnector implements ChannelConnector
{
    public function key(): string
    {
        return 'simulator';
    }

    public function pushAvailability(Channel $channel, Carbon $from, Carbon $to): int
    {
        $records = 0;
        foreach (RoomCategory::active()->get() as $category) {
            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                $open = Availability::remaining($category, $d->toDateString(), $d->copy()->addDay()->toDateString());
                $records++;
                Log::info('channel.push.availability', [
                    'channel' => $channel->key, 'category' => $category->slug,
                    'date' => $d->toDateString(), 'open' => $open,
                ]);
            }
        }

        return $records;
    }

    public function pushRates(Channel $channel, Carbon $from, Carbon $to): int
    {
        $records = 0;
        $plans = RatePlan::where('is_active', true)->get();
        foreach (RoomCategory::active()->get() as $category) {
            foreach ($plans as $plan) {
                for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                    $price = ChannelManager::priceFor($channel, $category, $plan, $d);
                    $records++;
                    Log::info('channel.push.rate', [
                        'channel' => $channel->key, 'category' => $category->slug,
                        'plan' => $plan->key, 'date' => $d->toDateString(), 'price' => $price,
                    ]);
                }
            }
        }

        return $records;
    }

    public function pullReservations(Channel $channel): array
    {
        return [];
    }

    public function testConnection(Channel $channel): array
    {
        return ['ok' => true, 'message' => 'Connecteur simulateur — aucune API à joindre.'];
    }
}
