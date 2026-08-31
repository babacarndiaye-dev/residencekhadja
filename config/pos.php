<?php

/*
|--------------------------------------------------------------------------
| Caisse restaurant (POS)
|--------------------------------------------------------------------------
|
| Seuils de sécurité, coupures pour le comptage de caisse et imputation
| comptable des remboursements au comptoir.
|
*/

return [

    // Au-delà de l'un de ces seuils, une remise exige un PIN manager.
    'discount' => [
        'max_percent' => 10,
        'max_amount' => 5000,
    ],

    // Remboursement caisse → journal financier (dépense).
    'refund' => [
        'expense_category' => 'divers_charges',
    ],

    // Coupures FCFA proposées au comptage (fond d'ouverture + clôture).
    'denominations' => [10000, 5000, 2000, 1000, 500, 100, 50, 25, 10, 5],

    // Longueur acceptée pour le PIN caisse.
    'pin' => [
        'min' => 4,
        'max' => 6,
    ],

    // Rôles autorisés à autoriser une opération sensible par PIN.
    'manager_roles' => ['direction', 'admin'],

    // Rôles proposés comme « serveur » sur un ticket.
    'server_roles' => ['restaurant', 'reception', 'direction'],

    // Compte de trésorerie (type cash) de la caisse restaurant — la session
    // ouverte sur ce compte reçoit les encaissements espèces du POS.
    'cash_account_code' => 'CAI-02',

    // Contrôle du petit-déjeuner.
    'breakfast' => [
        // Clé du service dans config('booking.extras') : sert au prix carte et
        // au test « inclus » (présent dans reservation.extras).
        'extra_key' => 'petit-dejeuner',
    ],

    // Recettes → décrément automatique du stock à la vente.
    'recipe' => [
        // Code de magasin par catégorie de stock (résolution du magasin qui fournit
        // l'ingrédient si la ligne de recette n'en précise pas).
        'warehouse_by_stock_category' => [
            'aliments' => 'RC',
            'boissons' => 'CB',
        ],
        'default_warehouse_code' => 'RC',
    ],

    // Centre d'alertes POS (commande pos:alerts).
    'alerts' => [
        'kitchen_late_minutes' => 20,
        'cancel_rate_threshold' => 0.15,
        'session_max_hours' => 14,
    ],

    // Postes du terminal (identification légère) → type de vente par défaut.
    'stations' => [
        'restaurant' => 'Caisse restaurant',
        'bar' => 'Bar',
        'room_service' => 'Room service',
    ],

    // Mentions imprimées (surchargeables via /admin/parametres).
    'ticket_footer' => 'Merci de votre visite — à bientôt !',
    'invoice_legal' => '',
];
