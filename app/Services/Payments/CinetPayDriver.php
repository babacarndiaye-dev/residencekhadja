<?php

namespace App\Services\Payments;

use App\Models\PaymentIntent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * CinetPay — API Checkout v2 (https://docs.cinetpay.com).
 *
 * Flux : POST /payment (transaction_id = notre référence) → redirection vers
 * `data.payment_url` → notification (form) confirmée par POST /payment/check
 * (source de vérité). En-tête `x-token` (HMAC) vérifié si un secret est posé.
 *
 * NB : non testé contre l'API réelle (site_id / api_key requis) ; formes de
 * requête/réponse d'après la doc publique, couvertes par Http::fake().
 */
class CinetPayDriver implements PaymentDriver
{
    private const BASE = 'https://api-checkout.cinetpay.com/v2';

    public function key(): string
    {
        return 'cinetpay';
    }

    public function start(PaymentIntent $intent): array
    {
        $cfg = config('payments.providers.cinetpay');

        $res = Http::acceptJson()->timeout(20)
            ->post(self::BASE.'/payment', [
                'apikey' => (string) ($cfg['api_key'] ?? ''),
                'site_id' => (string) ($cfg['site_id'] ?? ''),
                'transaction_id' => $intent->reference,
                'amount' => $intent->amount,
                'currency' => $intent->currency ?: 'XOF',
                'description' => $intent->purposeLabel().' — '.$intent->reference,
                'channels' => 'ALL',
                'customer_name' => $intent->payer_name ?: 'Client',
                'customer_email' => $intent->payer_email ?: '',
                'notify_url' => route('pay.webhook', $this->key()),
                'return_url' => route('pay.return', ['provider' => $this->key(), 'intent' => $intent->reference]),
            ])
            ->throw()
            ->json();

        if (! in_array((string) ($res['code'] ?? ''), ['201', '00'], true)) {
            throw new RuntimeException('CinetPay: '.($res['message'] ?? 'initialisation refusée'));
        }

        return [
            'redirect_url' => $res['data']['payment_url'],
            'provider_ref' => $res['data']['payment_token'] ?? null,
        ];
    }

    public function verify(PaymentIntent $intent): string
    {
        $cfg = config('payments.providers.cinetpay');

        $res = Http::acceptJson()->timeout(20)
            ->post(self::BASE.'/payment/check', [
                'apikey' => (string) ($cfg['api_key'] ?? ''),
                'site_id' => (string) ($cfg['site_id'] ?? ''),
                'transaction_id' => $intent->reference,
            ])
            ->json();

        return $this->mapStatus(
            $res['data']['status'] ?? '',
            (string) ($res['code'] ?? ''),
        );
    }

    public function parseNativeWebhook(Request $request): ?array
    {
        $transId = $request->input('cpm_trans_id');
        if (! $transId) {
            return null;   // pas une notification CinetPay → repli générique
        }

        $secret = config('payments.providers.cinetpay.webhook_secret');
        $token = $request->header('x-token');
        if ($secret && $token) {
            $payload = $request->input('cpm_site_id', '')
                .$request->input('cpm_trans_id', '')
                .$request->input('cpm_trans_date', '')
                .$request->input('cpm_amount', '')
                .$request->input('cpm_currency', '')
                .$request->input('signature', '')
                .$request->input('payment_method', '')
                .$request->input('cel_phone_num', '')
                .$request->input('cpm_phone_prefixe', '')
                .$request->input('cpm_language', '')
                .$request->input('cpm_version', '')
                .$request->input('cpm_payment_config', '')
                .$request->input('cpm_page_action', '')
                .$request->input('cpm_custom', '')
                .$request->input('cpm_designation', '')
                .$request->input('cpm_error_message', '');
            if (! hash_equals(hash_hmac('sha256', $payload, $secret), $token)) {
                throw new RuntimeException('CinetPay webhook: en-tête x-token invalide.');
            }
        }

        // La notification ne fait pas foi : on vérifie serveur-à-serveur.
        $intent = PaymentIntent::where('reference', $transId)->first();
        $status = $intent ? $this->verify($intent) : 'pending';

        return [
            'reference' => $transId,
            'provider_ref' => $request->input('cpm_payid'),
            'status' => $status,
        ];
    }

    private function mapStatus(string $status, string $code = ''): string
    {
        if ($code === '00') {
            return 'paid';
        }

        return match (mb_strtoupper($status)) {
            'ACCEPTED', 'SUCCESS', 'COMPLETED' => 'paid',
            'REFUSED', 'CANCELED', 'CANCELLED', 'FAILED' => 'failed',
            default => 'pending',
        };
    }
}
