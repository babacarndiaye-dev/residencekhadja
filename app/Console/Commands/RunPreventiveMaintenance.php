<?php

namespace App\Console\Commands;

use App\Services\PreventiveMaintenance;
use Illuminate\Console\Command;

class RunPreventiveMaintenance extends Command
{
    protected $signature = 'maintenance:run-plans';

    protected $description = 'Génère les tickets de maintenance préventive arrivés à échéance (§36)';

    public function handle(): int
    {
        $count = PreventiveMaintenance::run();

        $this->info("{$count} intervention(s) préventive(s) générée(s).");

        return self::SUCCESS;
    }
}
