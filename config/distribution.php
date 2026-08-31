<?php

/*
|--------------------------------------------------------------------------
| Distribution & Channel Manager (§29–31)
|--------------------------------------------------------------------------
| Connecteurs (colonne `connector` du canal, résolus par ChannelManager::connector) :
|  - simulator : aucune API, poussées ARI journalisées (channel_sync_logs) ;
|  - ical      : App\Services\Distribution\IcalConnector — importe les blocages
|                d'une URL iCal (credentials { ical_url, room_slug }). `channels:pull`.
| Les OTA à API certifiée (Booking.com Connectivity, Expedia, Airbnb) restent
| sur « simulator » tant que l'accord partenaire n'est pas en place.
*/

return [

    // Horizon de poussée par défaut (jours à partir d'aujourd'hui).
    'push_horizon_days' => 120,

    'default_commission_rate' => 0.15,

    // Vérification de signature des webhooks de canal (réservations entrantes).
    // Secret global de repli ; un secret par canal peut être posé dans
    // channels.credentials['webhook_secret']. Sans secret → non imposé.
    'webhook_secret' => env('CHANNEL_WEBHOOK_SECRET'),
    'webhook_signature_header' => env('CHANNEL_WEBHOOK_SIGNATURE_HEADER', 'X-Signature'),

    'channel_types' => [
        'direct' => 'Direct',
        'ota' => 'Agence en ligne (OTA)',
        'gds' => 'GDS',
        'wholesaler' => 'Grossiste / tour-opérateur',
        'metasearch' => 'Métamoteur',
    ],

    'connectors' => [
        'direct' => 'Direct (site)',
        'simulator' => 'Simulateur',
        'ical' => 'Calendrier iCal (import)',
    ],

    // Restrictions de calendrier gérables par date / catégorie.
    'restrictions' => [
        'stop_sell' => 'Vente stoppée',
        'cta' => 'Fermé à l’arrivée',
        'ctd' => 'Fermé au départ',
        'min_stay' => 'Séjour minimum',
        'max_stay' => 'Séjour maximum',
    ],

    // Canaux créés au seed (connecteur « simulator » sauf le direct).
    'seed_channels' => [
        ['key' => 'direct', 'name' => 'Réservation directe', 'type' => 'direct', 'connector' => 'direct', 'commission_rate' => 0.0],
        ['key' => 'booking_com', 'name' => 'Booking.com', 'type' => 'ota', 'connector' => 'simulator', 'commission_rate' => 0.15],
        ['key' => 'expedia', 'name' => 'Expedia', 'type' => 'ota', 'connector' => 'simulator', 'commission_rate' => 0.18],
        ['key' => 'airbnb', 'name' => 'Airbnb', 'type' => 'ota', 'connector' => 'simulator', 'commission_rate' => 0.14],
        ['key' => 'hotelbeds', 'name' => 'Hotelbeds', 'type' => 'wholesaler', 'connector' => 'simulator', 'commission_rate' => 0.20],
    ],
];
