<?php

/*
|--------------------------------------------------------------------------
| Comptabilité — partie double légère (§41)
|--------------------------------------------------------------------------
| Plan de comptes inspiré SYSCOHADA (simplifié). Les écritures sont
| générées automatiquement par App\Services\Accounting depuis les
| opérations (encaissements, dépenses, réceptions d'achat, règlements
| fournisseurs). États de synthèse (compte de résultat, bilan), déclaration
| de TVA, exercices comptables + clôture (regroupement des comptes de
| gestion, verrouillage de la période) gérés dans App\Services\Accounting.
*/

return [

    // Comptes de synthèse utilisés par la clôture d'exercice.
    'result_account' => '120000',        // Résultat net de l'exercice
    'retained_earnings_account' => '110000', // Report à nouveau
    'closing_journal' => 'CL',

    // code => [libellé, type]  (type : asset | liability | equity | income | expense)
    'chart' => [
        '110000' => ['Report à nouveau', 'equity'],
        '120000' => ['Résultat net de l’exercice', 'equity'],
        '101000' => ['Capital', 'equity'],
        '411000' => ['Clients', 'asset'],
        '401000' => ['Fournisseurs', 'liability'],
        '447000' => ['État & organismes sociaux, retenues', 'liability'],
        '445100' => ['TVA récupérable', 'asset'],
        '443100' => ['TVA facturée', 'liability'],
        '521000' => ['Banque', 'asset'],
        '531000' => ['Mobile money', 'asset'],
        '571000' => ['Caisse', 'asset'],
        '601000' => ['Achats stockés', 'expense'],
        '605000' => ['Énergie & fluides', 'expense'],
        '622000' => ['Services extérieurs', 'expense'],
        '622100' => ['Commissions OTA & distribution', 'expense'],
        '631000' => ['Impôts & taxes', 'expense'],
        '658000' => ['Charges diverses', 'expense'],
        '661000' => ['Charges de personnel', 'expense'],
        '706100' => ['Prestations hébergement', 'income'],
        '706200' => ['Restauration & bars', 'income'],
        '706300' => ['Séminaires & événements', 'income'],
        '758000' => ['Produits divers', 'income'],
    ],

    'journals' => [
        'VT' => 'Ventes',
        'AC' => 'Achats',
        'CA' => 'Caisse',
        'BQ' => 'Banque',
        'OD' => 'Opérations diverses',
        'CL' => 'Clôture d’exercice',
    ],

    'posting' => [
        'income' => [
            'hebergement' => '706100',
            'restaurant' => '706200',
            'evenements' => '706300',
            'divers_produits' => '758000',
        ],
        'expense' => [
            'achats' => '601000',
            'salaires' => '661000',
            'energie' => '605000',
            'maintenance' => '622000',
            'marketing' => '622000',
            'services_ext' => '622000',
            'commissions_ota' => '622100',
            'taxes' => '631000',
            'divers_charges' => '658000',
        ],
        'accounts' => [
            'suppliers' => '401000',
            'customers' => '411000',
            'vat_deductible' => '445100',
            'vat_collected' => '443100',
        ],
        // type de compte financier => compte du plan
        'treasury' => [
            'cash' => '571000',
            'bank' => '521000',
            'mobile' => '531000',
        ],
        'journal_for' => [
            'income' => 'VT',
            'expense' => 'OD',
            'purchase_receipt' => 'AC',
            'supplier_payment' => 'BQ',
        ],
    ],
];
