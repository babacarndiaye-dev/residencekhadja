<?php

/*
|--------------------------------------------------------------------------
| Marketing — segments, campagnes e-mail / SMS, codes promo (§19–20, §55)
|--------------------------------------------------------------------------
| E-mail : Mailable App\Mail\CampaignMessage mis en file (transport = MAIL_MAILER).
| SMS : App\Services\Sms — passerelle configurée dans config/sms.php.
*/

return [

    'channels' => [
        'email' => 'E-mail',
        'sms' => 'SMS',
    ],

    'sender' => [
        'email_from' => env('MARKETING_FROM_ADDRESS', 'ne-pas-repondre@residence-khadija.sn'),
        'email_name' => env('MARKETING_FROM_NAME', 'Hôtel Résidence Khadija'),
        // L'expéditeur SMS est défini dans config/sms.php (`from`).
    ],

    /*
    | Catalogue des règles de segmentation. Chaque règle = une clé dans la
    | "definition" JSON d'un segment ; App\Services\Segmentation les applique.
    */
    'segment_rules' => [
        'opted_in' => 'Consentement marketing accordé',
        'min_stays' => 'Nombre de séjours ≥',
        'min_spend' => 'Dépenses cumulées (FCFA) ≥',
        'country' => 'Pays de résidence',
        'tier' => 'Palier de fidélité',
        'has_tag' => 'Possède l’étiquette',
        'never_stayed' => 'Aucun séjour honoré',
        'inactive_days' => 'Sans séjour depuis (jours) ≥',
        'birthday_month' => 'Anniversaire au mois (1–12, ou « courant »)',
    ],

    'templates' => [
        [
            'name' => 'Offre anniversaire',
            'channel' => 'email',
            'subject' => 'Un cadeau pour votre anniversaire 🎁',
            'body' => "Bonjour {prenom},\n\nToute l'équipe de l'Hôtel Résidence Khadija vous souhaite un très joyeux anniversaire !\n"
                ."Profitez de -15 % sur votre prochaine nuitée avec le code {code}.\n\nÀ très bientôt à Thiès.",
        ],
        [
            'name' => 'Nous revenir (win-back)',
            'channel' => 'email',
            'subject' => 'Votre chambre vous attend à Thiès',
            'body' => "Bonjour {prenom},\n\nCela fait un moment ! Revenez profiter de la Teranga : -12 % avec le code {code}.",
        ],
        [
            'name' => 'SMS confirmation offre',
            'channel' => 'sms',
            'subject' => null,
            'body' => 'Residence Khadija : {prenom}, -10% sur votre prochain sejour avec le code {code}. Reservation directe.',
        ],
    ],
];
