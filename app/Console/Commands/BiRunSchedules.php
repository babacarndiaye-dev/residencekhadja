<?php

namespace App\Console\Commands;

use App\Mail\ScheduledReport;
use App\Models\ReportRun;
use App\Models\ReportSchedule;
use App\Services\ReportRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BiRunSchedules extends Command
{
    protected $signature = 'bi:run-schedules {--date=}';

    protected $description = 'Exécute les rapports planifiés arrivés à échéance (§28)';

    public function handle(): int
    {
        $on = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
        $ran = 0;

        foreach (ReportSchedule::where('is_active', true)->get() as $schedule) {
            if (! $schedule->isDue($on)) {
                continue;
            }

            $from = $on->copy()->subDays(max(1, $schedule->range_days))->startOfDay();
            $to = $on->copy()->subDay()->startOfDay();
            $report = ReportRegistry::run($schedule->report_key, $from, $to);

            ReportRun::create([
                'report_schedule_id' => $schedule->id,
                'report_key' => $schedule->report_key,
                'period_start' => $from->toDateString(),
                'period_end' => $to->toDateString(),
                'row_count' => count($report['rows']),
                'payload' => ['columns' => $report['columns'], 'sample' => array_slice($report['rows'], 0, 5)],
            ]);

            $schedule->update(['last_run_at' => now()]);
            $ran++;

            $recipients = array_filter((array) $schedule->recipients);
            if ($recipients) {
                $filename = Str::slug($schedule->report_key).'_'.$from->toDateString().'_'.$to->toDateString().'.csv';
                Mail::to($recipients)->queue(new ScheduledReport(
                    label: $schedule->reportLabel(),
                    periodStart: $from->toDateString(),
                    periodEnd: $to->toDateString(),
                    rowCount: count($report['rows']),
                    csv: ReportRegistry::toCsv($report),
                    filename: $filename,
                ));
            }

            $this->line("· {$schedule->reportLabel()} — ".count($report['rows']).' ligne(s)'
                .($recipients ? ' → '.implode(', ', $recipients) : ''));
        }

        $this->info("{$ran} rapport(s) planifié(s) exécuté(s).");

        return self::SUCCESS;
    }
}
