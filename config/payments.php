<?php

/*
|--------------------------------------------------------------------------
| Paiement en ligne — §25 (acompte réservation, addition QR, événements)
|--------------------------------------------------------------------------
| Le driver « simulator » fournit une page de paiement hébergée locale
| (aucun débit réel). Les drivers réels vivent dans App\Services\Payments\
| (PayDunyaDriver, CinetPayDriver) : renseigner les clés ci-dessous dans le
| .env, passer PAYMENT_DRIVER sur « paydunya » ou « cinetpay », déclarer chez
| le prestataire l'URL de notification /paiement/webhook/{provider} et l'URL
| de retour /paiement/retour/{provider}/{ref} (générées automatiquement).
*/

return [

    'driver' => env('PAYMENT_DRIVER', 'simulator'),

    'currency' => 'XOF',

    // En-tête portant la signature HMAC-SHA256 des webhooks prestataires.
    // La vérification n'est imposée que si le provider a un « webhook_secret ».
    'webhook_signature_header' => env('PAYMENT_WEBHOOK_SIGNATURE_HEADER', 'X-Signature'),

    // Durée de validité d'une intention de paiement.
    'intent_ttl_minutes' => 60,

    // Part d'acompte demandée en ligne pour une réservation directe.
    'reservation_deposit_rate' => 0.30,

    'methods' => [
        'carte' => 'Carte bancaire',
        'orange_money' => 'Orange Money',
        'wave' => 'Wave',
        'free_money' => 'Free Money',
    ],

    'providers' => [
        'simulator' => [
            'label' => 'Simulateur (sandbox)',
            'enabled' => true,
            'methods' => ['carte', 'orange_money', 'wave', 'free_money'],
        ],
        'paydunya' => [
            'label' => 'PayDunya',
            'enabled' => false,
            'methods' => ['carte', 'orange_money', 'wave', 'free_money'],
            'base_url' => env('PAYDUNYA_BASE_URL', 'https://app.paydunya.com/api/v1'),
            'master_key' => env('PAYDUNYA_MASTER_KEY'),
            'private_key' => env('PAYDUNYA_PRIVATE_KEY'),
            'token' => env('PAYDUNYA_TOKEN'),
            'webhook_secret' => env('PAYDUNYA_WEBHOOK_SECRET'),
        ],
        'cinetpay' => [
            'label' => 'CinetPay',
            'enabled' => false,
            'methods' => ['carte', 'orange_money', 'wave'],
            'site_id' => env('CINETPAY_SITE_ID'),
            'api_key' => env('CINETPAY_API_KEY'),
            'webhook_secret' => env('CINETPAY_WEBHOOK_SECRET'),
        ],
    ],

    // Saisie « carte » du simulateur qui force un refus (tests).
    'simulator_decline_trigger' => '4000000000000002',
];
