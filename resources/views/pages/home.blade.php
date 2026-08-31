@extends('layouts.app')

@section('title', 'Hôtel premium à Thiès, Sénégal')
@section('description', "Hôtel Résidence Khadija : chambres et suites élégantes, restaurant « Saveurs du Sénégal », rooftop panoramique, piscine et salles de séminaire au cœur de Thiès. Réservez au meilleur tarif direct.")

@php
    $hotel = config('hotel');

    $heroSlides = [
        ['img' => 'img/hotel/facade.jpg',          'alt' => "Façade de l'Hôtel Résidence Khadija à Thiès", 'label' => 'Bienvenue à Thiès'],
        ['img' => 'img/hotel/piscine.jpg',         'alt' => 'Piscine extérieure et transats',              'label' => 'Piscine & rooftop'],
        ['img' => 'img/hotel/suite-executive.jpg', 'alt' => 'Suite Exécutive, chambre et coin salon',       'label' => 'Suites signature'],
    ];

    $amenities = [
        ['icon' => 'wifi', 'label' => 'Wi-Fi fibre gratuit'],
        ['icon' => 'pool', 'label' => 'Piscine extérieure'],
        ['icon' => 'restaurant', 'label' => 'Restaurant Teranga'],
        ['icon' => 'deck', 'label' => 'Rooftop panoramique'],
        ['icon' => 'fitness_center', 'label' => 'Salle de fitness'],
        ['icon' => 'spa', 'label' => 'Spa & soins'],
        ['icon' => 'local_parking', 'label' => 'Parking privé'],
        ['icon' => 'bakery_dining', 'label' => 'Petit-déjeuner'],
        ['icon' => 'meeting_room', 'label' => 'Salles de séminaire'],
        ['icon' => 'concierge', 'label' => 'Conciergerie 24/7'],
    ];

    $discoverSlides = [
        ['img' => 'img/hotel/piscine-2.jpg', 'small' => 'Détente', 'title' => 'Piscine & terrasse',        'text' => "Bassin bordé de transats et de cabanas, service au bord de l'eau, du lever du jour au coucher du soleil.", 'href' => route('spaces'), 'cta' => 'Voir les espaces'],
        ['img' => 'img/hotel/salle-seminaire.jpg', 'small' => 'Affaires', 'title' => 'Salles de séminaire', 'text' => "Jusqu'à 250 participants, trois salles modulables, régie technique et restauration sur mesure.", 'href' => route('events'), 'cta' => 'Organiser un événement'],
        ['img' => 'img/hotel/suite-spa-bain.jpg', 'small' => 'Bien-être', 'title' => 'Spa & rituels',       'text' => 'Cabines de massage, hammam et rituels au karité et au baobab inspirés des traditions sénégalaises.', 'href' => route('spaces'), 'cta' => 'Découvrir le spa'],
    ];

    $reviewPages = collect($reviews['items'])->chunk(2)->values();
@endphp

@push('head')
    <link rel="preload" as="image" fetchpriority="high" href="{{ asset('img/hotel/facade.jpg') }}">
@endpush

