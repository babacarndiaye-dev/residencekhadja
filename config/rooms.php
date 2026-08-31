<?php

/*
|--------------------------------------------------------------------------
| Chambres & Suites
|--------------------------------------------------------------------------
| price = tarif indicatif "à partir de", par nuit, en FCFA (XOF).
| images = photos de l'hôtel dans public/img/hotel/ (servies via asset()).
| NB : la vitrine lit la table `room_categories`. Après modification ici,
| resynchroniser les images en base :
|   php artisan tinker --execute="foreach(config('rooms.items') as \$i){ \
|     App\Models\RoomCategory::where('slug',\$i['slug'])->update(['images'=>\$i['images']]); }"
*/

return [

    'categories' => [
        'chambre' => 'Chambres',
        'suite' => 'Suites',
    ],

    'items' => [

        [
            'slug' => 'chambre-superieure',
            'name' => 'Chambre Supérieure',
            'category' => 'chambre',
            'price' => 45000,
            'size' => 24,
            'capacity' => 2,
            'bed' => 'Lit Queen ou lits jumeaux',
            'view' => 'Vue ville',
            'featured' => false,
            'short' => 'Un cocon contemporain aux teintes de sable et de terre cuite, pensé pour '
                           ."un séjour d'affaires efficace comme pour une escale reposante.",
            'description' => "La Chambre Supérieure ouvre le séjour Khadija sur une note d'élégance sobre : "
                           .'matières naturelles, literie premium, bureau de travail et connexion très haut débit. '
                           ."La salle de bain en pierre reconstituée propose douche à l'italienne et produits d'accueil signature.",
            'amenities' => ['Wi-Fi fibre gratuit', 'Climatisation individuelle', 'Bureau de travail', 'Smart TV', 'Coffre-fort', 'Minibar', 'Service en chambre'],
            'images' => [
                '/img/hotel/chambre-superieure.jpg',
                '/img/hotel/salle-de-bain.jpg',
            ],
        ],

        [
            'slug' => 'chambre-deluxe',
            'name' => 'Chambre Deluxe',
            'category' => 'chambre',
            'price' => 58000,
            'size' => 30,
            'capacity' => 3,
            'bed' => 'Lit King',
            'view' => 'Vue jardin ou piscine',
            'featured' => true,
            'short' => "Plus d'espace, un salon de lecture et une vue apaisante sur le jardin ou la piscine.",
            'description' => "La Chambre Deluxe agrandit l'expérience : coin salon, fauteuil de lecture, dressing "
                           ."et grande fenêtre baignée de lumière. Les motifs géométriques discrets et l'artisanat local "
                           .'signent une atmosphère résolument sénégalaise et contemporaine.',
            'amenities' => ['Wi-Fi fibre gratuit', 'Climatisation individuelle', 'Coin salon', 'Machine à café', 'Smart TV', 'Coffre-fort', 'Peignoirs & chaussons', 'Service en chambre'],
            'images' => [
                '/img/hotel/chambre-deluxe.jpg',
                '/img/hotel/salle-de-bain.jpg',
            ],
        ],

        [
            'slug' => 'suite-junior',
            'name' => 'Suite Junior',
            'category' => 'suite',
            'price' => 82000,
            'size' => 42,
            'capacity' => 3,
            'bed' => 'Lit King + canapé-lit',
            'view' => 'Vue ville ou jardin',
            'featured' => true,
            'short' => "Une chambre et un véritable salon séparé — l'équilibre idéal pour les longs séjours.",
            'description' => 'La Suite Junior sépare nuit et vie de jour : salon indépendant, table à manger pour '
                           .'quatre, coin bureau et salle de bain double vasque. Parfaite pour recevoir, travailler '
                           .'ou prolonger le séjour en famille.',
            'amenities' => ['Wi-Fi fibre gratuit', 'Salon séparé', 'Climatisation individuelle', 'Machine à café', 'Deux Smart TV', 'Coffre-fort', 'Peignoirs & chaussons', 'Service en chambre 24h/24'],
            'images' => [
                '/img/hotel/suite-junior.jpg',
                '/img/hotel/chambre-deluxe.jpg',
            ],
        ],

        [
            'slug' => 'suite-executive',
            'name' => 'Suite Exécutive',
            'category' => 'suite',
            'price' => 110000,
            'size' => 55,
            'capacity' => 4,
            'bed' => 'Lit King + canapé-lit',
            'view' => 'Vue panoramique',
            'featured' => false,
            'short' => 'Vue panoramique, accès rooftop privilégié et attentions Khadija Privilege.',
            'description' => "La Suite Exécutive s'adresse aux voyageurs d'affaires exigeants : grand bureau, "
                           .'espace réunion informel, accès prioritaire au rooftop et petit-déjeuner servi en suite. '
                           ."Le design mêle laiton, bois et textures inspirées de l'océan.",
            'amenities' => ['Wi-Fi fibre gratuit', 'Espace réunion', 'Accès rooftop privilégié', 'Machine à café', 'Deux Smart TV', 'Coffre-fort', 'Check-in privatif', 'Service en chambre 24h/24'],
            'images' => [
                '/img/hotel/suite-executive.jpg',
                '/img/hotel/suite-spa-bain.jpg',
            ],
        ],

        [
            'slug' => 'suite-teranga',
            'name' => 'Suite Teranga',
            'category' => 'suite',
            'price' => 165000,
            'size' => 75,
            'capacity' => 4,
            'bed' => 'Lit King + chambre d\'appoint',
            'view' => 'Vue panoramique & terrasse privée',
            'featured' => true,
            'short' => "La signature de la maison : terrasse privée, salon d'apparat et service dédié.",
            'description' => "La Suite Teranga est l'hommage le plus abouti à l'hospitalité sénégalaise : "
                           ."salon d'apparat, salle à manger, terrasse privée avec vue, dressing et salle de bain "
                           .'spa avec baignoire îlot. Un majordome coordonne chaque instant du séjour.',
            'amenities' => ['Wi-Fi fibre gratuit', 'Terrasse privée', 'Service de majordome', 'Transfert aéroport inclus', 'Petit-déjeuner inclus', 'Machine à café', 'Salle de bain spa', 'Check-in privatif'],
            'images' => [
                '/img/hotel/suite-teranga.jpg',
                '/img/hotel/suite-spa-bain.jpg',
            ],
        ],
    ],
];
