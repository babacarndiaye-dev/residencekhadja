<?php

/*
|--------------------------------------------------------------------------
| Ressources humaines (§43–44, §46)
|--------------------------------------------------------------------------
*/

return [

    'departments' => [
        ['name' => 'Direction', 'code' => 'DIR'],
        ['name' => 'Réception & Front Office', 'code' => 'FO'],
        ['name' => 'Housekeeping', 'code' => 'HK'],
        ['name' => 'Restauration & Cuisine', 'code' => 'FB'],
        ['name' => 'Maintenance & Technique', 'code' => 'TECH'],
        ['name' => 'Économat & Achats', 'code' => 'ECO'],
        ['name' => 'Finance & Comptabilité', 'code' => 'FIN'],
        ['name' => 'Commercial & Événementiel', 'code' => 'COM'],
        ['name' => 'Sécurité', 'code' => 'SEC'],
    ],

    'positions' => [
        'FO' => ['Chef de réception', 'Réceptionniste', 'Night auditor', 'Bagagiste', 'Concierge'],
        'HK' => ['Gouvernante générale', 'Gouvernante d’étage', 'Femme de chambre', 'Équipier', 'Lingère'],
        'FB' => ['Chef de cuisine', 'Sous-chef', 'Commis de cuisine', 'Maître d’hôtel', 'Chef de rang', 'Serveur', 'Barman', 'Plongeur', 'Pâtissier'],
        'TECH' => ['Responsable technique', 'Technicien polyvalent', 'Électricien', 'Plombier'],
        'ECO' => ['Responsable économat', 'Magasinier', 'Agent de réception marchandises'],
        'FIN' => ['Chef comptable', 'Comptable', 'Caissier'],
        'COM' => ['Directeur commercial', 'Chargé de clientèle', 'Coordinateur événementiel'],
        'SEC' => ['Chef de sécurité', 'Agent de sécurité'],
        'DIR' => ['Directeur général', 'Assistante de direction'],
    ],

    'contract_types' => [
        'cdi' => 'CDI',
        'cdd' => 'CDD',
        'stage' => 'Stage',
        'interim' => 'Intérim',
        'journalier' => 'Journalier / extra',
    ],

    'employment_statuses' => [
        'active' => 'En poste',
        'on_leave' => 'En congé',
        'suspended' => 'Suspendu',
        'terminated' => 'Sorti',
    ],

    'marital_statuses' => [
        'celibataire' => 'Célibataire',
        'marie' => 'Marié(e)',
        'divorce' => 'Divorcé(e)',
        'veuf' => 'Veuf / Veuve',
    ],

    // Motifs de sortie (fin de collaboration).
    'termination_reasons' => [
        'demission' => 'Démission',
        'licenciement' => 'Licenciement',
        'fin_cdd' => 'Fin de CDD',
        'rupture_essai' => 'Rupture période d’essai',
        'rupture_conventionnelle' => 'Rupture d’un commun accord',
        'retraite' => 'Départ à la retraite',
        'abandon_poste' => 'Abandon de poste',
        'deces' => 'Décès',
    ],

    // Préavis légal par défaut (jours) — ajustable par contrat/motif.
    'notice_period_days' => 30,

    // Alertes RH (tableau de bord + commande hr:alerts).
    'alerts' => [
        'trial_lead_days' => 10,     // fin de période d'essai imminente
        'contract_lead_days' => 45,  // fin de CDD imminente
        'document_lead_days' => 30,  // pièce / visite médicale expirant
    ],

    // Jours fériés fixes (les fêtes mobiles — Korité, Tabaski, Ascension… —
    // se saisissent chaque année dans /admin/rh/services).
    'fixed_holidays' => [
        ['month' => 1, 'day' => 1, 'name' => 'Jour de l’An'],
        ['month' => 4, 'day' => 4, 'name' => 'Fête de l’Indépendance'],
        ['month' => 5, 'day' => 1, 'name' => 'Fête du Travail'],
        ['month' => 8, 'day' => 15, 'name' => 'Assomption'],
        ['month' => 11, 'day' => 1, 'name' => 'Toussaint'],
        ['month' => 12, 'day' => 25, 'name' => 'Noël'],
    ],

    'document_categories' => [
        'contrat' => 'Contrat',
        'piece_identite' => "Pièce d'identité",
        'diplome' => 'Diplôme',
        'certificat' => 'Certificat / attestation',
        'rib' => 'RIB',
        'medical' => 'Visite médicale',
        'autre' => 'Autre',
    ],

    'leave_types' => [
        'conge_paye' => ['label' => 'Congé payé', 'paid' => true, 'deducts_balance' => true],
        'maladie' => ['label' => 'Congé maladie', 'paid' => true, 'deducts_balance' => false],
        'sans_solde' => ['label' => 'Congé sans solde', 'paid' => false, 'deducts_balance' => false],
        'maternite' => ['label' => 'Congé maternité', 'paid' => true, 'deducts_balance' => false],
        'paternite' => ['label' => 'Congé paternité', 'paid' => true, 'deducts_balance' => false],
        'exceptionnel' => ['label' => 'Absence exceptionnelle', 'paid' => true, 'deducts_balance' => false],
    ],

    // Droit à congé accumulé chaque mois (jours ouvrables). ~24 j / an au Sénégal.
    'leave_accrual_per_month' => 2,

    // Modèles de shift.
    'shift_templates' => [
        ['name' => 'Matin', 'start' => '06:00', 'end' => '14:00', 'break' => 30, 'color' => '#de6443'],
        ['name' => 'Journée', 'start' => '08:00', 'end' => '17:00', 'break' => 60, 'color' => '#596d7a'],
        ['name' => 'Après-midi', 'start' => '14:00', 'end' => '22:00', 'break' => 30, 'color' => '#61767d'],
        ['name' => 'Nuit', 'start' => '22:00', 'end' => '06:00', 'break' => 30, 'color' => '#202628'],
    ],

    // Pointage borne.
    'clock' => [
        'grace_minutes' => 5,        // tolérance avant de compter un retard
        'min_break_minutes' => 0,
        'auto_close_hours' => 16,    // ferme un pointage oublié au-delà
    ],
];
