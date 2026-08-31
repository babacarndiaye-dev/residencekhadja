<?php

/*
|--------------------------------------------------------------------------
| Maintenance (§35–36)
|--------------------------------------------------------------------------
*/

return [

    'equipment_categories' => [
        'climatisation' => 'Climatisation',
        'ascenseur' => 'Ascenseurs',
        'groupe_electrogene' => 'Groupe électrogène',
        'plomberie' => 'Plomberie',
        'electricite' => 'Électricité',
        'piscine' => 'Piscine',
        'cuisine' => 'Équipements cuisine',
        'fitness' => 'Équipements fitness',
        'autre' => 'Autre',
    ],

    'ticket_priorities' => [
        'low' => 'Basse',
        'normal' => 'Normale',
        'high' => 'Haute',
        'critical' => 'Critique',
    ],

    'ticket_statuses' => [
        'open' => 'Ouvert',
        'assigned' => 'Affecté',
        'in_progress' => 'En cours',
        'on_hold' => 'En attente',
        'resolved' => 'Résolu',
        'closed' => 'Clôturé',
    ],

    // Plans de maintenance préventive créés au seed (§36).
    'preventive_plans' => [
        ['name' => 'Révision climatisation', 'category' => 'climatisation', 'interval_days' => 90, 'priority' => 'normal',
            'checklist' => ['Nettoyage filtres', 'Contrôle gaz réfrigérant', 'Vérification condensats', 'Test thermostat', 'Nettoyage unité extérieure']],
        ['name' => 'Inspection ascenseurs', 'category' => 'ascenseur', 'interval_days' => 30, 'priority' => 'high',
            'checklist' => ['Test arrêts d’urgence', 'Contrôle câbles et poulies', 'Graissage guides', 'Test éclairage cabine', 'Vérification téléalarme']],
        ['name' => 'Entretien groupe électrogène', 'category' => 'groupe_electrogene', 'interval_days' => 180, 'priority' => 'high',
            'checklist' => ['Niveau et qualité huile', 'Niveau carburant et fuites', 'Test démarrage automatique', 'Contrôle batterie', 'Nettoyage filtres à air']],
        ['name' => 'Traitement piscine', 'category' => 'piscine', 'interval_days' => 7, 'priority' => 'normal',
            'checklist' => ['pH et chlore', 'Nettoyage filtres et skimmers', 'Contrôle pompe de circulation', 'Aspiration du bassin', 'Niveau d’eau']],
        ['name' => 'Contrôle équipements cuisine', 'category' => 'cuisine', 'interval_days' => 60, 'priority' => 'normal',
            'checklist' => ['Températures chambres froides', 'Joints et fermetures', 'Dégivrage', 'Contrôle hotte et filtres', 'Test coupure gaz']],
        ['name' => 'Maintenance salle fitness', 'category' => 'fitness', 'interval_days' => 120, 'priority' => 'low',
            'checklist' => ['Serrage visserie', 'Lubrification tapis et rails', 'Contrôle câbles et poulies', 'Désinfection surfaces', 'Test arrêts d’urgence']],
        ['name' => 'Ronde plomberie', 'category' => 'plomberie', 'interval_days' => 90, 'priority' => 'normal',
            'checklist' => ['Recherche de fuites', 'Détartrage mousseurs', 'Contrôle chasse d’eau', 'Groupe de sécurité chauffe-eau', 'Évacuations']],
        ['name' => 'Contrôle tableau électrique', 'category' => 'electricite', 'interval_days' => 180, 'priority' => 'high',
            'checklist' => ['Test différentiels', 'Serrage des bornes', 'Thermographie tableau', 'Contrôle onduleur', 'Éclairage de secours']],
    ],
];
