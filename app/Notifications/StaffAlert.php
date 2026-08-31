<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Alerte in-app générique pour le personnel (canal « database » uniquement).
 * Créée via App\Support\Notify::role() / ::users().
 */
class StaffAlert extends Notification
{
    use Queueable;

    /**
     * @param  string  $title  Titre court
     * @param  string  $body  Ligne de détail
     * @param  string|null  $url  Lien back-office (route relative)
     * @param  string  $level  info | success | warning | critical
     * @param  string  $icon  Émoji d'entête
     */
    public function __construct(
        public string $title,
        public string $body = '',
        public ?string $url = null,
        public string $level = 'info',
        public string $icon = '🔔',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'level' => $this->level,
            'icon' => $this->icon,
        ];
    }
}
