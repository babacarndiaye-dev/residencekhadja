<?php

/*
|--------------------------------------------------------------------------
| SMS — passerelle d'envoi (transactionnel + marketing)
|--------------------------------------------------------------------------
| driver :
|   log    → journalise seulement (défaut, aucun envoi réel)
|   http   → POST form générique { from, to, message } + Bearer token, 2xx = OK
|   twilio → API Twilio (Basic auth SID:token, .../Messages.json)
|   orange → Orange SMS API (OAuth2 client_credentials puis /outbound/.../requests)
|
| Les SMS transactionnels invité (confirmation de réservation, pré-arrivée,
| suivi des demandes) sont mis en file via App\Jobs\SendSms et ne partent
| que si `transactional` est vrai ET que le client a un numéro.
*/

return [

    'driver' => env('SMS_DRIVER', 'log'),

    // Expéditeur affiché (alphanumérique 11 car. max, ou numéro selon la passerelle).
    'from' => env('SMS_FROM', 'KHADIJA'),

    // Envoi automatique des SMS invité transactionnels.
    'transactional' => env('SMS_TRANSACTIONAL', true),

    // Indicatif pays ajouté aux numéros locaux sans « + » (221 = Sénégal).
    'default_country' => (string) env('SMS_DEFAULT_COUNTRY', '221'),

    'http' => [
        'endpoint' => env('SMS_HTTP_ENDPOINT'),
        'token' => env('SMS_HTTP_TOKEN'),
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM'),   // numéro +1…/+221… ou identifiant alphanumérique
    ],

    'orange' => [
        'client_id' => env('ORANGE_SMS_CLIENT_ID'),
        'client_secret' => env('ORANGE_SMS_CLIENT_SECRET'),
        'sender' => env('ORANGE_SMS_SENDER'),        // numéro autorisé, ex « +221771234567 »
        'sender_name' => env('ORANGE_SMS_SENDER_NAME', 'Khadija'),
        'token_url' => env('ORANGE_SMS_TOKEN_URL', 'https://api.orange.com/oauth/v3/token'),
        'base_url' => env('ORANGE_SMS_BASE_URL', 'https://api.orange.com/smsmessaging/v1/outbound'),
    ],

    // Modèles des SMS transactionnels — :placeholders remplacés à l'envoi.
    'templates' => [
        'reservation_confirmed' => 'Residence Khadija : reservation :ref confirmee, arrivee le :in. Au plaisir de vous accueillir.',
        'pre_arrival' => 'Residence Khadija : votre arrivee est prevue le :in (check-in des 14h). Bon voyage !',
        'request_ack' => 'Residence Khadija : votre demande ":label" est prise en charge.',
        'request_done' => 'Residence Khadija : votre demande ":label" a ete traitee. Bon sejour !',
        'pos_invoice' => 'Residence Khadija : votre facture :ref est disponible : :url',
    ],
];
