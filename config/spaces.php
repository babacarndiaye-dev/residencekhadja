<?php

/*
|--------------------------------------------------------------------------
| Espaces, bien-être & séminaires
|--------------------------------------------------------------------------
*/

return [

    // Espaces loisirs & bien-être.
    'leisure' => [
        [
            'slug' => 'piscine',
            'name' => 'Piscine extérieure',
            'desc' => 'Bassin chauffé bordé de transats et de cabanas, ouvert du lever du jour au coucher '
                     ."du soleil, avec service au bord de l'eau.",
            'info' => '07h00 – 19h00 · Accès résidents',
            'image' => '/img/hotel/piscine.jpg',
        ],
        [
            'slug' => 'rooftop',
            'name' => 'Rooftop panoramique',
            'desc' => 'Terrasse au dernier étage : lounge, bar Baobab et vue à 180° sur Thiès. '
                     .'Le rendez-vous du sundowner et des soirées privées.',
            'info' => '17h00 – 01h00',
            'image' => 'https://images.unsplash.com/photo-1533106497176-45ae19e68ba2?auto=format&fit=crop&w=1200&q=70',
        ],
        [
            'slug' => 'fitness',
            'name' => 'Salle de fitness',
            'desc' => 'Plateau cardio et musculation équipé Technogym, coaching sur demande, '
                     .'espace étirement et fontaine à eau.',
            'info' => 'Accès 24h/24 · Résidents',
            'image' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=1200&q=70',
        ],
        [
            'slug' => 'spa',
            'name' => 'Spa & soins',
            'desc' => 'Cabines de massage, hammam et rituels au karité et au baobab inspirés des '
                     .'traditions sénégalaises de soin.',
            'info' => 'Sur réservation · 10h00 – 20h00',
            'image' => '/img/hotel/suite-spa-bain.jpg',
        ],
        [
            'slug' => 'business-corner',
            'name' => 'Business Corner',
            'desc' => 'Espace de coworking ouvert 24h/24 : postes de travail, imprimante, cabines '
                     ."d'appel et café en accès libre.",
            'info' => 'Accès 24h/24 · Résidents',
            'image' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=70',
        ],
    ],

    // Salles de séminaire & configurations.
    'meeting_rooms' => [
        [
            'name' => 'Salle Ndar',
            'area' => 180,
            'capacities' => ['Théâtre' => 200, 'Classe' => 110, 'U' => 60, 'Banquet' => 150],
            'features' => ['Lumière du jour', 'Vidéoprojection 4K', 'Sonorisation', 'Cabine de traduction'],
            'image' => '/img/hotel/salle-seminaire.jpg',
        ],
        [
            'name' => 'Salle Sine',
            'area' => 90,
            'capacities' => ['Théâtre' => 90, 'Classe' => 50, 'U' => 32, 'Banquet' => 70],
            'features' => ['Lumière du jour', 'Écran LED', 'Sonorisation', 'Climatisation renforcée'],
            'image' => '/img/hotel/salle-reunion.jpg',
        ],
        [
            'name' => 'Salon Saloum',
            'area' => 45,
            'capacities' => ['Réunion' => 18, 'U' => 16, 'Comité' => 12],
            'features' => ['Table de conseil', 'Écran tactile', 'Visioconférence', 'Office traiteur'],
            'image' => '/img/hotel/salle-reunion.jpg',
        ],
    ],

    'events_services' => [
        'Séminaires résidentiels et journées d\'étude',
        'Conférences et lancements de produit',
        'Dîners de gala et soirées d\'entreprise',
        'Cérémonies familiales et mariages',
        'Pauses-café et déjeuners d\'affaires sur mesure',
        'Régie technique, hôtesses et transport des participants',
    ],
];
