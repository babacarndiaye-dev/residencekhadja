<?php

namespace App\Services\Payments;

use App\Models\PaymentIntent;
use Illuminate\Http\Request;

/**
 * Driver « simulateur » : page de paiement hébergée localement
 * (PaymentCheckoutController). Aucun appel réseau, aucun débit.
 */
class SimulatorDriver implements PaymentDriver
{
    public function key(): string
    {
        return 'simulator';
    }

    public function start(PaymentIntent $intent): array
    {
        return [
            'redirect_url' => route('pay.checkout', $intent->reference),
            'provider_ref' => null,
        ];
    }

    public function verify(PaymentIntent $intent): string
    {
        return match ($intent->status) {
            'paid' => 'paid',
            'failed', 'expired', 'cancelled' => 'failed',
            default => 'pending',
        };
    }

    /** Le simulateur n'émet pas de webhook natif : on laisse le repli générique jouer. */
    public function parseNativeWebhook(Request $request): ?array
    {
        return null;
    }
}
