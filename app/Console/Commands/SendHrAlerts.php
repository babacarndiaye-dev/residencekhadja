<?php

namespace App\Console\Commands;

use App\Services\HrAlerts;
use App\Support\Notify;
use Illuminate\Console\Command;

class SendHrAlerts extends Command
{
    protected $signature = 'hr:alerts';

    protected $description = 'Pousse les alertes RH urgentes (fin d’essai / CDD, pièces expirées) à la RH et à la direction';

    public function handle(): int
    {
        $alerts = HrAlerts::collect();
        $urgent = $alerts->where('level', 'critical');

        foreach ($urgent as $a) {
            Notify::roles(['rh', 'direction'], 'Alerte RH', $a['label'], $a['url'], level: 'warning', icon: '📌');
        }

        $this->info("{$alerts->count()} alerte(s) RH · {$urgent->count()} urgente(s) notifiée(s).");

        return self::SUCCESS;
    }
}