@section('content')

    {{-- ============================ HERO — slider plein écran ============================ --}}
    <section
        class="relative isolate flex min-h-[92vh] items-center overflow-hidden bg-nuit-900"
        x-data="rkCarousel({ count: {{ count($heroSlides) }}, interval: 6000, labels: @js(array_column($heroSlides, 'label')) })"
        x-on:mouseenter="stop()" x-on:mouseleave="interval && play()"
    >
        @foreach ($heroSlides as $i => $s)
            <div class="absolute inset-0 -z-10 transition-opacity duration-1000 ease-out"
                 :class="active === {{ $i }} ? 'opacity-100' : 'opacity-0'">
                <img src="{{ asset($s['img']) }}" alt="{{ $s['alt'] }}"
                     width="1200" height="900" decoding="async"
                     @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif
                     class="h-full w-full object-cover"
                     :class="active === {{ $i }} ? 'kenburns' : ''">
            </div>
        @endforeach
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-nuit-900 via-nuit-900/60 to-nuit-900/25"></div>

        <x-container class="flex w-full flex-col gap-9 py-28">
            <div class="max-w-2xl">
                <span class="eyebrow flex items-center gap-3 text-terracotta-300">
                    <span class="inline-block h-px w-8 bg-terracotta-400"></span>
                    <span x-text="labels[active] || labels[0]">{{ $heroSlides[0]['label'] }}</span>
                </span>
                <h1 class="mt-5 text-4xl leading-[1.05] text-white sm:text-5xl lg:text-6xl">
                    {!! nl2br(e(config('vitrine.hero_title'))) !!}
                </h1>
                <p class="mt-5 max-w-xl text-lg leading-relaxed text-white/80">
                    {{ config('vitrine.hero_subtitle') }}
                </p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <x-button :href="route('rooms.index')" variant="light" size="lg">{{ config('vitrine.hero_cta_primary') }}</x-button>
                    <x-button href="#offres" variant="ghost" size="lg" class="text-white hover:text-terracotta-200">
                        Nos offres
                    </x-button>
                </div>
            </div>

            <div class="reveal"><x-booking-widget tone="light" /></div>
        </x-container>

        {{-- Contrôles précédent / suivant --}}
        <div class="absolute bottom-6 right-4 z-10 hidden gap-2 sm:right-8 sm:flex">
            <button type="button" x-on:click="prev()" aria-label="Diapositive précédente"
                    class="grid h-11 w-11 place-items-center bg-white/10 text-white ring-1 ring-white/30 backdrop-blur transition-colors hover:bg-terracotta-500 hover:ring-terracotta-500">
                <x-icon name="chevron_left" />
            </button>
            <button type="button" x-on:click="next()" aria-label="Diapositive suivante"
                    class="grid h-11 w-11 place-items-center bg-white/10 text-white ring-1 ring-white/30 backdrop-blur transition-colors hover:bg-terracotta-500 hover:ring-terracotta-500">
                <x-icon name="chevron_right" />
            </button>
        </div>

        {{-- Puces --}}
        <div class="absolute bottom-8 left-1/2 z-10 flex -translate-x-1/2 gap-2">
            <template x-for="i in pages" :key="i">
                <button type="button" x-on:click="go(i - 1)" :aria-label="'Diapositive ' + i"
                        class="h-1.5 rounded-full transition-all duration-300"
                        :class="active === i - 1 ? 'w-7 bg-terracotta-400' : 'w-3 bg-white/40 hover:bg-white/70'"></button>
            </template>
        </div>
    </section>

    {{-- ===================== OFFRES EXCLUSIVES ===================== --}}
    <section id="offres" class="py-20 sm:py-28">
        <x-container>
            <div class="reveal mx-auto mb-14 text-center">
                <x-section-heading eyebrow="Offres exclusives" title="Réservez en direct, au meilleur tarif" align="center" class="mx-auto text-center">
                    Des séjours packagés pour chaque façon de voyager. Sans intermédiaire, sans surprise.
                </x-section-heading>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($offers as $i => $offer)
                    <div class="reveal reveal--left" style="--rd: {{ $i * 0.15 }}s"><x-offer-card :offer="$offer" /></div>
                @endforeach
            </div>

            <div class="reveal mt-10 text-center">
                <x-button :href="route('offers.index')" variant="ghost">Toutes les offres &rarr;</x-button>
            </div>
        </x-container>
    </section>

    {{-- ========================= À PROPOS ========================= --}}
    <section class="bg-sable-100 py-20 sm:py-28">
        <x-container class="grid items-center gap-14 lg:grid-cols-2">
            <div class="reveal reveal--left relative">
                <div class="deco-shape -left-4 -top-4 h-28 w-28"></div>
                <div class="deco-shape -bottom-5 -right-3 h-20 w-20" style="animation-delay: -3s"></div>
                <div class="hvr-zoom relative overflow-hidden rounded-2xl shadow-card">
                    <img src="{{ asset('img/hotel/lobby.jpg') }}" alt="Lobby et salon d'accueil de l'Hôtel Résidence Khadija"
                         width="1200" height="800" loading="lazy" decoding="async"
                         class="aspect-[4/3] w-full object-cover">
                </div>
            </div>

            <div class="reveal reveal--right flex flex-col gap-6">
                <x-section-heading eyebrow="L'hôtel" title="Une adresse pensée pour le voyage d'affaires et l'art de vivre">
                    {{ $hotel['intro'] }}
                </x-section-heading>
                <div>
                    <x-button :href="route('about')" variant="outline">En savoir plus sur l'hôtel</x-button>
                </div>
            </div>
        </x-container>
    </section>

    {{-- ===================== CHIFFRES (compteurs) ===================== --}}
    <section class="py-16 sm:py-20">
        <x-container>
            <div class="reveal grid grid-cols-2 gap-8 sm:grid-cols-4">
                @foreach ($hotel['stats'] as $stat)
                    <div class="text-center">
                        <div class="font-display text-4xl font-semibold text-terracotta-600 sm:text-5xl">
                            @if (is_numeric($stat['value']))
                                <span data-count="{{ $stat['value'] }}">0</span>
                            @else
                                {{ $stat['value'] }}
                            @endif
                        </div>
                        <div class="mt-2 text-xs uppercase tracking-wider text-nuit-500">{{ $stat['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </x-container>
    </section>

    {{-- ======================== CHAMBRES & SUITES ========================== --}}
    <section class="bg-nuit-900 py-20 text-white sm:py-28">
        <x-container>
            <div class="reveal mx-auto mb-14 text-center">
                <x-section-heading eyebrow="Découvrir" title="Chambres & Suites" tone="light" align="center" class="mx-auto text-center">
                    Cinq catégories, des matières naturelles et une literie d'exception. Chaque chambre est
                    équipée pour travailler et pensée pour se reposer.
                </x-section-heading>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($rooms as $i => $room)
                    <div class="reveal reveal--up" style="--rd: {{ $i * 0.15 }}s"><x-room-card :room="$room" /></div>
                @endforeach
            </div>

            <div class="reveal mt-10 text-center">
                <x-button :href="route('rooms.index')" variant="light">Toutes les chambres &rarr;</x-button>
            </div>
        </x-container>
    </section>

    {{-- ================= DÉCOUVRIR — carrousel pleine largeur ================= --}}
    <section class="py-20 sm:py-28"
             x-data="rkCarousel({ count: {{ count($discoverSlides) }}, interval: 7000 })"
             x-on:mouseenter="stop()" x-on:mouseleave="interval && play()">
        <x-container>
            <div class="reveal overflow-hidden rounded-3xl">
                <div class="rk-track" :style="trackStyle()">
                    @foreach ($discoverSlides as $s)
                        <div class="relative w-full shrink-0">
                            <img src="{{ asset($s['img']) }}" alt="{{ $s['title'] }}"
                                 width="1400" height="760" loading="lazy" decoding="async"
                                 class="h-[520px] w-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-r from-nuit-900/85 via-nuit-900/45 to-transparent"></div>
                            <div class="absolute inset-0 flex items-center">
                                <div class="max-w-lg px-8 text-white sm:px-14">
                                    <span class="eyebrow text-terracotta-300">{{ $s['small'] }}</span>
                                    <h3 class="mt-3 text-3xl text-white sm:text-4xl">{{ $s['title'] }}</h3>
                                    <p class="mt-4 text-white/80">{{ $s['text'] }}</p>
                                    <div class="mt-6">
                                        <x-button :href="$s['href']" variant="light" size="sm">{{ $s['cta'] }}</x-button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="reveal mt-6 flex items-center justify-center gap-3">
                <button type="button" x-on:click="prev()" aria-label="Précédent"
                        class="grid h-11 w-11 place-items-center border border-sable-300 text-nuit-700 transition-colors hover:border-terracotta-400 hover:text-terracotta-600">
                    <x-icon name="chevron_left" />
                </button>
                <template x-for="i in pages" :key="i">
                    <button type="button" x-on:click="go(i - 1)" :aria-label="'Diapositive ' + i"
                            class="h-1.5 rounded-full transition-all duration-300"
                            :class="active === i - 1 ? 'w-7 bg-terracotta-500' : 'w-3 bg-sable-300 hover:bg-nuit-300'"></button>
                </template>
                <button type="button" x-on:click="next()" aria-label="Suivant"
                        class="grid h-11 w-11 place-items-center border border-sable-300 text-nuit-700 transition-colors hover:border-terracotta-400 hover:text-terracotta-600">
                    <x-icon name="chevron_right" />
                </button>
            </div>
        </x-container>
    </section>

    {{-- ======================== ÉQUIPEMENTS ======================== --}}
    <section class="bg-sable-100 py-20 sm:py-28">
        <x-container>
            <div class="reveal mx-auto mb-14 text-center">
                <x-section-heading eyebrow="Services" title="Nos meilleurs équipements" align="center" class="mx-auto text-center">
                    Tout ce qu'il faut pour un séjour sans friction — des essentiels du quotidien aux
                    attentions qui font la différence.
                </x-section-heading>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ($amenities as $i => $a)
                    <div class="reveal reveal--up flex flex-col items-center gap-3 rounded-2xl bg-white p-6 text-center shadow-card"
                         style="--rd: {{ ($i % 5) * 0.08 }}s">
                        <span class="grid h-14 w-14 place-items-center rounded-full bg-terracotta-50 text-terracotta-600">
                            <x-icon :name="$a['icon']" :size="26" />
                        </span>
                        <p class="text-sm font-semibold text-nuit-800">{{ $a['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </x-container>
    </section>

    {{-- ========================== AVIS — carrousel ========================== --}}
    <section class="py-20 sm:py-28"
             x-data="rkCarousel({ count: {{ max(1, $reviewPages->count()) }}, interval: 8000 })"
             x-on:mouseenter="stop()" x-on:mouseleave="interval && play()">
        <x-container class="grid gap-10 lg:grid-cols-[0.9fr_1.4fr] lg:items-center">
            <div class="reveal flex flex-col gap-5">
                <x-section-heading eyebrow="Avis clients" title="Ils ont séjourné à la Résidence Khadija" />
                <div class="flex items-center gap-2 text-sm text-nuit-600">
                    <span class="flex items-center gap-0.5 text-laiton-500">
                        @for ($i = 0; $i < 5; $i++)
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="m10 1.5 2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 15l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9L10 1.5Z"/></svg>
                        @endfor
                    </span>
                    <span><strong class="text-nuit-900">{{ $reviews['summary']['rating'] }}</strong> / 5 · {{ $reviews['summary']['count'] }} {{ $reviews['summary']['source'] }}</span>
                </div>
                <div class="flex gap-2">
                    <button type="button" x-on:click="prev()" aria-label="Avis précédent"
                            class="grid h-11 w-11 place-items-center border border-sable-300 text-nuit-700 transition-colors hover:border-terracotta-400 hover:text-terracotta-600">
                        <x-icon name="chevron_left" />
                    </button>
                    <button type="button" x-on:click="next()" aria-label="Avis suivant"
                            class="grid h-11 w-11 place-items-center border border-sable-300 text-nuit-700 transition-colors hover:border-terracotta-400 hover:text-terracotta-600">
                        <x-icon name="chevron_right" />
                    </button>
                </div>
            </div>

            <div class="reveal overflow-hidden">
                <div class="rk-track" :style="trackStyle()">
                    @foreach ($reviewPages as $page)
                        <div class="w-full shrink-0">
                            <div class="grid gap-6 md:grid-cols-2">
                                @foreach ($page as $review)
                                    <figure class="flex h-full flex-col gap-4 rounded-2xl bg-white p-6 shadow-card">
                                        <x-icon name="format_quote" :size="34" class="text-terracotta-300" />
                                        <div class="flex gap-0.5 text-laiton-500">
                                            @for ($i = 0; $i < $review['rating']; $i++)
                                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="m10 1.5 2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 15l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9L10 1.5Z"/></svg>
                                            @endfor
                                        </div>
                                        <blockquote class="flex-1 text-sm leading-relaxed text-ink-soft">« {{ $review['text'] }} »</blockquote>
                                        <figcaption class="text-sm">
                                            <span class="font-semibold text-nuit-900">{{ $review['name'] }}</span><br>
                                            <span class="text-nuit-400">{{ $review['origin'] }} · {{ $review['trip'] }}</span>
                                        </figcaption>
                                    </figure>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-container>
    </section>

    {{-- ==================== SÉMINAIRES (bande parallaxe) ==================== --}}
    <section class="relative isolate overflow-hidden py-28">
        <img src="{{ asset('img/hotel/salle-reunion.jpg') }}" alt=""
             width="1200" height="800" loading="lazy" decoding="async"
             class="absolute inset-0 -z-10 h-full w-full object-cover">
        <div class="absolute inset-0 -z-10 bg-nuit-900/78"></div>
        <x-container class="reveal reveal--left flex max-w-2xl flex-col gap-5 text-white">
            <span class="eyebrow text-terracotta-300">Séminaires &amp; événements</span>
            <h2 class="text-3xl text-white sm:text-4xl">Jusqu'à 250 participants, trois salles modulables et une équipe dédiée</h2>
            <p class="text-white/75">
                Journées d'étude, conférences, dîners de gala ou cérémonies familiales : nous concevons
                votre événement de A à Z, restauration et régie technique comprises.
            </p>
            <div class="flex flex-wrap gap-3">
                <x-button :href="route('events')" variant="light">Organiser un événement</x-button>
                <x-button :href="route('events').'#devis'" variant="ghost" class="text-white hover:text-terracotta-200">Demander un devis</x-button>
            </div>
        </x-container>
    </section>

    {{-- ====================== EXPÉRIENCES ======================= --}}
    <section class="py-20 sm:py-28">
        <x-container>
            <div class="reveal mx-auto mb-14 text-center">
                <x-section-heading eyebrow="Teranga" title="Des expériences pour vivre Thiès autrement" align="center" class="mx-auto text-center">
                    {{ config('experiences.teranga_pitch') }}
                </x-section-heading>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                @foreach ($experiences->take(3) as $i => $xp)
                    <article class="reveal reveal--left overflow-hidden rounded-2xl bg-white shadow-card" style="--rd: {{ $i * 0.15 }}s">
                        <div class="hvr-zoom aspect-[16/10] overflow-hidden">
                            <img src="{{ $xp['image'] }}" alt="{{ $xp['name'] }}" width="800" height="500"
                                 loading="lazy" decoding="async" class="h-full w-full object-cover">
                        </div>
                        <div class="flex flex-col gap-2 p-6">
                            <h3 class="text-xl text-nuit-900">{{ $xp['name'] }}</h3>
                            <p class="text-sm leading-relaxed text-ink-soft">{{ $xp['desc'] }}</p>
                            <a href="{{ route('experiences') }}" class="mt-1 inline-flex items-center gap-1 text-sm font-semibold text-terracotta-600 hover:text-terracotta-700">
                                En savoir plus <x-icon name="arrow_forward" :size="16" />
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </x-container>
    </section>

    {{-- ============================= CTA ============================= --}}
    <section class="relative isolate overflow-hidden py-24 text-center">
        <img src="{{ asset('img/hotel/piscine-3.jpg') }}" alt=""
             width="1200" height="800" loading="lazy" decoding="async"
             class="absolute inset-0 -z-10 h-full w-full object-cover"
             data-parallax="0.05" style="transform: translateY(var(--py, 0)) scale(1.1)">
        <div class="absolute inset-0 -z-10 bg-nuit-900/72"></div>
        <x-container class="reveal flex flex-col items-center gap-6">
            <span class="eyebrow text-terracotta-300">Réservation directe</span>
            <h2 class="max-w-2xl text-3xl text-white sm:text-4xl lg:text-[2.75rem]">{{ config('vitrine.cta_title') }}</h2>
            <p class="max-w-xl text-white/75">{{ config('vitrine.cta_text') }}</p>
            <div class="flex flex-wrap justify-center gap-3">
                <x-button :href="route('booking.start')" variant="primary" size="lg">Vérifier les disponibilités</x-button>
                <x-button :href="'https://wa.me/'.$hotel['contact']['whatsapp_href']" variant="ghost" size="lg" class="text-white hover:text-terracotta-200">
                    Nous joindre sur WhatsApp
                </x-button>
            </div>
        </x-container>
    </section>

    {{-- ========================= GALERIE ========================= --}}
    <section class="pb-4">
        <x-container>
            <div class="reveal mb-8 flex flex-wrap items-end justify-between gap-4">
                <x-section-heading eyebrow="Galerie" title="Un aperçu de la maison" />
                <x-button :href="route('gallery')" variant="ghost">Toute la galerie &rarr;</x-button>
            </div>
        </x-container>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            @foreach ($gallery as $photo)
                <div class="hvr-zoom aspect-square overflow-hidden">
                    <img src="{{ $photo['src'] }}" alt="{{ $photo['alt'] }}" width="500" height="500"
                         loading="lazy" decoding="async" class="h-full w-full object-cover">
                </div>
            @endforeach
        </div>
    </section>

    {{-- ===================== CONTACT / ACCÈS ====================== --}}
    <section class="py-20 sm:py-28">
        <x-container class="grid gap-10 lg:grid-cols-2 lg:items-center">
            <div class="reveal reveal--left flex flex-col gap-5">
                <x-section-heading eyebrow="Contact & accès" title="Venez comme vous êtes, repartez avec la Teranga">
                    Réception ouverte 24h/24. Notre conciergerie organise vos transferts, vos excursions
                    et vos réservations au restaurant.
                </x-section-heading>
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-nuit-400">Adresse</dt>
                        <dd class="mt-1 text-sm text-nuit-800">{{ $hotel['contact']['address_line'] }}<br>{{ $hotel['contact']['address_area'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-nuit-400">Téléphone</dt>
                        <dd class="mt-1 text-sm text-nuit-800"><a href="tel:{{ $hotel['contact']['phone_href'] }}" class="hover:text-terracotta-600">{{ $hotel['contact']['phone'] }}</a></dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-nuit-400">E-mail</dt>
                        <dd class="mt-1 text-sm text-nuit-800"><a href="mailto:{{ $hotel['contact']['email'] }}" class="hover:text-terracotta-600">{{ $hotel['contact']['email'] }}</a></dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-nuit-400">Horaires</dt>
                        <dd class="mt-1 text-sm text-nuit-800">{{ $hotel['hours']['restaurant'] }}<br>{{ $hotel['hours']['rooftop'] }}</dd>
                    </div>
                </dl>
                <div class="flex flex-wrap gap-3">
                    <x-button :href="route('contact')" variant="primary">Nous écrire</x-button>
                    <x-button :href="$hotel['geo']['directions_url']" variant="outline">Itinéraire</x-button>
                </div>
            </div>

            <div class="reveal reveal--right overflow-hidden rounded-2xl shadow-card">
                <iframe title="Localisation de l'Hôtel Résidence Khadija à Thiès"
                        src="{{ $hotel['geo']['map_embed'] }}"
                        width="100%" height="420" style="border:0" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </x-container>
    </section>

@endsection
