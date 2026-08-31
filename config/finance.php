<?php

/*
|--------------------------------------------------------------------------
| Finance & Caisses (§40, §42)
|--------------------------------------------------------------------------
*/

return [

    'account_types' => [
        'cash' => 'Caisse',
        'bank' => 'Banque',
        'mobile' => 'Mobile money',
    ],

    'income_categories' => [
        'hebergement' => 'Hébergement',
        'restaurant' => 'Restaurant & bars',
        'evenements' => 'Séminaires & événements',
        'divers_produits' => 'Produits divers',
    ],

    'expense_categories' => [
        'achats' => 'Achats & approvisionnements',
        'salaires' => 'Salaires & charges',
        'energie' => 'Énergie & fluides',
        'maintenance' => 'Maintenance & entretien',
        'marketing' => 'Marketing & commissions',
        'commissions_ota' => 'Commissions OTA & distribution',
        'services_ext' => 'Services extérieurs',
        'taxes' => 'Impôts & taxes',
        'divers_charges' => 'Charges diverses',
    ],

    'payment_methods' => [
        'especes' => 'Espèces',
        'carte' => 'Carte bancaire',
        'virement' => 'Virement',
        'mobile' => 'Mobile money',
        'cheque' => 'Chèque',
    ],

    // Comptes financiers créés au seed.
    'default_accounts' => [
        ['name' => 'Caisse principale', 'type' => 'cash',  'code' => 'CAI-01'],
        ['name' => 'Caisse restaurant', 'type' => 'cash',  'code' => 'CAI-02'],
        ['name' => 'Banque - compte courant', 'type' => 'bank', 'code' => 'BQ-01'],
        ['name' => 'Mobile money', 'type' => 'mobile', 'code' => 'MM-01'],
    ],
];
