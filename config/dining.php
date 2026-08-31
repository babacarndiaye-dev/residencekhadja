<?php

/*
|--------------------------------------------------------------------------
| Restaurant, bars & « Saveurs du Sénégal »
|--------------------------------------------------------------------------
*/

return [

    'venues' => [
        [
            'slug' => 'restaurant-teranga',
            'name' => 'Restaurant Teranga',
            'type' => 'Cuisine sénégalaise & internationale',
            'hours' => '6h30 – 23h00',
            'desc' => 'Table principale de la maison, le Restaurant Teranga célèbre le meilleur du terroir '
                     .'sénégalais et une carte internationale généreuse, dans une salle lumineuse ouverte sur le jardin.',
            'image' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1200&q=70',
        ],
        [
            'slug' => 'rooftop-baobab',
            'name' => 'Rooftop Baobab',
            'type' => 'Bar à cocktails & tapas',
            'hours' => '17h00 – 01h00',
            'desc' => 'Au dernier étage, le Rooftop Baobab offre une vue dégagée sur Thiès, des cocktails '
                     .'signature au bissap et au bouye, et une ambiance musicale feutrée au coucher du soleil.',
            'image' => 'https://images.unsplash.com/photo-1470337458703-46ad1756a187?auto=format&fit=crop&w=1200&q=70',
        ],
        [
            'slug' => 'lobby-lounge',
            'name' => 'Lobby Lounge & Café',
            'type' => 'Café de spécialité & pâtisseries',
            'hours' => '6h30 – 22h00',
            'desc' => 'Un salon de thé contemporain pour un café Touba revisité, des pâtisseries maison '
                     ."et des rendez-vous d'affaires informels.",
            'image' => 'https://images.unsplash.com/photo-1445116572660-236099ec97a0?auto=format&fit=crop&w=1200&q=70',
        ],
        [
            'slug' => 'pool-bar',
            'name' => 'Pool Bar',
            'type' => 'Snacking & jus frais',
            'hours' => '10h00 – 19h00',
            'desc' => 'Au bord de la piscine, une carte légère de salades, grillades et jus de fruits pressés minute.',
            'image' => 'https://images.unsplash.com/photo-1544148103-0773bf10d330?auto=format&fit=crop&w=1200&q=70',
        ],
    ],

    // Rubrique « Saveurs du Sénégal » — chaque plat porte un storytelling.
    'saveurs_du_senegal' => [
        [
            'name' => 'Thiéboudienne',
            'tag' => 'Le plat national',
            'story' => 'Riz au poisson mijoté à la sauce tomate, légumes du marché et poisson farci à la '
                     .'rof (persil, ail, piment). Notre chef le prépare « bou rouge » dans une marmite en fonte, '
                     ."comme à Saint-Louis, sa ville d'origine.",
            'image' => 'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?auto=format&fit=crop&w=900&q=70',
        ],
        [
            'name' => 'Yassa Poulet',
            'tag' => 'Casamance',
            'story' => 'Poulet fermier longuement mariné au citron vert et aux oignons confits, grillé puis '
                     .'nappé de sa sauce. Un équilibre acidulé servi avec un riz parfumé.',
            'image' => 'https://images.unsplash.com/photo-1432139555190-58524dae6a55?auto=format&fit=crop&w=900&q=70',
        ],
        [
            'name' => 'Mafé',
            'tag' => 'Le réconfort',
            'story' => "Ragoût à la pâte d'arachide, viande de bœuf fondante et légumes racines. "
                     .'Une recette de famille transmise par la grand-mère de notre cheffe pâtissière.',
            'image' => 'https://images.unsplash.com/photo-1455619452474-d2be8b1e70cd?auto=format&fit=crop&w=900&q=70',
        ],
        [
            'name' => 'Pastels & Accras',
            'tag' => 'À partager',
            'story' => 'Chaussons croustillants au thon et beignets de crevettes, servis avec une sauce '
                     ."kaani relevée. L'entrée conviviale par excellence.",
            'image' => 'https://images.unsplash.com/photo-1541592106381-b31e9677c0e5?auto=format&fit=crop&w=900&q=70',
        ],
        [
            'name' => 'Bissap & Bouye',
            'tag' => 'Boissons maison',
            'story' => "Infusion glacée d'hibiscus à la fleur d'oranger et jus de pain de singe onctueux, "
                     .'pressés chaque matin et sucrés avec modération.',
            'image' => 'https://images.unsplash.com/photo-1499638673689-79a0b5115d87?auto=format&fit=crop&w=900&q=70',
        ],
        [
            'name' => 'Thiakry du Chef',
            'tag' => 'Dessert signature',
            'story' => "Couscous de mil sucré, lait caillé, mangue rôtie et éclats d'arachide caramélisée. "
                     ."La touche finale d'un repas Teranga.",
            'image' => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=900&q=70',
        ],
    ],
];
