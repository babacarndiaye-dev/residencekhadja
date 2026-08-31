<?php

namespace App\Console\Commands;

use App\Mail\PreArrivalReminder;
use App\Models\Reservation;
use App\Services\Sms;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendPreArrivalReminders extends Command
{
    protected $signature = 'reservations:pre-arrival {--days=2 : Nombre de jours avant l\'arrivée} {--date=}';

    protected $description = 'Envoie l\'e-mail de pré-arrivée aux séjours confirmés qui arrivent bientôt';

    public function handle(): int
    {
        $target = ($this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today())
            ->addDays((int) $this->option('days'))
            ->toDateString();

        $reservations = Reservation::with('guest', 'roomCategory')
            ->whereIn('status', ['confirmed', 'pending'])
            ->whereDate('check_in', $target)
            ->whereNull('pre_arrival_sent_at')
            ->get()
            ->filter(fn (Reservation $r) => filled($r->guest?->email) || filled($r->guest?->phone));

        foreach ($reservations as $reservation) {
            if (filled($reservation->guest->email)) {
                Mail::to($reservation->guest->email)->queue(new PreArrivalReminder($reservation));
            }

            Sms::queueTemplate($reservation->guest->phone, 'pre_arrival', [
                'in' => $reservation->check_in->format('d/m/Y'),
            ]);

            $reservation->forceFill(['pre_arrival_sent_at' => now()])->saveQuietly();
        }

        $this->info("{$reservations->count()} rappel(s) de pré-arrivée mis en file (arrivées du {$target}).");

        return self::SUCCESS;
    }
}
