<?php

/*
|--------------------------------------------------------------------------
| Satisfaction & e-réputation (§ audit #10)
|--------------------------------------------------------------------------
| Enquête post-séjour → note globale + NPS + notes par critère + commentaire.
| Les commentaires avec accord du client sont publiables sur la vitrine.
| Les détracteurs alertent la réception / la direction.
*/

return [

    // Délai après le départ avant l'envoi de l'invitation (jours).
    'invite_delay_days' => 1,

    // Relance unique si l'invitation n'a pas été complétée (0 = pas de relance).
    'reminder_after_days' => 5,

    // Durée de validité du lien d'enquête (jours).
    'link_ttl_days' => 30,

    // Note globale (sur 5) en dessous de laquelle on alerte l'équipe.
    'alert_at_or_below' => 3,

    // Critères notés sur 5 (clé => libellé). Modifiable sans migration.
    'categories' => [
        'room' => 'Chambre & confort',
        'cleanliness' => 'Propreté',
        'staff' => 'Accueil & personnel',
        'food' => 'Restauration',
        'value' => 'Rapport qualité-prix',
    ],

    'nps_question' => 'Recommanderiez-vous l’Hôtel Résidence Khadija à un proche ?',

    // Vitrine : nombre d'avis publiés affichés (complétés par config/reviews.php si besoin).
    'public_reviews_count' => 8,

    // Après un avis positif, on invite le client à le partager publiquement.
    'promoter_score' => 9,          // NPS >= 9 = promoteur
    'google_review_url' => env('GOOGLE_REVIEW_URL'),
    'tripadvisor_url' => env('TRIPADVISOR_URL'),
];
