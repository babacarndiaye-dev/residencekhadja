<?php

/*
|--------------------------------------------------------------------------
| Navigation principale
|--------------------------------------------------------------------------
| 'label' passe par le catalogue de traduction (lang/fr/site.php) : la
| structure est donc prête pour EN / Wolof / Arabe sans modifier les vues.
*/

return [
    'primary' => [
        ['route' => 'about',        'label' => 'site.nav.about'],
        ['route' => 'rooms.index',  'label' => 'site.nav.rooms'],
        ['route' => 'restaurant',   'label' => 'site.nav.restaurant'],
        ['route' => 'spaces',       'label' => 'site.nav.spaces'],
        ['route' => 'events',       'label' => 'site.nav.events'],
        ['route' => 'offers.index', 'label' => 'site.nav.offers'],
        ['route' => 'gallery',      'label' => 'site.nav.gallery'],
        ['route' => 'contact',      'label' => 'site.nav.contact'],
    ],
];
