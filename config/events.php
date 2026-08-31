<?php

/*
|--------------------------------------------------------------------------
| Commercial & Événements (MICE) — §21–24 (séminaires, banquets, mariages)
|--------------------------------------------------------------------------
| Pipeline commercial → devis → événement confirmé → feuille de fonction (BEO)
| → acompte / solde en trésorerie. Salles chargées en base par EventsSeeder.
*/

return [

    'default_tax_rate' => 0.18,
    'default_deposit_rate' => 0.30,
    'quote_validity_days' => 14,
    'option_hold_days' => 7,

    'event_types' => [
        'seminaire' => 'Séminaire / journée d’étude',
        'conference' => 'Conférence / convention',
        'gala' => 'Dîner de gala / soirée d’entreprise',
        'mariage' => 'Mariage / cérémonie familiale',
        'reunion' => 'Réunion / comité',
        'lancement' => 'Lancement de produit',
        'autre' => 'Autre',
    ],

    // Étapes du pipeline commercial (ordre = progression).
    'pipeline_stages' => [
        'nouveau' => ['label' => 'Nouveau', 'color' => '#596d7a'],
        'qualifie' => ['label' => 'Qualifié', 'color' => '#77878f'],
        'devis' => ['label' => 'Devis envoyé', 'color' => '#61767d'],
        'negociation' => ['label' => 'Négociation', 'color' => '#7e9299'],
        'gagne' => ['label' => 'Gagné', 'color' => '#2f855a'],
        'perdu' => ['label' => 'Perdu', 'color' => '#a73514'],
    ],

    'lead_sources' => [
        'site' => 'Site web',
        'telephone' => 'Téléphone',
        'email' => 'E-mail direct',
        'recommandation' => 'Recommandation',
        'agence' => 'Agence événementielle',
        'salon' => 'Salon professionnel',
        'client_existant' => 'Client existant',
        'salle' => 'Demande de salle',
    ],

    'activity_types' => [
        'note' => 'Note',
        'call' => 'Appel',
        'email' => 'E-mail',
        'meeting' => 'Rendez-vous',
        'task' => 'Tâche à faire',
    ],

    'quote_item_categories' => [
        'location' => 'Location de salle',
        'restauration' => 'Restauration',
        'pause' => 'Pauses-café',
        'technique' => 'Technique & régie',
        'hebergement' => 'Hébergement',
        'divers' => 'Divers / prestations',
    ],

    'layouts' => ['Théâtre', 'Classe', 'U', 'Banquet', 'Cocktail', 'Réunion', 'Comité'],

    'agenda_categories' => [
        'accueil' => 'Accueil & logistique',
        'restauration' => 'Restauration',
        'technique' => 'Technique',
        'logistique' => 'Logistique salle',
        'autre' => 'Autre',
    ],

    // Forfaits restauration (prix par personne, FCFA).
    'catering_packages' => [
        ['code' => 'PAUSE_SIMPLE', 'category' => 'pause', 'name' => 'Pause simple (café, thé, eau)', 'per_pax_price' => 2500],
        ['code' => 'PAUSE_GOURMANDE', 'category' => 'pause', 'name' => 'Pause gourmande (viennoiseries, jus, fruits)', 'per_pax_price' => 4500],
        ['code' => 'DEJ_AFFAIRES', 'category' => 'restauration', 'name' => 'Déjeuner d’affaires 3 services', 'per_pax_price' => 15000],
        ['code' => 'COCKTAIL', 'category' => 'restauration', 'name' => 'Cocktail dînatoire (12 pièces)', 'per_pax_price' => 18000],
        ['code' => 'DINER_GALA', 'category' => 'restauration', 'name' => 'Dîner de gala 4 services', 'per_pax_price' => 28000],
    ],

    // Matériel technique (prix par unité et par jour, FCFA).
    'av_equipment' => [
        ['code' => 'VIDEOPROJ', 'name' => 'Vidéoprojecteur 4K + écran', 'unit' => 'jour', 'price' => 45000],
        ['code' => 'SONO', 'name' => 'Sonorisation + 2 micros HF', 'unit' => 'jour', 'price' => 60000],
        ['code' => 'ECRAN_LED', 'name' => 'Mur d’images LED', 'unit' => 'jour', 'price' => 250000],
        ['code' => 'TRADUCTION', 'name' => 'Cabine de traduction + 50 récepteurs', 'unit' => 'jour', 'price' => 180000],
        ['code' => 'REGISSEUR', 'name' => 'Régisseur technique', 'unit' => 'jour', 'price' => 55000],
        ['code' => 'PADDEX', 'name' => 'Paperboard + kit conférencier', 'unit' => 'jour', 'price' => 8000],
    ],
];
