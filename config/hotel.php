<?php

/*
|--------------------------------------------------------------------------
| Identité de l'établissement — Hôtel Résidence Khadija
|--------------------------------------------------------------------------
| Toutes les données "vitrine" sont centralisées ici et dans les autres
| fichiers config/*.php (rooms, dining, spaces, offers, experiences,
| gallery, reviews, booking). Aucun texte n'est codé en dur dans les vues :
| il suffit de modifier ces fichiers pour faire évoluer le site.
*/

return [

    'name' => 'Hôtel Résidence Khadija',
    'short_name' => 'Résidence Khadija',
    'monogram' => 'HRK',
    'tagline' => "L'art sénégalais de recevoir, réinventé par la technologie.",
    'baseline' => 'Élégance • Confort • Affaires • Hospitalité',
    'intro' => "Au cœur de Thiès, l'Hôtel Résidence Khadija conjugue la Teranga sénégalaise, "
                   ."le confort d'un établissement premium et les services d'un hôtel pensé pour les affaires "
                   .'comme pour les séjours en famille.',

    'city' => 'Thiès',
    'country' => 'Sénégal',

    'contact' => [
        'address_line' => 'Avenue Léopold Sédar Senghor',
        'address_area' => 'Thiès Nord, Thiès',
        'address_country' => 'Sénégal',
        'phone' => '+221 33 000 00 00',
        'phone_href' => '+221330000000',
        'whatsapp' => '+221 77 000 00 00',
        'whatsapp_href' => '221770000000',
        'email' => 'contact@residence-khadija.sn',
        'reservations_email' => 'reservation@residence-khadija.sn',
    ],

    // Coordonnées approximatives du centre de Thiès (à ajuster avec la position réelle).
    'geo' => [
        'lat' => 14.7910,
        'lng' => -16.9256,
        'map_embed' => 'https://www.google.com/maps?q=Thi%C3%A8s%20S%C3%A9n%C3%A9gal&output=embed',
        'directions_url' => 'https://www.google.com/maps/dir/?api=1&destination=Thi%C3%A8s+S%C3%A9n%C3%A9gal',
    ],

    'hours' => [
        'reception' => 'Réception ouverte 24h/24',
        'restaurant' => 'Restaurant : 6h30 – 23h00',
        'rooftop' => 'Rooftop : 17h00 – 01h00',
    ],

    'social' => [
        'facebook' => 'https://facebook.com/',
        'instagram' => 'https://instagram.com/',
        'linkedin' => 'https://linkedin.com/',
    ],

    // Chiffres-clés affichés sur la page d'accueil.
    'stats' => [
        ['value' => '68',   'label' => 'Chambres & suites'],
        ['value' => '4',    'label' => 'Espaces de restauration'],
        ['value' => '250',  'label' => 'Places en séminaire'],
        ['value' => '24/7', 'label' => 'Conciergerie'],
    ],

    // Programme de fidélité (vitrine).
    'loyalty' => [
        'name' => 'Khadija Privilege',
        'levels' => ['Classic', 'Silver', 'Gold', 'Platinum', 'VIP'],
        'pitch' => "Chaque séjour, chaque dîner et chaque événement vous rapproche d'attentions "
                  .'réservées : surclassement, late check-out, expériences Teranga et tarifs membres.',
    ],

    'currency' => [
        'code' => 'XOF',
        'symbol' => 'FCFA',
    ],
];
