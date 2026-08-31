<?php

namespace App\Services\Payments;

use App\Models\PaymentIntent;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * PayDunya — API Checkout Invoice v1 (https://developers.paydunya.com).
 *
 * Flux : create-invoice → redirection vers `response_text` (invoice_url) →
 * IPN (form-urlencoded `data[...]`, authentifié par `data[hash]` = SHA-512 de
 * la clé privée) + repli `confirm/{token}`.
 *
 * NB : non testé contre le sandbox réel (compte marchand requis) ; les formes
 * de requête/réponse suivent la doc publique et sont couvertes par Http::fake().
 */
class PayDunyaDriver implements PaymentDriver
{
    public function key(): string
    {
        return 'paydunya';
    }

    public function start(PaymentIntent $intent): array
    {
        $res = $this->client()
            ->post('/checkout-invoice/create', [
                'invoice' => [
                    'total_amount' => $intent->amount,
                    'description' => $intent->purposeLabel().' — '.$intent->reference,
                ],
                'store' => ['name' => config('hotel.name', 'Hôtel Résidence Khadija')],
                'custom_data' => ['intent' => $intent->reference],
                'actions' => [
                    'return_url' => route('pay.return', ['provider' => $this->key(), 'intent' => $intent->reference]),
                    'cancel_url' => route('pay.return', ['provider' => $this->key(), 'intent' => $intent->reference]),
                    'callback_url' => route('pay.webhook', $this->key()),
                ],
            ])
            ->throw()
            ->json();

        if (($res['response_code'] ?? null) !== '00') {
            throw new RuntimeException('PayDunya: '.($res['response_text'] ?? 'création de facture refusée'));
        }

        return [
            'redirect_url' => $res['response_text'] ?? $res['invoice_url'],
            'provider_ref' => $res['token'] ?? null,
        ];
    }

    public function verify(PaymentIntent $intent): string
    {
        if (! $intent->provider_ref) {
            return 'pending';
        }

        $res = $this->client()->get('/checkout-invoice/confirm/'.$intent->provider_ref)->json();

        return $this->mapStatus($res['status'] ?? ($res['response_text'] ?? ''));
    }

    public function parseNativeWebhook(Request $request): ?array
    {
        $data = $request->input('data');
        if (! is_array($data) || ! isset($data['status'])) {
            return null;   // format non PayDunya → repli générique
        }

        $privateKey = (string) config('payments.providers.paydunya.private_key');
        $expected = hash('sha512', $privateKey);
        if ($privateKey === '' || ! hash_equals($expected, (string) ($data['hash'] ?? ''))) {
            throw new RuntimeException('PayDunya webhook: signature invalide.');
        }

        return [
            'reference' => $data['custom_data']['intent'] ?? null,
            'provider_ref' => $data['invoice']['token'] ?? ($data['token'] ?? null),
            'status' => $this->mapStatus($data['status']),
        ];
    }

    private function mapStatus(string $status): string
    {
        return match (mb_strtolower($status)) {
            'completed', 'paid', 'success' => 'paid',
            'cancelled', 'canceled', 'failed', 'declined' => 'failed',
            default => 'pending',
        };
    }

    private function client(): PendingRequest
    {
        $cfg = config('payments.providers.paydunya');

        return Http::baseUrl($cfg['base_url'] ?? 'https://app.paydunya.com/api/v1')
            ->acceptJson()
            ->timeout(20)
            ->withHeaders([
                'PAYDUNYA-MASTER-KEY' => (string) ($cfg['master_key'] ?? ''),
                'PAYDUNYA-PRIVATE-KEY' => (string) ($cfg['private_key'] ?? ''),
                'PAYDUNYA-TOKEN' => (string) ($cfg['token'] ?? ''),
            ]);
    }
}
