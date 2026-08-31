<?php

/*
|--------------------------------------------------------------------------
| Piscine — réservation de transats & cabanas
|--------------------------------------------------------------------------
*/

return [

    'open_from' => '08:00',
    'open_to' => '19:00',

    // Créneaux réservables.
    'slots' => [
        'morning' => ['label' => 'Matinée', 'from' => '08:00', 'to' => '13:00'],
        'afternoon' => ['label' => 'Après-midi', 'from' => '13:00', 'to' => '19:00'],
        'full_day' => ['label' => 'Journée', 'from' => '08:00', 'to' => '19:00'],
    ],

    'kinds' => [
        'transat' => 'Transat',
        'daybed' => 'Bain de soleil double',
        'cabana' => 'Cabana',
    ],

    'statuses' => [
        'booked' => 'Réservé',
        'checked_in' => 'Installé',
        'done' => 'Terminé',
        'no_show' => 'Non présenté',
        'cancelled' => 'Annulé',
    ],

    // Parc créé au seed.
    'seed_assets' => [
        ['kind' => 'transat', 'prefix' => 'Transat', 'count' => 14, 'capacity' => 1, 'half_day_price' => 3000, 'full_day_price' => 5000],
        ['kind' => 'daybed', 'prefix' => 'Bain double', 'count' => 4, 'capacity' => 2, 'half_day_price' => 6000, 'full_day_price' => 10000],
        ['kind' => 'cabana', 'prefix' => 'Cabana', 'count' => 3, 'capacity' => 4, 'half_day_price' => 20000, 'full_day_price' => 35000],
    ],
];
