<?php

/*
|--------------------------------------------------------------------------
| Galerie photos
|--------------------------------------------------------------------------
| Regroupée par thème pour permettre un filtre côté client.
*/

return [

    // Restauration & Thiès : filtres réintroduits dès que des photos maison sont fournies.
    'filters' => ['Tous', 'Chambres', 'Espaces', 'Séminaires'],

    'photos' => [
        ['group' => 'Chambres',     'alt' => 'Suite Exécutive, chambre et coin salon',        'src' => '/img/hotel/suite-executive.jpg'],
        ['group' => 'Chambres',     'alt' => 'Suite Junior avec salon et coin repas',         'src' => '/img/hotel/suite-junior.jpg'],
        ['group' => 'Chambres',     'alt' => 'Chambre Deluxe, tête de lit capitonnée',        'src' => '/img/hotel/chambre-deluxe.jpg'],
        ['group' => 'Chambres',     'alt' => 'Chambre Supérieure lumineuse',                  'src' => '/img/hotel/chambre-superieure.jpg'],
        ['group' => 'Chambres',     'alt' => 'Salle de bain, double vasque et douche à l\'italienne', 'src' => '/img/hotel/salle-de-bain.jpg'],
        ['group' => 'Chambres',     'alt' => 'Salle de bain spa avec baignoire balnéo',       'src' => '/img/hotel/suite-spa-bain.jpg'],
        ['group' => 'Espaces',      'alt' => 'Piscine extérieure et transats',                'src' => '/img/hotel/piscine.jpg'],
        ['group' => 'Espaces',      'alt' => 'Terrasse de la piscine à l\'ombre du parasol',  'src' => '/img/hotel/piscine-2.jpg'],
        ['group' => 'Espaces',      'alt' => 'Bassin bordé de palmiers',                      'src' => '/img/hotel/piscine-3.jpg'],
        ['group' => 'Espaces',      'alt' => 'Lobby et salon d\'accueil aux fauteuils orange', 'src' => '/img/hotel/lobby.jpg'],
        ['group' => 'Espaces',      'alt' => 'Façade de l\'Hôtel Résidence Khadija à Thiès',   'src' => '/img/hotel/facade.jpg'],
        ['group' => 'Espaces',      'alt' => 'Entrée principale de l\'hôtel',                  'src' => '/img/hotel/entree.jpg'],
        ['group' => 'Séminaires',   'alt' => 'Salle de séminaire en configuration U',         'src' => '/img/hotel/salle-seminaire.jpg'],
        ['group' => 'Séminaires',   'alt' => 'Salle de réunion avec écran interactif',        'src' => '/img/hotel/salle-reunion.jpg'],
    ],
];
