<?php

/*
|--------------------------------------------------------------------------
| Guest Experience Management System (GEMS)
|--------------------------------------------------------------------------
| Tableau de bord intelligent des chambres + espace client : le contenu
| s'adapte à l'occupant (séjour, profil, droits). Phase 1 = fiche chambre
| back-office. Les segments serviront à la personnalisation (phase IA §7).
*/

return [

    'checkin_time' => '14:00',
    'checkout_time' => '12:00',

    // Catalogue de services présentés sur la fiche chambre et, plus tard,
    // dans l'espace client. `icon` = Material Symbol.
    'services' => [
        ['key' => 'room_service', 'label' => 'Room service', 'icon' => 'room_service', 'group' => 'Chambre'],
        ['key' => 'housekeeping', 'label' => 'Ménage', 'icon' => 'cleaning_services', 'group' => 'Chambre'],
        ['key' => 'laundry', 'label' => 'Blanchisserie', 'icon' => 'local_laundry_service', 'group' => 'Chambre'],
        ['key' => 'restaurant', 'label' => 'Restaurant', 'icon' => 'restaurant', 'group' => 'Restauration'],
        ['key' => 'bar', 'label' => 'Bar', 'icon' => 'local_bar', 'group' => 'Restauration'],
        ['key' => 'breakfast', 'label' => 'Petit-déjeuner', 'icon' => 'bakery_dining', 'group' => 'Restauration'],
        ['key' => 'pool_spa', 'label' => 'Piscine & Spa', 'icon' => 'pool', 'group' => 'Hôtel'],
        ['key' => 'gym', 'label' => 'Salle de sport', 'icon' => 'fitness_center', 'group' => 'Hôtel'],
        ['key' => 'wifi', 'label' => 'Wi-Fi', 'icon' => 'wifi', 'group' => 'Hôtel'],
        ['key' => 'shuttle', 'label' => 'Navette / transfert', 'icon' => 'airport_shuttle', 'group' => 'Hôtel'],
        ['key' => 'taxi', 'label' => 'Taxi', 'icon' => 'local_taxi', 'group' => 'Externe'],
        ['key' => 'excursion', 'label' => 'Excursions', 'icon' => 'tour', 'group' => 'Externe'],
    ],

    // Profils déduits du séjour (badge sur la fiche ; base de la perso IA).
    // Ordre = priorité (le premier qui correspond gagne).
    'segments' => [
        'vip' => ['label' => 'VIP', 'icon' => 'workspace_premium', 'loyalty_tiers' => ['Or', 'Platine', 'Diamant']],
        'pro' => ['label' => 'Professionnel', 'icon' => 'business_center', 'requires_company' => true],
        'famille' => ['label' => 'Famille', 'icon' => 'family_restroom', 'min_children' => 1],
    ],
];
