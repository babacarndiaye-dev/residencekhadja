<?php

/*
|--------------------------------------------------------------------------
| Carte digitale — données de départ (seed)
|--------------------------------------------------------------------------
| Prix en FCFA. 'room_service' = catégorie proposée aussi au room service.
| 'venues' = slugs de config('dining.venues') qui servent cette catégorie.
| Chaque item : allergens[], tags[], options[] (groupes → choix).
*/

return [

    'categories' => [
        [
            'slug' => 'entrees',
            'name' => 'Entrées & pastels',
            'description' => 'À partager ou pour commencer en douceur.',
            'room_service' => true,
            'venues' => ['restaurant-teranga', 'rooftop-baobab', 'lobby-lounge'],
            'items' => [
                [
                    'slug' => 'pastels-thon', 'name' => 'Pastels au thon (x4)', 'price' => 3500,
                    'description' => 'Chaussons croustillants, thon rôf, sauce kaani.',
                    'image' => 'https://images.unsplash.com/photo-1541592106381-b31e9677c0e5?auto=format&fit=crop&w=800&q=70',
                    'allergens' => ['gluten', 'poisson'], 'tags' => ['à partager'],
                ],
                [
                    'slug' => 'accras-crevettes', 'name' => 'Accras de crevettes (x6)', 'price' => 4500,
                    'description' => 'Beignets moelleux, mayonnaise au bissap.',
                    'image' => 'https://images.unsplash.com/photo-1626200419199-391ae4be7f92?auto=format&fit=crop&w=800&q=70',
                    'allergens' => ['gluten', 'crustacés', 'œuf'], 'tags' => ['à partager'],
                ],
                [
                    'slug' => 'salade-teranga', 'name' => 'Salade Teranga', 'price' => 4000,
                    'description' => 'Jeunes pousses, mangue verte, avocat, arachide caramélisée.',
                    'image' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=800&q=70',
                    'allergens' => ['arachide'], 'tags' => ['végétarien', 'sans gluten'],
                    'options' => [
                        ['name' => 'Protéine', 'type' => 'single', 'required' => false, 'choices' => [
                            ['name' => 'Nature', 'price_delta' => 0],
                            ['name' => 'Poulet grillé', 'price_delta' => 2000],
                            ['name' => 'Crevettes', 'price_delta' => 3000],
                        ]],
                    ],
                ],
            ],
        ],

        [
            'slug' => 'saveurs-du-senegal',
            'name' => 'Saveurs du Sénégal',
            'description' => 'Les classiques du terroir, revisités par le chef.',
            'room_service' => true,
            'venues' => ['restaurant-teranga', 'rooftop-baobab'],
            'items' => [
                [
                    'slug' => 'thieboudienne', 'name' => 'Thiéboudienne rouge', 'price' => 7500,
                    'description' => 'Riz au poisson, légumes du marché, poisson farci à la rôf.',
                    'story' => 'Préparé en marmite de fonte comme à Saint-Louis, ville d’origine du chef.',
                    'image' => 'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?auto=format&fit=crop&w=800&q=70',
                    'allergens' => ['poisson'], 'tags' => ['signature', 'épicé'], 'signature' => true,
                ],
                [
                    'slug' => 'yassa-poulet', 'name' => 'Yassa poulet', 'price' => 6500,
                    'description' => 'Poulet fermier mariné au citron, oignons confits, riz parfumé.',
                    'story' => 'Recette de Casamance, équilibre acidulé et fondant.',
                    'image' => 'https://images.unsplash.com/photo-1432139555190-58524dae6a55?auto=format&fit=crop&w=800&q=70',
                    'allergens' => [], 'tags' => ['signature'], 'signature' => true,
                    'options' => [
                        ['name' => 'Accompagnement', 'type' => 'single', 'required' => true, 'choices' => [
                            ['name' => 'Riz blanc', 'price_delta' => 0],
                            ['name' => 'Riz brisé', 'price_delta' => 0],
                            ['name' => 'Attiéké', 'price_delta' => 500],
                        ]],
                        ['name' => 'Piment', 'type' => 'single', 'required' => false, 'choices' => [
                            ['name' => 'Doux', 'price_delta' => 0],
                            ['name' => 'Relevé', 'price_delta' => 0],
                        ]],
                    ],
                ],
                [
                    'slug' => 'mafe-boeuf', 'name' => 'Mafé de bœuf', 'price' => 6800,
                    'description' => 'Ragoût à la pâte d’arachide, viande fondante, légumes racines.',
                    'story' => 'La recette de la grand-mère de la cheffe pâtissière.',
                    'image' => 'https://images.unsplash.com/photo-1455619452474-d2be8b1e70cd?auto=format&fit=crop&w=800&q=70',
                    'allergens' => ['arachide'], 'tags' => [],
                ],
            ],
        ],

        [
            'slug' => 'grillades',
            'name' => 'Grillades & poissons',
            'description' => 'Du feu de bois, servi avec deux accompagnements.',
            'room_service' => true,
            'venues' => ['restaurant-teranga', 'rooftop-baobab', 'pool-bar'],
            'items' => [
                [
                    'slug' => 'dorade-braisee', 'name' => 'Dorade braisée entière', 'price' => 9000,
                    'description' => 'Marinade yaboy, citron, oignons, servie avec alloco.',
                    'image' => 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?auto=format&fit=crop&w=800&q=70',
                    'allergens' => ['poisson'], 'tags' => [],
                ],
                [
                    'slug' => 'brochettes-boeuf', 'name' => 'Brochettes de bœuf (x3)', 'price' => 7000,
                    'description' => 'Bœuf mariné, poivrons, sauce moutarde-miel.',
                    'image' => 'https://images.unsplash.com/photo-1529193591184-b1d58069ecdd?auto=format&fit=crop&w=800&q=70',
                    'allergens' => ['moutarde'], 'tags' => [],
                    'options' => [
                        ['name' => 'Cuisson', 'type' => 'single', 'required' => true, 'choices' => [
                            ['name' => 'À point', 'price_delta' => 0],
                            ['name' => 'Bien cuit', 'price_delta' => 0],
                            ['name' => 'Saignant', 'price_delta' => 0],
                        ]],
                        ['name' => 'Suppléments', 'type' => 'multi', 'required' => false, 'choices' => [
                            ['name' => 'Frites maison', 'price_delta' => 1500],
                            ['name' => 'Riz', 'price_delta' => 1000],
                            ['name' => 'Salade', 'price_delta' => 1500],
                        ]],
                    ],
                ],
                [
                    'slug' => 'burger-khadija', 'name' => 'Burger Khadija', 'price' => 6500,
                    'description' => 'Bœuf, cheddar, oignons caramélisés au bissap, frites.',
                    'image' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=800&q=70',
                    'allergens' => ['gluten', 'lait', 'œuf'], 'tags' => [],
                ],
            ],
        ],

        [
            'slug' => 'desserts',
            'name' => 'Desserts',
            'description' => 'La touche sucrée maison.',
            'room_service' => true,
            'venues' => ['restaurant-teranga', 'rooftop-baobab', 'lobby-lounge'],
            'items' => [
                [
                    'slug' => 'thiakry', 'name' => 'Thiakry du chef', 'price' => 3000,
                    'description' => 'Couscous de mil, lait caillé, mangue rôtie, arachide caramélisée.',
                    'story' => 'La signature sucrée d’un repas Teranga.',
                    'image' => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=800&q=70',
                    'allergens' => ['lait', 'arachide'], 'tags' => ['signature'], 'signature' => true,
                ],
                [
                    'slug' => 'salade-fruits', 'name' => 'Salade de fruits de saison', 'price' => 2800,
                    'description' => 'Mangue, papaye, ananas, fruit de la passion.',
                    'image' => 'https://images.unsplash.com/photo-1490474418585-ba9bad8fd0ea?auto=format&fit=crop&w=800&q=70',
                    'allergens' => [], 'tags' => ['végétalien', 'sans gluten'],
                ],
            ],
        ],

        [
            'slug' => 'boissons',
            'name' => 'Boissons fraîches',
            'description' => 'Pressées et infusées maison.',
            'room_service' => true,
            'kitchen' => false,          // servi au bar, ne passe pas par la cuisine
            'venues' => ['restaurant-teranga', 'rooftop-baobab', 'lobby-lounge', 'pool-bar'],
            'items' => [
                ['slug' => 'bissap', 'name' => 'Bissap maison', 'price' => 1500, 'description' => 'Infusion glacée d’hibiscus, fleur d’oranger.', 'image' => 'https://images.unsplash.com/photo-1499638673689-79a0b5115d87?auto=format&fit=crop&w=800&q=70', 'allergens' => [], 'tags' => ['sans alcool']],
                ['slug' => 'bouye', 'name' => 'Jus de bouye', 'price' => 1500, 'description' => 'Pain de singe onctueux, peu sucré.', 'image' => 'https://images.unsplash.com/photo-1546173159-315724a31696?auto=format&fit=crop&w=800&q=70', 'allergens' => [], 'tags' => ['sans alcool']],
                ['slug' => 'gingembre', 'name' => 'Gingembre citron', 'price' => 1800, 'description' => 'Pressé minute, bien relevé.', 'image' => 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?auto=format&fit=crop&w=800&q=70', 'allergens' => [], 'tags' => ['sans alcool', 'épicé']],
                ['slug' => 'eau-minerale', 'name' => 'Eau minérale 50cl', 'price' => 1000, 'description' => 'Plate ou gazeuse.', 'image' => null, 'allergens' => [], 'tags' => [],
                    'options' => [['name' => 'Type', 'type' => 'single', 'required' => true, 'choices' => [['name' => 'Plate', 'price_delta' => 0], ['name' => 'Gazeuse', 'price_delta' => 0]]]]],
            ],
        ],

        [
            'slug' => 'cocktails',
            'name' => 'Cocktails & bières',
            'description' => 'Servis au Rooftop Baobab.',
            'room_service' => false,
            'kitchen' => false,          // bar
            'venues' => ['rooftop-baobab', 'pool-bar'],
            'items' => [
                ['slug' => 'cocktail-baobab', 'name' => 'Signature Baobab', 'price' => 5000, 'description' => 'Rhum ambré, bouye, citron vert, sirop de gingembre.', 'image' => 'https://images.unsplash.com/photo-1551024709-8f23befc6f87?auto=format&fit=crop&w=800&q=70', 'allergens' => [], 'tags' => ['signature'], 'signature' => true],
                ['slug' => 'bissap-spritz', 'name' => 'Bissap Spritz', 'price' => 4500, 'description' => 'Vin pétillant, bissap, eau gazeuse.', 'image' => 'https://images.unsplash.com/photo-1560512823-829485b8bf24?auto=format&fit=crop&w=800&q=70', 'allergens' => ['sulfites'], 'tags' => []],
                ['slug' => 'biere-locale', 'name' => 'Bière locale 33cl', 'price' => 2500, 'description' => 'Blonde, servie fraîche.', 'image' => null, 'allergens' => ['gluten'], 'tags' => []],
            ],
        ],
    ],

    // Nombre de tables générées par lieu qui accepte le QR (hors room service).
    'tables_per_venue' => [
        'restaurant-teranga' => 18,
        'rooftop-baobab' => 12,
        'lobby-lounge' => 6,
        'pool-bar' => 8,
    ],

    // Paramètres commande.
    'service_charge_rate' => 0.0,   // service compris
    'tax_rate' => 0.10,
];
