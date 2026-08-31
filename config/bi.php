<?php

/*
|--------------------------------------------------------------------------
| Décisionnel — BI, KPI & reporting (§26–28)
|--------------------------------------------------------------------------
| Consolide les données opérationnelles (PMS, F&B, housekeeping, maintenance,
| stock, finance, RH, CRM, événements, paiements). Les instantanés quotidiens
| sont écrits par `php artisan bi:snapshot` et lus par les graphes de tendance.
*/

return [

    // Objectifs / seuils (pour les alertes du tableau de bord).
    'targets' => [
        'occupancy' => 65,          // %
        'adr' => 60000,             // FCFA
        'revpar' => 39000,          // FCFA
        'fnb_avg_check' => 9000,    // FCFA
        'cancellation_rate' => 12,  // % (max)
        'qc_pass_rate' => 90,       // %
        'online_payment_share' => 40, // %
        'loyalty_optin_rate' => 60, // %
        'event_win_rate' => 30,     // %
    ],

    // Clés calculées et stockées chaque jour dans `daily_metrics`.
    'snapshot_metrics' => [
        'rooms_sold', 'occupancy', 'adr', 'revpar',
        'room_revenue', 'fnb_revenue', 'other_revenue', 'total_revenue', 'total_expense',
        'bookings_created', 'bookings_cancelled', 'covers',
        'hk_tasks', 'hk_tasks_done', 'maintenance_open',
        'treasury', 'stock_value',
        'online_payments', 'online_payment_amount',
    ],

    // Bibliothèque de rapports exportables (CSV).
    'reports' => [
        'reservations' => ['label' => 'Réservations', 'group' => 'Hébergement'],
        'occupancy_daily' => ['label' => 'Occupation quotidienne (Occ / ADR / RevPAR)', 'group' => 'Hébergement'],
        'arrivals' => ['label' => 'Arrivées de la période', 'group' => 'Hébergement'],
        'departures' => ['label' => 'Départs de la période', 'group' => 'Hébergement'],
        'revenue_daily' => ['label' => 'Chiffre d’affaires par jour', 'group' => 'Finance'],
        'payments' => ['label' => 'Paiements en ligne', 'group' => 'Finance'],
        'ap_ar' => ['label' => 'Créances & dettes', 'group' => 'Finance'],
        'fnb_items' => ['label' => 'Ventes restaurant par article', 'group' => 'Restauration'],
        'pos_bestsellers' => ['label' => 'Caisse — meilleures ventes & marge', 'group' => 'Restauration'],
        'pos_servers' => ['label' => 'Caisse — performance serveurs', 'group' => 'Restauration'],
        'stock_valuation' => ['label' => 'Valorisation du stock', 'group' => 'Économat'],
        'loyalty_members' => ['label' => 'Membres fidélité', 'group' => 'CRM'],
        'events_pipeline' => ['label' => 'Pipeline événementiel', 'group' => 'Commercial'],
    ],
];
