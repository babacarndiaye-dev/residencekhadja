<?php

namespace App\Jobs;

use App\Services\Sms;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Envoi d'un SMS hors du cycle requête (passerelle potentiellement lente).
 * Traité par le worker de file (`php artisan queue:work`).
 */
class SendSms implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public string $to,
        public string $message,
        public ?string $from = null,
    ) {}

    public function handle(): void
    {
        Sms::send($this->to, $this->message, $this->from);
    }
}
