<?php

/*
|--------------------------------------------------------------------------
| Écran d'accueil (Splash Screen) de la vitrine
|--------------------------------------------------------------------------
| Introduction premium jouée une seule fois par session de navigation.
| Ces valeurs sont surchargeables depuis le back-office
| (Paramètres → Écran d'accueil) : elles vivent alors dans `site_settings`
| sous des clés préfixées « splash. » et sont relues par App\Support\Splash.
*/

return [

    // Interrupteur général.
    'enabled' => true,

    // Chorégraphie : cinematic (complète) · fade (sobre) · zoom · minimal (logo seul).
    'animation' => 'cinematic',

    // Dégradé de fond plein écran (sombre → teinte chaude discrète).
    'background_from' => '#1b2023',
    'background_to' => '#3a2a22',

    // Halo lumineux lent en arrière-plan (désactivé d'office sur mobile).
    'glow' => true,

    // Logo : null → public/img/logo-hrk.svg ; sinon chemin sur le disque « public ».
    'logo_path' => null,

    // Textes.
    'welcome_text' => 'Bienvenue',
    'hotel_name' => null,               // null → config('hotel.name')
    'signature' => 'Votre confort, notre priorité',

    // Durée d'affichage avant la transition de sortie (ms). Borné 1000–6000.
    'duration_ms' => 2600,
];
