<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Services\Satisfaction;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendSatisfactionInvitations extends Command
{
    protected $signature = 'satisfaction:invite
        {--days=1 : Nombre de jours après le départ}
        {--date= : Forcer la date de référence (test)}';

    protected $description = 'Envoie l’enquête de satisfaction aux séjours terminés + relance les invitations sans réponse';

    public function handle(): int
    {
        $refDate = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
        $target = $refDate->copy()->subDays((int) $this->option('days'))->toDateString();

        $stays = Reservation::with('guest')
            ->where('status', 'checked_out')
            ->whereDate('check_out', $target)
            ->whereDoesntHave('satisfactionSurvey')
            ->get();

        $invited = 0;
        foreach ($stays as $stay) {
            if (Satisfaction::inviteForStay($stay)) {
                $invited++;
            }
        }

        $reminded = Satisfaction::remindStale($refDate->copy()->endOfDay());
        $expired = Satisfaction::expireStale($refDate);

        $this->info("{$invited} invitation(s) · {$reminded} relance(s) · {$expired} lien(s) expiré(s) (départs du {$target}).");

        return self::SUCCESS;
    }
}
