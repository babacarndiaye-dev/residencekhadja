<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Services\ChannelManager;
use Illuminate\Console\Command;

class PullChannelReservations extends Command
{
    protected $signature = 'channels:pull {--channel= : Clé d’un canal précis}';

    protected $description = 'Importe les réservations / blocages entrants des canaux (iCal…)';

    public function handle(): int
    {
        $only = $this->option('channel')
            ? Channel::where('key', $this->option('channel'))->first()
            : null;

        if ($this->option('channel') && ! $only) {
            $this->error('Canal introuvable.');

            return self::FAILURE;
        }

        $handled = ChannelManager::pullReservations($only);
        $this->info("{$handled} canal/canaux traité(s).");

        return self::SUCCESS;
    }
}
