<?php

/*
|--------------------------------------------------------------------------
| Réglages SEO par défaut
|--------------------------------------------------------------------------
| Surcharge possible page par page via la vue (section 'meta').
*/

return [

    'default_title' => 'Hôtel Résidence Khadija — Hôtel premium à Thiès, Sénégal',
    'title_suffix' => ' · Hôtel Résidence Khadija',
    'default_description' => 'Hôtel premium à Thiès : chambres et suites élégantes, restaurant « Saveurs du '
                           .'Sénégal », rooftop, piscine, salles de séminaire et Teranga. Réservez au meilleur tarif direct.',
    // Chemin local (public/) — la vue le convertit en URL absolue pour Open Graph.
    'default_og_image' => '/img/hotel/facade.jpg',
    'locale' => 'fr_SN',
    'twitter' => '@residencekhadija',

    // Renseigner pour activer le suivi (chargé de façon différée).
    'google_analytics_id' => null,

    'keywords_local' => [
        'Hôtel Résidence Khadija', 'hôtel à Thiès', 'hébergement Thiès', 'restaurant Thiès',
        'rooftop Thiès', 'séminaire Thiès', 'salle de séminaire Sénégal', 'voyage d\'affaires Sénégal',
        'hôtel premium Sénégal', 'tourisme Thiès',
    ],
];
