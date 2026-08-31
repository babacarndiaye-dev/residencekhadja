<?php

namespace App\Console\Commands;

use App\Services\ChannelManager;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PushChannels extends Command
{
    protected $signature = 'channels:push {--days= : Horizon en jours} {--rates : Pousser aussi les tarifs}';

    protected $description = 'Pousse la disponibilité (et les tarifs) vers les canaux connectés (§30)';

    public function handle(): int
    {
        $from = Carbon::today();
        $to = Carbon::today()->addDays((int) ($this->option('days') ?: config('distribution.push_horizon_days', 120)));

        $avail = ChannelManager::pushAvailability($from, $to);
        $this->info("Disponibilité poussée vers {$avail} canal/canaux.");

        if ($this->option('rates')) {
            $rates = ChannelManager::pushRates($from, $to);
            $this->info("Tarifs poussés vers {$rates} canal/canaux.");
        }

        return self::SUCCESS;
    }
}
