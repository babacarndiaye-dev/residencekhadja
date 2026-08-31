<?php

/*
|--------------------------------------------------------------------------
| Paramètres du moteur de réservation (vitrine / front)
|--------------------------------------------------------------------------
| Cette étape ne persiste rien en base : le tunnel est piloté en session
| et se termine par une demande de réservation (référence + e-mail à
| l'hôtel). L'intégration PMS + paiement viendra dans une phase ultérieure.
*/

return [

    'currency' => 'FCFA',
    'tax_rate' => 0.10,   // TVA / taxes affichées à titre indicatif
    'tourist_tax' => 1000,   // taxe de séjour par personne et par nuit (FCFA)
    'min_nights' => 1,
    'max_nights' => 30,
    'max_rooms' => 5,
    'max_guests_room' => 4,
    'booking_window_months' => 12,

    // Politiques tarifaires proposées à l'étape « chambre ».
    'rate_plans' => [
        'flexible' => [
            'label' => 'Tarif flexible',
            'multiplier' => 1.0,
            'note' => 'Annulation gratuite jusqu\'à 48 h avant l\'arrivée.',
        ],
        'non_remboursable' => [
            'label' => 'Tarif non remboursable',
            'multiplier' => 0.85,
            'note' => '-15 % · Aucun remboursement en cas d\'annulation.',
        ],
    ],

    // Services additionnels (upselling) proposés à l'étape « options ».
    'extras' => [
        [
            'key' => 'petit-dejeuner',
            'name' => 'Petit-déjeuner buffet',
            'desc' => 'Buffet continental & sénégalais, servi de 6h30 à 10h30.',
            'price' => 6000,
            'unit' => 'par personne / nuit',
            'per' => 'guest_night',
        ],
        [
            'key' => 'transfert-aeroport',
            'name' => 'Transfert aéroport (AIBD)',
            'desc' => 'Chauffeur privé depuis ou vers l\'aéroport Blaise Diagne.',
            'price' => 25000,
            'unit' => 'par trajet',
            'per' => 'stay',
        ],
        [
            'key' => 'late-checkout',
            'name' => 'Départ tardif 15h00',
            'desc' => 'Profitez de la chambre jusqu\'à 15h00 le jour du départ.',
            'price' => 12000,
            'unit' => 'par séjour',
            'per' => 'stay',
        ],
        [
            'key' => 'diner-teranga',
            'name' => 'Dîner « Saveurs du Sénégal »',
            'desc' => 'Menu 3 services autour des spécialités du chef.',
            'price' => 18000,
            'unit' => 'par personne',
            'per' => 'guest',
        ],
        [
            'key' => 'spa',
            'name' => 'Rituel spa 60 min',
            'desc' => 'Massage au karité et au baobab, à réserver à l\'arrivée.',
            'price' => 30000,
            'unit' => 'par personne',
            'per' => 'guest',
        ],
    ],

    // Codes promo reconnus par le tunnel (démonstration front).
    'promo_codes' => [
        'TERANGA' => ['type' => 'percent', 'value' => 10, 'label' => 'Offre Teranga -10 %'],
        'DIRECT12' => ['type' => 'percent', 'value' => 12, 'label' => 'Meilleur tarif direct -12 %'],
        'BIENVENUE' => ['type' => 'amount',  'value' => 15000, 'label' => 'Bienvenue : -15 000 FCFA'],
    ],

    // Demandes spéciales proposées en cases à cocher.
    'special_requests' => [
        'Étage élevé',
        'Chambre calme',
        'Lit bébé',
        'Arrivée tardive (après 22h)',
        'Chambres communicantes',
        'Accès PMR',
    ],
];
