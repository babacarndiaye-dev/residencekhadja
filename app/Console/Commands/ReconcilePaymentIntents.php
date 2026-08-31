<?php

namespace App\Console\Commands;

use App\Models\PaymentIntent;
use App\Services\PaymentGateway;
use Illuminate\Console\Command;
use Throwable;

class ReconcilePaymentIntents extends Command
{
    protected $signature = 'payments:reconcile {--minutes=15 : Âge minimum des intentions à vérifier}';

    protected $description = 'Vérifie auprès du prestataire les intentions de paiement encore ouvertes (webhook manquant)';

    public function handle(): int
    {
        if (config('payments.driver', 'simulator') === 'simulator') {
            $this->info('Driver « simulator » : rien à réconcilier.');

            return self::SUCCESS;
        }

        $intents = PaymentIntent::query()
            ->whereIn('status', ['pending', 'processing'])
            ->where('provider', '!=', 'simulator')
            ->where('created_at', '<=', now()->subMinutes((int) $this->option('minutes')))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->limit(200)
            ->get();

        $paid = 0;
        $failed = 0;
        foreach ($intents as $intent) {
            try {
                $fresh = PaymentGateway::reconcile($intent);
                $fresh->status === 'paid' && $paid++;
                $fresh->status === 'failed' && $failed++;
            } catch (Throwable $e) {
                $this->warn("{$intent->reference} : {$e->getMessage()}");
            }
        }

        $this->info("{$intents->count()} intention(s) vérifiée(s) · {$paid} payée(s) · {$failed} échouée(s).");

        return self::SUCCESS;
    }
}
