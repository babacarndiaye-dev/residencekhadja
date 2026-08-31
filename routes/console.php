<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Maintenance préventive : génération quotidienne des interventions dues (§36).
Schedule::command('maintenance:run-plans')->dailyAt('06:00');

// Décisionnel : instantané des KPI de la veille + rapports planifiés (§27–28).
Schedule::command('bi:snapshot')->dailyAt('01:00');
Schedule::command('bi:run-schedules')->dailyAt('07:00');

// Distribution : poussée ARI vers les canaux connectés (§30).
Schedule::command('channels:push --rates')->hourly();
Schedule::command('channels:pull')->hourly()->withoutOverlapping();

// Relance de pré-arrivée (J-2) aux séjours confirmés avec e-mail.
Schedule::command('reservations:pre-arrival')->dailyAt('09:00');

// Enquête de satisfaction (J+1 après le départ) + relances + péremption des liens.
Schedule::command('satisfaction:invite')->dailyAt('10:00');

// Alertes RH : fin d'essai / de CDD, pièces & visites médicales qui expirent.
Schedule::command('hr:alerts')->weekdays()->dailyAt('08:00');

// Alertes caisse restaurant : stock bas, cuisine en retard, caisse ouverte trop longtemps…
Schedule::command('pos:alerts')->everyThirtyMinutes()->between('7:00', '23:00');

// Paiement : rattrape les intentions ouvertes dont le webhook prestataire n'est pas arrivé.
Schedule::command('payments:reconcile')->everyTenMinutes()->withoutOverlapping();

// File d'attente (e-mails, notifications) — sans worker permanent : on vide la file
// chaque minute. Pour un volume élevé, lancer plutôt `php artisan queue:work` en service.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()->withoutOverlapping();
