<?php

namespace App\Console\Commands;

use App\Services\Analytics;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class BiSnapshot extends Command
{
    protected $signature = 'bi:snapshot {--date= : Jour à figer (Y-m-d), défaut = hier}
                                        {--backfill= : Nombre de jours à recalculer en remontant}';

    protected $description = 'Calcule et stocke les KPI quotidiens (§27)';

    public function handle(): int
    {
        $end = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::yesterday();
        $days = max(1, (int) ($this->option('backfill') ?: 1));

        $written = 0;
        for ($i = 0; $i < $days; $i++) {
            $date = $end->copy()->subDays($i);
            $written += Analytics::snapshot($date);
            $this->line("· {$date->toDateString()} figé");
        }

        $this->info("{$written} métrique(s) écrite(s).");

        return self::SUCCESS;
    }
}
