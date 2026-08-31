<?php

/*
|--------------------------------------------------------------------------
| CRM — vue client 360°, historique, préférences, consentement (§18–20, §52–54)
|--------------------------------------------------------------------------
*/

return [

    'interaction_types' => [
        'note' => 'Note interne',
        'call' => 'Appel',
        'email' => 'E-mail',
        'sms' => 'SMS',
        'complaint' => 'Réclamation',
        'compliment' => 'Compliment',
    ],

    'acquisition_sources' => [
        'direct' => 'Site direct',
        'walk_in' => 'Passage / walk-in',
        'phone' => 'Téléphone',
        'ota' => 'Agence en ligne (OTA)',
        'corporate' => 'Compte entreprise',
        'event' => 'Événement / séminaire',
        'referral' => 'Recommandation',
    ],

    // Étiquettes proposées pour segmenter rapidement un client.
    'tag_suggestions' => [
        'VIP', 'Affaires', 'Famille', 'Lune de miel', 'Séminaire',
        'Habitué', 'Sensible au prix', 'Allergies', 'Végétarien', 'PMR',
    ],
];
