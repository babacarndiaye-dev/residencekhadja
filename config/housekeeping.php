<?php

/*
|--------------------------------------------------------------------------
| Housekeeping (§33–34)
|--------------------------------------------------------------------------
*/

return [

    'task_types' => [
        'departure' => 'Recouche départ',
        'stayover' => 'Recouche séjour',
        'touch_up' => 'Rafraîchissement',
        'deep_clean' => 'Nettoyage à fond',
        'inspection' => 'Inspection seule',
    ],

    'incident_categories' => [
        'damage' => 'Dégât / casse',
        'missing_item' => 'Article manquant',
        'lost_found' => 'Objet trouvé',
        'cleanliness' => 'Propreté',
        'maintenance' => 'Problème technique',
        'other' => 'Autre',
    ],

    // Check-list de contrôle qualité appliquée à l'inspection d'une chambre.
    'qc_checklist' => [
        'Lit fait, linge sans tache ni pli',
        'Sol aspiré et lavé, plinthes propres',
        'Salle de bain désinfectée, robinetterie détartrée',
        'Sanitaires impeccables, joints propres',
        'Poussières faites (meubles, cadres, luminaires)',
        'Vitres et miroirs sans trace',
        'Produits d’accueil et linge de bain réassortis',
        'Minibar réassorti et à température',
        'Télécommande, téléphone et interrupteurs désinfectés',
        'Odeur neutre, aération effectuée',
        'Climatisation et éclairage fonctionnels',
        'Coffre ouvert et vide, cintres complets',
    ],

    // Score de contrôle en dessous duquel la chambre repart en nettoyage.
    'qc_pass_score' => 85,
];
