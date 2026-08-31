<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        $seo = config('seo');
        // yieldContent() renvoie une valeur déjà passée par e() quand la section
        // est définie en ligne (@section('title', '...')). On normalise donc tout
        // en HTML échappé et on affiche via {!! !!}.
        $pageTitle = trim($__env->yieldContent('title'));
        $title = $pageTitle ? $pageTitle . e($seo['title_suffix']) : e($seo['default_title']);
        $description = trim($__env->yieldContent('description')) ?: e($seo['default_description']);
        // Une image OG doit être une URL absolue : on préfixe les chemins locaux.
        $absUrl = fn (string $src) => \Illuminate\Support\Str::startsWith($src, ['http://', 'https://']) ? $src : url($src);
        $rawOgImage = trim($__env->yieldContent('og_image')) ?: $seo['default_og_image'];
        $ogImage = e($absUrl($rawOgImage));
        $canonical = e(url()->current());
    @endphp

    <title>{!! $title !!}</title>
    <meta name="description" content="{!! $description !!}">
    <link rel="canonical" href="{!! $canonical !!}">
    <meta name="theme-color" content="#de6443">
    <meta name="robots" content="index,follow,max-image-preview:large">

    {{-- Open Graph / Twitter --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('hotel.name') }}">
    <meta property="og:locale" content="{{ $seo['locale'] }}">
    <meta property="og:title" content="{!! $title !!}">
    <meta property="og:description" content="{!! $description !!}">
    <meta property="og:url" content="{!! $canonical !!}">
    <meta property="og:image" content="{!! $ogImage !!}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="{{ $seo['twitter'] }}">

    {{-- Perf : préconnexion aux origines tierces --}}
    <link rel="preconnect" href="https://images.unsplash.com">
    <link rel="dns-prefetch" href="https://images.unsplash.com">

    @include('partials.fonts')

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    @include('partials.material-symbols')
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Données structurées : établissement --}}
    @php
        $amenities = ['Wi-Fi gratuit', 'Piscine extérieure', 'Restaurant', 'Rooftop bar', 'Salle de fitness', 'Salles de séminaire', 'Parking', 'Climatisation'];
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Hotel',
            'name' => config('hotel.name'),
            'description' => $seo['default_description'],
            'url' => url('/'),
            'telephone' => config('hotel.contact.phone'),
            'email' => config('hotel.contact.email'),
            'image' => $absUrl($seo['default_og_image']),
            'priceRange' => 'XOF',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => config('hotel.contact.address_line'),
                'addressLocality' => config('hotel.city'),
                'addressRegion' => 'Thiès',
                'addressCountry' => 'SN',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => config('hotel.geo.lat'),
                'longitude' => config('hotel.geo.lng'),
            ],
            'starRating' => ['@type' => 'Rating', 'ratingValue' => '4'],
            'amenityFeature' => array_map(
                fn ($a) => ['@type' => 'LocationFeatureSpecification', 'name' => $a, 'value' => true],
                $amenities
            ),
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

    @stack('head')
</head>
<body data-surface="site" class="min-h-screen bg-sable-50 text-ink antialiased">
    @include('partials.splash')

    <a href="#contenu" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-lg focus:bg-nuit-900 focus:px-4 focus:py-2 focus:text-white">
        Aller au contenu
    </a>

    @include('partials.header')

    <main id="contenu">
        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="mx-auto mt-4 max-w-3xl px-5">
                <div class="flex items-start gap-3 rounded-xl border border-laiton-200 bg-laiton-50 px-4 py-3 text-sm text-nuit-800">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-laiton-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10A8 8 0 1 1 2 10a8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a1 1 0 0 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2v-3a1 1 0 0 0-1-1H9Z" clip-rule="evenodd"/></svg>
                    <p>{{ session('status') }}</p>
                    <button type="button" x-on:click="show = false" class="ml-auto text-nuit-400 hover:text-nuit-800" aria-label="Fermer">&times;</button>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    @include('partials.footer')

    {{-- Retour en haut --}}
    <button
        x-data="{ show: false }"
        x-on:scroll.window="show = window.scrollY > 700"
        x-show="show" x-transition x-cloak
        x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })"
        class="fixed bottom-5 right-5 z-40 flex h-11 w-11 items-center justify-center rounded-full bg-nuit-900 text-white hover:bg-terracotta-500"
        aria-label="Revenir en haut de page"
    >
        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 16V4m0 0-5 5m5-5 5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>

    <style>[x-cloak]{display:none!important}</style>
    @stack('scripts')
</body>
</html>
