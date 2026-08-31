<?php

/*
|--------------------------------------------------------------------------
| Application mobile invité (PWA) — §58–60
|--------------------------------------------------------------------------
| Espace `/app` : le client s'y connecte avec sa référence de réservation
| + son nom, ou via un lien magique remis à la réception. Installable
| (manifest + service worker), fonctionne hors-ligne pour le socle.
*/

return [

    'enabled' => true,

    // Service worker (cache hors-ligne). À n'activer qu'avec un vrai certificat TLS :
    // avec un certificat auto-signé, les requêtes fetch() du SW échouent et l'app
    // affiche en boucle la page « hors ligne ». L'installation « écran d'accueil »
    // (manifeste + icône) fonctionne indépendamment.
    'service_worker' => (bool) env('GUEST_APP_SW', false),

    'name' => 'Résidence Khadija',
    'short_name' => 'Khadija',
    'theme_color' => '#374249',
    'background_color' => '#f8f9f7',

    // Durée de vie du jeton d'accès invité (jours).
    'token_ttl_days' => 21,

    'wifi' => [
        'ssid' => env('GUEST_WIFI_SSID', 'Residence-Khadija'),
        'password' => env('GUEST_WIFI_PASSWORD', 'teranga2026'),
    ],

    // Demandes chambre proposées dans l'app.
    'request_types' => [
        'menage' => 'Ménage de la chambre',
        'linge' => 'Change du linge / serviettes',
        'articles' => 'Articles (oreiller, couverture, nécessaire)',
        'depannage' => 'Dépannage technique',
        'reveil' => 'Réveil / rappel',
        'transport' => 'Taxi / transfert',
        'conciergerie' => 'Conciergerie',
        'checkout' => 'Départ express (check-out)',
        'autre' => 'Autre demande',
    ],

    // Demandes qui remontent à la maintenance plutôt qu'à la réception.
    'maintenance_types' => ['depannage'],

    /*
    |----------------------------------------------------------------------
    | Catalogue de services demandables en chambre (GEMS §4)
    |----------------------------------------------------------------------
    | Affiché sur /app/demandes. `price` en FCFA (0 = gratuit / sur demande).
    | Un service payant crée aussitôt une ligne de folio (reservation_charges)
    | rattachée à la demande. `routed_to` ∈ reception | housekeeping | maintenance.
    | `qty` = true → l'invité choisit une quantité (1..5).
    */
    'service_categories' => [
        'restauration' => 'Restauration',
        'menage' => 'Ménage & linge',
        'confort' => 'Confort',
        'bien_etre' => 'Bien-être',
        'transport' => 'Transport',
        'maintenance' => 'Dépannage',
        'divers' => 'Conciergerie',
    ],

    // Icône Material Symbols par catégorie (pour les puces de filtre).
    'service_category_icons' => [
        'restauration' => 'restaurant',
        'menage' => 'cleaning_services',
        'confort' => 'king_bed',
        'bien_etre' => 'spa',
        'transport' => 'local_taxi',
        'maintenance' => 'build',
        'divers' => 'concierge',
    ],

    'services' => [
        // Restauration → réception (coordination room-service ; la carte complète reste /app/carte).
        ['slug' => 'petit_dej_chambre', 'category' => 'restauration', 'label' => 'Petit-déjeuner en chambre', 'desc' => 'Servi de 6h30 à 10h30', 'icon' => '🥐', 'price' => 6000, 'routed_to' => 'reception', 'qty' => true],
        ['slug' => 'plateau_repas', 'category' => 'restauration', 'label' => 'Plateau repas', 'desc' => 'Plat du jour + boisson + dessert', 'icon' => '🍽️', 'price' => 9000, 'routed_to' => 'reception', 'qty' => true],
        ['slug' => 'boissons_chambre', 'category' => 'restauration', 'label' => 'Boissons / eau minérale', 'desc' => 'Sélection minibar', 'icon' => '🥤', 'price' => 3000, 'routed_to' => 'reception', 'qty' => true],
        ['slug' => 'the_cafe', 'category' => 'restauration', 'label' => 'Thé / café', 'desc' => 'Théière ou cafetière', 'icon' => '☕', 'price' => 2500, 'routed_to' => 'reception', 'qty' => true],

        // Ménage & linge → housekeeping (gratuit).
        ['slug' => 'menage_chambre', 'category' => 'menage', 'label' => 'Ménage de la chambre', 'desc' => 'Passage à l\'horaire souhaité', 'icon' => '🧹', 'price' => 0, 'routed_to' => 'housekeeping', 'qty' => false],
        ['slug' => 'change_linge', 'category' => 'menage', 'label' => 'Change du linge / serviettes', 'desc' => 'Draps et serviettes propres', 'icon' => '🛏️', 'price' => 0, 'routed_to' => 'housekeeping', 'qty' => false],
        ['slug' => 'blanchisserie', 'category' => 'menage', 'label' => 'Blanchisserie / pressing', 'desc' => 'Retour sous 24 h — facturé au poids', 'icon' => '👔', 'price' => 5000, 'routed_to' => 'housekeeping', 'qty' => false],

        // Confort → housekeeping.
        ['slug' => 'articles_chambre', 'category' => 'confort', 'label' => 'Oreiller / couverture en plus', 'desc' => 'Nécessaire de couchage', 'icon' => '🧷', 'price' => 0, 'routed_to' => 'housekeeping', 'qty' => true],
        ['slug' => 'necessaire_toilette', 'category' => 'confort', 'label' => 'Nécessaire de toilette', 'desc' => 'Brosse à dents, rasoir, coton…', 'icon' => '🧴', 'price' => 0, 'routed_to' => 'housekeeping', 'qty' => false],
        ['slug' => 'lit_appoint', 'category' => 'confort', 'label' => 'Lit d\'appoint', 'desc' => 'Installé avant votre retour', 'icon' => '🛌', 'price' => 15000, 'routed_to' => 'housekeeping', 'qty' => false],

        // Bien-être & transport → réception.
        ['slug' => 'massage_chambre', 'category' => 'bien_etre', 'label' => 'Massage en chambre (50 min)', 'desc' => 'Sur rendez-vous, selon disponibilité', 'icon' => '💆', 'price' => 25000, 'routed_to' => 'reception', 'qty' => false],
        ['slug' => 'taxi_ville', 'category' => 'transport', 'label' => 'Taxi — course en ville', 'desc' => 'Départ sous 15 min', 'icon' => '🚕', 'price' => 5000, 'routed_to' => 'reception', 'qty' => false],
        ['slug' => 'transfert_aeroport', 'category' => 'transport', 'label' => 'Transfert aéroport (AIBD)', 'desc' => 'Véhicule privé avec chauffeur', 'icon' => '🚙', 'price' => 30000, 'routed_to' => 'reception', 'qty' => false],

        // Dépannage → maintenance.
        ['slug' => 'depannage_chambre', 'category' => 'maintenance', 'label' => 'Dépannage technique', 'desc' => 'Clim, plomberie, électricité, TV…', 'icon' => '🛠️', 'price' => 0, 'routed_to' => 'maintenance', 'qty' => false],

        // Conciergerie → réception.
        ['slug' => 'reveil', 'category' => 'divers', 'label' => 'Réveil / rappel', 'desc' => 'Indiquez l\'heure en précision', 'icon' => '⏰', 'price' => 0, 'routed_to' => 'reception', 'qty' => false],
        ['slug' => 'late_checkout', 'category' => 'divers', 'label' => 'Départ tardif (late check-out)', 'desc' => 'Chambre gardée jusqu\'à 15h', 'icon' => '🕒', 'price' => 10000, 'routed_to' => 'reception', 'qty' => false],
        ['slug' => 'conciergerie', 'category' => 'divers', 'label' => 'Conciergerie', 'desc' => 'Réservation resto, excursion, autre', 'icon' => '🛎️', 'price' => 0, 'routed_to' => 'reception', 'qty' => false],
    ],

    // Raccourcis de l'écran d'accueil invité. `cat` = catégorie de services ciblée
    // (le catalogue s'ouvre filtré dessus) ; sinon `to` = destination directe.
    'quick_actions' => [
        ['label' => 'Room service', 'icon' => 'restaurant', 'cat' => 'restauration'],
        ['label' => 'Ménage', 'icon' => 'cleaning_services', 'cat' => 'menage'],
        ['label' => 'Dépannage', 'icon' => 'build', 'cat' => 'maintenance'],
        ['label' => 'Transport', 'icon' => 'local_taxi', 'cat' => 'transport'],
        ['label' => 'Wi-Fi', 'icon' => 'wifi', 'to' => 'wifi'],
        ['label' => 'Fidélité', 'icon' => 'workspace_premium', 'to' => 'loyalty'],
    ],
];
