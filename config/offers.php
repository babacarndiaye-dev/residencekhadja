<?php

/*
|--------------------------------------------------------------------------
| Offres & séjours packagés
|--------------------------------------------------------------------------
| discount = pourcentage indicatif affiché (0 = pas de remise chiffrée).
*/

return [
    [
        'slug' => 'reservation-directe',
        'name' => 'Meilleur tarif direct',
        'badge' => '-12 %',
        'discount' => 12,
        'audience' => 'Tous voyageurs',
        'summary' => 'Réservez sur ce site et profitez du meilleur tarif garanti, sans intermédiaire.',
        'includes' => ['-12 % sur le tarif flexible', 'Wi-Fi fibre gratuit', 'Surclassement selon disponibilité', 'Annulation gratuite jusqu\'à 48 h'],
        'terms' => "Offre soumise à disponibilité. Non cumulable avec d'autres promotions.",
        'image' => '/img/hotel/facade.jpg',
    ],
    [
        'slug' => 'escale-affaires',
        'name' => 'Escale Affaires',
        'badge' => 'Business',
        'discount' => 0,
        'audience' => 'Voyage d\'affaires',
        'summary' => 'Le séjour pensé pour les déplacements professionnels : arrivée fluide et soirée productive.',
        'includes' => ['Petit-déjeuner inclus', 'Check-in privatif', 'Late check-out 14h00', 'Accès Business Corner & pressing express'],
        'terms' => 'Tarif entreprise disponible via un compte société. Nous consulter.',
        'image' => '/img/hotel/suite-executive.jpg',
    ],
    [
        'slug' => 'weekend-teranga',
        'name' => 'Week-end Teranga',
        'badge' => '2 nuits',
        'discount' => 15,
        'audience' => 'Couples & familles',
        'summary' => 'Deux nuits pour découvrir Thiès, la table du chef et le rooftop au coucher du soleil.',
        'includes' => ['-15 % dès 2 nuits', 'Dîner « Saveurs du Sénégal » pour deux', 'Cocktail de bienvenue au rooftop', 'Départ tardif le dimanche'],
        'terms' => 'Réservation pour un séjour incluant la nuit du samedi. Soumis à disponibilité.',
        'image' => '/img/hotel/suite-teranga.jpg',
    ],
    [
        'slug' => 'long-sejour',
        'name' => 'Long Séjour',
        'badge' => '7 nuits +',
        'discount' => 25,
        'audience' => 'Séjours prolongés',
        'summary' => "À partir de 7 nuits, l'hôtel devient une résidence : tarifs dégressifs et services inclus.",
        'includes' => ['Jusqu\'à -25 %', 'Ménage quotidien', 'Forfait blanchisserie hebdomadaire', 'Kitchenette sur demande'],
        'terms' => 'Tarif dégressif selon la durée. Devis personnalisé sur demande.',
        'image' => '/img/hotel/suite-junior.jpg',
    ],
];
