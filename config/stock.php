<?php

/*
|--------------------------------------------------------------------------
| Stocks & Achats (§37–39)
|--------------------------------------------------------------------------
*/

return [

    'units' => ['unité', 'kg', 'g', 'L', 'cl', 'carton', 'lot', 'bouteille', 'sac', 'rouleau'],

    'categories' => [
        'aliments' => 'Aliments',
        'boissons' => 'Boissons',
        'consommables' => 'Consommables',
        'emballages' => 'Emballages',
        'entretien' => "Produits d'entretien",
        'economat' => 'Économat / divers',
    ],

    'warehouse_types' => [
        'general' => 'Magasin général',
        'kitchen' => 'Réserve cuisine',
        'bar' => 'Cave / bar',
        'housekeeping' => 'Lingerie / housekeeping',
        'maintenance' => 'Atelier maintenance',
    ],

    // Motifs de mouvement (traçabilité).
    'movement_reasons' => [
        'purchase_receipt' => 'Réception achat',
        'consumption' => 'Consommation / sortie',
        'transfer' => 'Transfert inter-magasin',
        'inventory' => 'Ajustement inventaire',
        'loss' => 'Perte / casse',
        'return' => 'Retour fournisseur',
        'initial' => 'Stock initial',
    ],

    'tax_rate' => 0.18, // TVA achats (indicative)
];
