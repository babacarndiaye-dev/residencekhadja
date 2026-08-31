<?php

/*
|--------------------------------------------------------------------------
| Manifeste des réglages éditables depuis le back-office (§ Paramètres)
|--------------------------------------------------------------------------
| Chaque champ surcharge un chemin de config au démarrage
| (App\Support\SiteSettings::apply). La valeur par défaut affichée dans le
| formulaire est lue directement dans la config (fichiers config/*.php) :
| tant qu'un champ n'est pas enregistré en base, le site garde la valeur
| du fichier.
|
| type  : text | textarea | number | email | tel | url | boolean
| cast  : string | int | float | bool  (conversion avant surcharge de config)
| icon  : nom d'un Material Symbol (https://fonts.google.com/icons)
| desc  : phrase d'aide affichée en tête de section
*/

return [

    'groups' => [

        'identite' => [
            'label' => 'Identité',
            'icon' => 'hotel',
            'desc' => 'Nom, accroche et localisation — repris partout sur la vitrine, les e-mails et les documents.',
            'fields' => [
                ['key' => 'hotel.name', 'label' => 'Nom de l’établissement', 'type' => 'text', 'rules' => 'required|string|max:120'],
                ['key' => 'hotel.short_name', 'label' => 'Nom court', 'type' => 'text', 'rules' => 'required|string|max:80'],
                ['key' => 'hotel.tagline', 'label' => 'Accroche', 'type' => 'text', 'rules' => 'nullable|string|max:180'],
                ['key' => 'hotel.baseline', 'label' => 'Baseline (eyebrow accueil)', 'type' => 'text', 'rules' => 'nullable|string|max:120'],
                ['key' => 'hotel.intro', 'label' => 'Introduction (accueil / à-propos)', 'type' => 'textarea', 'rules' => 'nullable|string|max:600'],
                ['key' => 'hotel.city', 'label' => 'Ville', 'type' => 'text', 'rules' => 'required|string|max:80'],
                ['key' => 'hotel.country', 'label' => 'Pays', 'type' => 'text', 'rules' => 'required|string|max:80'],
            ],
        ],

        'accueil' => [
            'label' => 'Vitrine · Accueil',
            'icon' => 'home',
            'desc' => 'Le grand titre du hero, le sous-titre et la bande d’appel à l’action en bas de l’accueil.',
            'fields' => [
                ['key' => 'vitrine.hero_title', 'label' => 'Titre principal (hero)', 'type' => 'textarea', 'rules' => 'nullable|string|max:160'],
                ['key' => 'vitrine.hero_subtitle', 'label' => 'Sous-titre du hero', 'type' => 'textarea', 'rules' => 'nullable|string|max:320'],
                ['key' => 'vitrine.hero_cta_primary', 'label' => 'Bouton principal du hero', 'type' => 'text', 'rules' => 'nullable|string|max:40'],
                ['key' => 'vitrine.cta_title', 'label' => 'Bande CTA — titre', 'type' => 'text', 'rules' => 'nullable|string|max:120'],
                ['key' => 'vitrine.cta_text', 'label' => 'Bande CTA — texte', 'type' => 'textarea', 'rules' => 'nullable|string|max:320'],
            ],
        ],

        'chiffres' => [
            'label' => 'Vitrine · Chiffres-clés',
            'icon' => 'analytics',
            'desc' => 'Les 4 compteurs affichés sur l’accueil (nombre + libellé).',
            'fields' => [
                ['key' => 'hotel.stats.0.value', 'label' => 'Chiffre 1', 'type' => 'text', 'rules' => 'nullable|string|max:12'],
                ['key' => 'hotel.stats.0.label', 'label' => 'Libellé 1', 'type' => 'text', 'rules' => 'nullable|string|max:60'],
                ['key' => 'hotel.stats.1.value', 'label' => 'Chiffre 2', 'type' => 'text', 'rules' => 'nullable|string|max:12'],
                ['key' => 'hotel.stats.1.label', 'label' => 'Libellé 2', 'type' => 'text', 'rules' => 'nullable|string|max:60'],
                ['key' => 'hotel.stats.2.value', 'label' => 'Chiffre 3', 'type' => 'text', 'rules' => 'nullable|string|max:12'],
                ['key' => 'hotel.stats.2.label', 'label' => 'Libellé 3', 'type' => 'text', 'rules' => 'nullable|string|max:60'],
                ['key' => 'hotel.stats.3.value', 'label' => 'Chiffre 4', 'type' => 'text', 'rules' => 'nullable|string|max:12'],
                ['key' => 'hotel.stats.3.label', 'label' => 'Libellé 4', 'type' => 'text', 'rules' => 'nullable|string|max:60'],
            ],
        ],

        'teranga' => [
            'label' => 'Vitrine · Teranga & fidélité',
            'icon' => 'volunteer_activism',
            'desc' => 'Le texte « Teranga » des pages Expériences / À-propos et le pitch du programme de fidélité affiché sur la vitrine.',
            'fields' => [
                ['key' => 'experiences.teranga_pitch', 'label' => 'Texte Teranga', 'type' => 'textarea', 'rules' => 'nullable|string|max:600'],
                ['key' => 'hotel.loyalty.name', 'label' => 'Nom du programme (vitrine)', 'type' => 'text', 'rules' => 'nullable|string|max:80'],
                ['key' => 'hotel.loyalty.pitch', 'label' => 'Pitch fidélité (vitrine)', 'type' => 'textarea', 'rules' => 'nullable|string|max:400'],
            ],
        ],

        'contact' => [
            'label' => 'Contact',
            'icon' => 'call',
            'desc' => 'Téléphone, WhatsApp et e-mails — pied de page, page Contact et e-mails de confirmation.',
            'fields' => [
                ['key' => 'hotel.contact.address_line', 'label' => 'Adresse', 'type' => 'text', 'rules' => 'nullable|string|max:180'],
                ['key' => 'hotel.contact.address_area', 'label' => 'Quartier / ville', 'type' => 'text', 'rules' => 'nullable|string|max:120'],
                ['key' => 'hotel.contact.phone', 'label' => 'Téléphone (affiché)', 'type' => 'tel', 'rules' => 'nullable|string|max:40'],
                ['key' => 'hotel.contact.phone_href', 'label' => 'Téléphone (lien tel:)', 'type' => 'text', 'rules' => 'nullable|string|max:40'],
                ['key' => 'hotel.contact.whatsapp', 'label' => 'WhatsApp (affiché)', 'type' => 'tel', 'rules' => 'nullable|string|max:40'],
                ['key' => 'hotel.contact.whatsapp_href', 'label' => 'WhatsApp (numéro lien)', 'type' => 'text', 'rules' => 'nullable|string|max:40'],
                ['key' => 'hotel.contact.email', 'label' => 'E-mail de contact (boîte des messages)', 'type' => 'email', 'rules' => 'required|email|max:180'],
                ['key' => 'hotel.contact.reservations_email', 'label' => 'E-mail réservations', 'type' => 'email', 'rules' => 'nullable|email|max:180'],
            ],
        ],

        'horaires' => [
            'label' => 'Horaires',
            'icon' => 'schedule',
            'desc' => 'Horaires affichés sur la vitrine et dans l’app invité.',
            'fields' => [
                ['key' => 'hotel.hours.reception', 'label' => 'Réception', 'type' => 'text', 'rules' => 'nullable|string|max:120'],
                ['key' => 'hotel.hours.restaurant', 'label' => 'Restaurant', 'type' => 'text', 'rules' => 'nullable|string|max:120'],
                ['key' => 'hotel.hours.rooftop', 'label' => 'Rooftop', 'type' => 'text', 'rules' => 'nullable|string|max:120'],
            ],
        ],

        'reseaux' => [
            'label' => 'Réseaux sociaux',
            'icon' => 'share',
            'desc' => 'Liens des icônes réseaux sociaux du pied de page.',
            'fields' => [
                ['key' => 'hotel.social.facebook', 'label' => 'Facebook', 'type' => 'url', 'rules' => 'nullable|url|max:200'],
                ['key' => 'hotel.social.instagram', 'label' => 'Instagram', 'type' => 'url', 'rules' => 'nullable|url|max:200'],
                ['key' => 'hotel.social.linkedin', 'label' => 'LinkedIn', 'type' => 'url', 'rules' => 'nullable|url|max:200'],
            ],
        ],

        'acces' => [
            'label' => 'Carte & accès',
            'icon' => 'location_on',
            'desc' => 'Carte intégrée et lien d’itinéraire de la page Contact.',
            'fields' => [
                ['key' => 'hotel.geo.map_embed', 'label' => 'URL carte intégrée (iframe)', 'type' => 'url', 'rules' => 'nullable|url|max:400'],
                ['key' => 'hotel.geo.directions_url', 'label' => 'URL itinéraire', 'type' => 'url', 'rules' => 'nullable|url|max:400'],
            ],
        ],

        'reservation' => [
            'label' => 'Réservation',
            'icon' => 'event_available',
            'desc' => 'Acompte, taxes et durées appliqués au tunnel de réservation en ligne.',
            'fields' => [
                ['key' => 'payments.reservation_deposit_rate', 'label' => 'Taux d’acompte (0–1)', 'type' => 'number', 'step' => '0.01', 'cast' => 'float', 'rules' => 'required|numeric|min:0|max:1'],
                ['key' => 'booking.tourist_tax', 'label' => 'Taxe de séjour / pers. / nuit (FCFA)', 'type' => 'number', 'cast' => 'int', 'rules' => 'required|integer|min:0|max:100000'],
                ['key' => 'booking.tax_rate', 'label' => 'TVA affichée (0–1)', 'type' => 'number', 'step' => '0.01', 'cast' => 'float', 'rules' => 'required|numeric|min:0|max:1'],
                ['key' => 'booking.min_nights', 'label' => 'Séjour minimum (nuits)', 'type' => 'number', 'cast' => 'int', 'rules' => 'required|integer|min:1|max:60'],
                ['key' => 'booking.max_nights', 'label' => 'Séjour maximum (nuits)', 'type' => 'number', 'cast' => 'int', 'rules' => 'required|integer|min:1|max:365'],
                ['key' => 'booking.booking_window_months', 'label' => 'Fenêtre de réservation (mois)', 'type' => 'number', 'cast' => 'int', 'rules' => 'required|integer|min:1|max:36'],
            ],
        ],

        'restauration' => [
            'label' => 'Restauration',
            'icon' => 'restaurant',
            'desc' => 'TVA et frais de service appliqués aux commandes et à la caisse.',
            'fields' => [
                ['key' => 'menu.tax_rate', 'label' => 'TVA restauration (0–1)', 'type' => 'number', 'step' => '0.01', 'cast' => 'float', 'rules' => 'required|numeric|min:0|max:1'],
                ['key' => 'menu.service_charge_rate', 'label' => 'Frais de service (0–1)', 'type' => 'number', 'step' => '0.01', 'cast' => 'float', 'rules' => 'required|numeric|min:0|max:1'],
            ],
        ],

        'fidelite' => [
            'label' => 'Fidélité (barème)',
            'icon' => 'loyalty',
            'desc' => 'Règles de gain et de rachat des points du programme de fidélité.',
            'fields' => [
                ['key' => 'loyalty.program_name', 'label' => 'Nom du programme', 'type' => 'text', 'rules' => 'required|string|max:80'],
                ['key' => 'loyalty.base_earn_per_1000', 'label' => 'Points gagnés / 1000 FCFA', 'type' => 'number', 'cast' => 'int', 'rules' => 'required|integer|min:0|max:100'],
                ['key' => 'loyalty.point_value_fcfa', 'label' => 'Valeur d’un point (FCFA)', 'type' => 'number', 'cast' => 'int', 'rules' => 'required|integer|min:1|max:1000'],
                ['key' => 'loyalty.min_redeem_points', 'label' => 'Rachat minimum (points)', 'type' => 'number', 'cast' => 'int', 'rules' => 'required|integer|min:0|max:100000'],
            ],
        ],

        'wifi' => [
            'label' => 'Wi-Fi invité',
            'icon' => 'wifi',
            'desc' => 'Identifiants Wi-Fi affichés dans l’app invité.',
            'fields' => [
                ['key' => 'guestapp.wifi.ssid', 'label' => 'Nom du réseau (SSID)', 'type' => 'text', 'rules' => 'nullable|string|max:60'],
                ['key' => 'guestapp.wifi.password', 'label' => 'Mot de passe Wi-Fi', 'type' => 'text', 'rules' => 'nullable|string|max:60'],
            ],
        ],

        'seo' => [
            'label' => 'SEO & partage',
            'icon' => 'travel_explore',
            'desc' => 'Titres, description et image de partage par défaut (référencement + réseaux sociaux).',
            'fields' => [
                ['key' => 'seo.default_title', 'label' => 'Titre par défaut', 'type' => 'text', 'rules' => 'required|string|max:180'],
                ['key' => 'seo.title_suffix', 'label' => 'Suffixe de titre', 'type' => 'text', 'rules' => 'nullable|string|max:80'],
                ['key' => 'seo.default_description', 'label' => 'Description par défaut', 'type' => 'textarea', 'rules' => 'nullable|string|max:320'],
                // Accepte une URL absolue (https://…) OU un chemin local du site (/img/…).
                ['key' => 'seo.default_og_image', 'label' => 'Image de partage (URL ou chemin /img/…)', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:400', 'regex:#^(https?://|/)#']],
                ['key' => 'seo.twitter', 'label' => 'Compte X / Twitter', 'type' => 'text', 'rules' => 'nullable|string|max:40'],
                ['key' => 'seo.google_analytics_id', 'label' => 'ID Google Analytics', 'type' => 'text', 'rules' => 'nullable|string|max:40'],
            ],
        ],

        'emails' => [
            'label' => 'E-mails sortants',
            'icon' => 'mail',
            'desc' => 'Adresse et nom d’expéditeur des e-mails transactionnels (confirmations, reçus…).',
            'fields' => [
                ['key' => 'mail.from.address', 'label' => 'Adresse expéditrice', 'type' => 'email', 'rules' => 'required|email|max:180'],
                ['key' => 'mail.from.name', 'label' => 'Nom expéditeur', 'type' => 'text', 'rules' => 'required|string|max:120'],
            ],
        ],

        'rh' => [
            'label' => 'RH & Paie',
            'icon' => 'badge',
            'desc' => 'Congés, préavis, alertes RH et coefficients de paie.',
            'fields' => [
                ['key' => 'hr.leave_accrual_per_month', 'label' => 'Congés acquis / mois (jours)', 'type' => 'number', 'cast' => 'float', 'rules' => 'required|numeric|min:0|max:5', 'step' => '0.5'],
                ['key' => 'hr.notice_period_days', 'label' => 'Préavis par défaut (jours)', 'type' => 'number', 'cast' => 'int', 'rules' => 'required|integer|min:0|max:180'],
                ['key' => 'hr.alerts.trial_lead_days', 'label' => 'Alerte fin de période d’essai (jours avant)', 'type' => 'number', 'cast' => 'int', 'rules' => 'required|integer|min:1|max:90'],
                ['key' => 'hr.alerts.contract_lead_days', 'label' => 'Alerte fin de CDD (jours avant)', 'type' => 'number', 'cast' => 'int', 'rules' => 'required|integer|min:1|max:180'],
                ['key' => 'hr.alerts.document_lead_days', 'label' => 'Alerte pièce / visite médicale (jours avant)', 'type' => 'number', 'cast' => 'int', 'rules' => 'required|integer|min:1|max:180'],
                ['key' => 'payroll.working_days_per_month', 'label' => 'Jours ouvrés / mois (paie)', 'type' => 'number', 'cast' => 'int', 'rules' => 'required|integer|min:20|max:31'],
                ['key' => 'payroll.overtime_multiplier', 'label' => 'Majoration heures supplémentaires', 'type' => 'number', 'cast' => 'float', 'rules' => 'required|numeric|min:1|max:3', 'step' => '0.05'],
                ['key' => 'payroll.night_multiplier', 'label' => 'Majoration heures de nuit', 'type' => 'number', 'cast' => 'float', 'rules' => 'required|numeric|min:1|max:3', 'step' => '0.05'],
                ['key' => 'payroll.sunday_multiplier', 'label' => 'Majoration dimanche', 'type' => 'number', 'cast' => 'float', 'rules' => 'required|numeric|min:1|max:3', 'step' => '0.05'],
                ['key' => 'payroll.holiday_multiplier', 'label' => 'Majoration jour férié', 'type' => 'number', 'cast' => 'float', 'rules' => 'required|numeric|min:1|max:3', 'step' => '0.05'],
                ['key' => 'payroll.seniority.enabled', 'label' => 'Prime d’ancienneté active', 'type' => 'boolean', 'cast' => 'bool', 'rules' => 'boolean'],
                ['key' => 'payroll.seniority.start_years', 'label' => 'Ancienneté — départ (années)', 'type' => 'number', 'cast' => 'int', 'rules' => 'required|integer|min:1|max:10'],
                ['key' => 'payroll.seniority.start_percent', 'label' => 'Ancienneté — taux de départ (%)', 'type' => 'number', 'cast' => 'float', 'rules' => 'required|numeric|min:0|max:25', 'step' => '0.5'],
                ['key' => 'payroll.seniority.step_percent', 'label' => 'Ancienneté — pas par an (%)', 'type' => 'number', 'cast' => 'float', 'rules' => 'required|numeric|min:0|max:10', 'step' => '0.5'],
                ['key' => 'payroll.seniority.cap_percent', 'label' => 'Ancienneté — plafond (%)', 'type' => 'number', 'cast' => 'float', 'rules' => 'required|numeric|min:0|max:50', 'step' => '1'],
                ['key' => 'payroll.thirteenth_month.enabled', 'label' => 'Prime de fin d’année (13e mois) active', 'type' => 'boolean', 'cast' => 'bool', 'rules' => 'boolean'],
                ['key' => 'payroll.thirteenth_month.fraction', 'label' => '13e mois — fraction du salaire de base', 'type' => 'number', 'cast' => 'float', 'rules' => 'required|numeric|min:0|max:2', 'step' => '0.05'],
            ],
        ],

        'caisse' => [
            'label' => 'Caisse (POS)',
            'icon' => 'point_of_sale',
            'desc' => 'Seuils de remise avant PIN manager, TVA & service, seuils d’alerte, et mentions imprimées sur les tickets et factures.',
            'fields' => [
                ['key' => 'pos.discount.max_percent', 'label' => 'Remise % max sans PIN', 'type' => 'number', 'cast' => 'int', 'rules' => 'required|integer|min:0|max:100'],
                ['key' => 'pos.discount.max_amount', 'label' => 'Remise FCFA max sans PIN', 'type' => 'number', 'cast' => 'int', 'rules' => 'required|integer|min:0'],
                ['key' => 'menu.tax_rate', 'label' => 'Taux de TVA restauration', 'type' => 'number', 'cast' => 'float', 'rules' => 'required|numeric|min:0|max:1', 'step' => '0.01'],
                ['key' => 'menu.service_charge_rate', 'label' => 'Taux de service', 'type' => 'number', 'cast' => 'float', 'rules' => 'required|numeric|min:0|max:1', 'step' => '0.01'],
                ['key' => 'pos.alerts.kitchen_late_minutes', 'label' => 'Alerte commande cuisine en retard (min)', 'type' => 'number', 'cast' => 'int', 'rules' => 'required|integer|min:5|max:120'],
                ['key' => 'pos.alerts.cancel_rate_threshold', 'label' => 'Alerte taux d’annulation (fraction)', 'type' => 'number', 'cast' => 'float', 'rules' => 'required|numeric|min:0|max:1', 'step' => '0.01'],
                ['key' => 'pos.ticket_footer', 'label' => 'Bas de ticket', 'type' => 'textarea', 'rules' => 'nullable|string|max:300'],
                ['key' => 'pos.invoice_legal', 'label' => 'Mentions légales (facture A4)', 'type' => 'textarea', 'rules' => 'nullable|string|max:400'],
            ],
        ],
    ],
];
