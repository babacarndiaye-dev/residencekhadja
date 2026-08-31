<?php

/*
|--------------------------------------------------------------------------
| Programme de fidélité — « Khadija Privilège » (§55–57)
|--------------------------------------------------------------------------
| Les paliers sont chargés en base par CrmSeeder ; ce fichier reste la
| source d'ajustement. Les points sont crédités automatiquement à chaque
| encaissement de séjour (observer Payment).
*/

return [

    'program_name' => 'Khadija Privilège',
    'member_prefix' => 'KP',

    // Points gagnés par tranche de 1 000 FCFA dépensés (avant application du taux de palier).
    'base_earn_per_1000' => 1,

    // Valeur d'un point à l'utilisation (FCFA) — pour les remises à la réception.
    'point_value_fcfa' => 5,

    // Palier minimum de rachat.
    'min_redeem_points' => 500,

    // Durée de validité indicative des points (mois) — affichage seulement.
    'points_validity_months' => 24,

    'tiers' => [
        [
            'code' => 'DECOUVERTE',
            'name' => 'Découverte',
            'min_points' => 0,
            'earn_rate' => 1.0,
            'color' => '#596d7a',
            'perks' => [
                'Wi-Fi premium offert',
                'Tarif membre sur la réservation directe',
            ],
        ],
        [
            'code' => 'PRIVILEGE',
            'name' => 'Privilège',
            'min_points' => 3000,
            'earn_rate' => 1.25,
            'color' => '#61767d',
            'perks' => [
                'Surclassement selon disponibilité',
                'Check-out tardif à 14 h',
                '-10 % au restaurant',
            ],
        ],
        [
            'code' => 'PRESTIGE',
            'name' => 'Prestige',
            'min_points' => 9000,
            'earn_rate' => 1.5,
            'color' => '#de6443',
            'perks' => [
                'Surclassement garanti (1 catégorie)',
                'Petit-déjeuner offert',
                'Transfert aéroport offert (1 A/R par séjour)',
                'Ligne dédiée réservation',
            ],
        ],
    ],
];
