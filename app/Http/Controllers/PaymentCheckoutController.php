<?php

namespace App\Http\Controllers;

use App\Models\PaymentIntent;
use App\Services\PaymentGateway;
use App\Support\WebhookSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class PaymentCheckoutController extends Controller
{
    /** Page de paiement hébergée (driver simulateur). */
    public function show(PaymentIntent $intent)
    {
        if (in_array($intent->status, ['paid', 'refunded'], true)) {
            return redirect()->route('pay.receipt', $intent->reference);
        }

        // Prestataire réel : rediriger vers sa page de paiement.
        if ($intent->provider !== 'simulator' && ! empty($intent->meta['checkout_url'])) {
            return redirect()->away($intent->meta['checkout_url']);
        }

        return view('pages.pay.checkout', [
            'intent' => $intent,
            'expired' => ! $intent->isOpen(),
            'methods' => array_intersect_key(
                config('payments.methods'),
                array_flip(config('payments.providers.'.config('payments.driver').'.methods', ['carte'])),
            ),
        ]);
    }

    /** Traitement simulateur : capture ou échec. */
    public function process(Request $request, PaymentIntent $intent)
    {
        abort_unless(config('payments.driver') === 'simulator', 404);

        $data = $request->validate([
            'method' => ['required', Rule::in(array_keys(config('payments.methods')))],
            'card' => ['nullable', 'string', 'max:32'],
            'action' => ['nullable', 'in:pay,fail'],
        ]);

        if (! $intent->isOpen()) {
            return redirect()->route('pay.receipt', $intent->reference);
        }

        $declined = ($data['action'] ?? null) === 'fail'
            || preg_replace('/\s+/', '', (string) ($data['card'] ?? '')) === config('payments.simulator_decline_trigger');

        if ($declined) {
            PaymentGateway::fail($intent, 'Paiement refusé par la banque (simulateur).');

            return back()->withErrors(['card' => 'Paiement refusé. Réessayez avec un autre moyen.']);
        }

        PaymentGateway::capture($intent, $data['method'], 'SIM-'.strtoupper(bin2hex(random_bytes(5))));

        return redirect()->route('pay.receipt', $intent->reference);
    }

    /**
     * Retour du client depuis la page d'un prestataire réel (return_url).
     * On réconcilie l'intention avec l'état effectif avant d'afficher le reçu.
     */
    public function return(Request $request, string $provider, PaymentIntent $intent)
    {
        abort_unless(config("payments.providers.{$provider}"), 404);

        try {
            PaymentGateway::reconcile($intent->fresh());
        } catch (Throwable $e) {
            Log::warning('payment.return.reconcile_failed', [
                'provider' => $provider, 'intent' => $intent->reference, 'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('pay.receipt', $intent->reference);
    }

    public function receipt(PaymentIntent $intent)
    {
        return view('pages.pay.receipt', ['intent' => $intent->load('payable')]);
    }

    /**
     * Webhook prestataire (idempotent). D'abord l'analyse native du driver
     * (PayDunya `data[hash]`, CinetPay `payment/check`) ; sinon repli sur une
     * charge JSON signée HMAC (simulateur / prestataire configurable).
     */
    public function webhook(Request $request, string $provider)
    {
        $config = config("payments.providers.{$provider}");
        abort_unless($config, 404);

        try {
            $parsed = PaymentGateway::driver($provider)->parseNativeWebhook($request);
        } catch (Throwable $e) {
            Log::warning('payment.webhook.bad_signature', [
                'provider' => $provider, 'ip' => $request->ip(), 'error' => $e->getMessage(),
            ]);

            return response()->json(['ok' => false, 'reason' => 'bad_signature'], 401);
        }

        if ($parsed === null) {
            $secret = $config['webhook_secret'] ?? null;
            if (! WebhookSignature::valid($request, $secret, config('payments.webhook_signature_header', 'X-Signature'))) {
                Log::warning('payment.webhook.bad_signature', ['provider' => $provider, 'ip' => $request->ip()]);

                return response()->json(['ok' => false, 'reason' => 'bad_signature'], 401);
            }
            if (! WebhookSignature::enforced($secret)) {
                Log::warning('payment.webhook.unverified', ['provider' => $provider]);
            }

            $parsed = [
                'reference' => $request->input('reference') ?? $request->input('invoice.token'),
                'provider_ref' => $request->input('provider_ref') ?? $request->input('token'),
                'status' => $request->input('status', 'paid'),
            ];
        }

        $intent = PaymentIntent::where('reference', $parsed['reference'])
            ->when($parsed['provider_ref'], fn ($q) => $q->orWhere('provider_ref', $parsed['provider_ref']))
            ->first();

        if (! $intent) {
            return response()->json(['ok' => false, 'reason' => 'unknown_intent'], 202);
        }

        match ($parsed['status']) {
            'paid', 'completed', 'success' => PaymentGateway::capture($intent, $intent->method ?? 'carte', $parsed['provider_ref']),
            'failed', 'cancelled' => PaymentGateway::fail($intent, 'Notifié échoué par '.$provider),
            default => null,
        };

        return response()->json(['ok' => true]);
    }
}
