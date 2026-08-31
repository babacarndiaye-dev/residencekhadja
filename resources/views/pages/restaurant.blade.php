@extends('layouts.app')

@section('title', 'Restaurant & Saveurs du Sénégal')
@section('description', "Restaurant Teranga, Rooftop Baobab, Lobby Lounge et Pool Bar : la table de l'Hôtel Résidence Khadija célèbre le terroir sénégalais et une carte internationale.")

@push('head')
@php
    $restaurantLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Restaurant',
        'name' => 'Restaurant Teranga — Hôtel Résidence Khadija',
        'servesCuisine' => ['Sénégalaise', 'Internationale'],
        'priceRange' => 'XOF',
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => config('hotel.city'),
            'addressCountry' => 'SN',
        ],
        'telephone' => config('hotel.contact.phone'),
        'openingHours' => 'Mo-Su 06:30-23:00',
    ];
@endphp
<script type="application/ld+json">{!! json_encode($restaurantLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')

    <x-page-hero eyebrow="Gastronomie" title="Saveurs du Sénégal, servies avec la Teranga"
                 crumb="Restaurant"
                 :image="asset('img/hotel/lobby.jpg')">
        Quatre lieux, une même exigence : des produits du marché de Thiès, une cuisine sincère
        et des dressages soignés, du petit-déjeuner au dernier cocktail.
    </x-page-hero>

    {{-- Lieux de restauration --}}
    <section class="py-16 sm:py-24">
        <x-container class="flex flex-col gap-16">
            @foreach ($venues as $i => $venue)
                <article class="reveal grid gap-8 lg:grid-cols-2 lg:items-center {{ $i % 2 ? 'lg:[&>figure]:order-2' : '' }}">
                    <figure class="overflow-hidden rounded-2xl shadow-card">
                        <img src="{{ $venue['image'] }}" alt="{{ $venue['name'] }}" width="900" height="640"
                             loading="lazy" decoding="async" class="aspect-[7/5] w-full object-cover">
                    </figure>
                    <div class="flex flex-col gap-3">
                        <span class="eyebrow">{{ $venue['type'] }}</span>
                        <h2 class="text-3xl text-nuit-900">{{ $venue['name'] }}</h2>
                        <p class="text-ink-soft leading-relaxed">{{ $venue['desc'] }}</p>
                        <p class="text-sm font-medium text-terracotta-600">Ouvert {{ $venue['hours'] }}</p>
                    </div>
                </article>
            @endforeach
        </x-container>
    </section>

    {{-- Saveurs du Sénégal --}}
    <section class="bg-nuit-900 py-16 text-white sm:py-24">
        <x-container>
            <x-section-heading eyebrow="La carte" title="Saveurs du Sénégal" tone="light" class="reveal">
                Chaque plat porte le nom d'une région, d'une recette de famille ou d'un geste transmis.
                Voici quelques signatures du chef.
            </x-section-heading>

            <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($saveurs as $dish)
                    <article class="reveal overflow-hidden rounded-2xl bg-white/5 ring-1 ring-white/10">
                        <div class="aspect-[4/3] overflow-hidden">
                            <img src="{{ $dish['image'] }}" alt="{{ $dish['name'] }}" width="600" height="450"
                                 loading="lazy" decoding="async" class="h-full w-full object-cover">
                        </div>
                        <div class="flex flex-col gap-2 p-5">
                            <span class="eyebrow">{{ $dish['tag'] }}</span>
                            <h3 class="text-xl text-white">{{ $dish['name'] }}</h3>
                            <p class="text-sm leading-relaxed text-white/65">{{ $dish['story'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>

            <p class="reveal mt-10 text-sm text-white/50">
                Allergènes et régimes particuliers : signalez-le à votre serveur, la brigade adapte les recettes.
            </p>
        </x-container>
    </section>

    <section class="py-16 sm:py-24">
        <x-container class="reveal flex flex-col items-start gap-5 rounded-3xl bg-sable-100 p-8 sm:p-12 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-xl">
                <h2 class="text-2xl text-nuit-900 sm:text-3xl">Réserver une table</h2>
                <p class="mt-2 text-ink-soft">Pour un dîner « Saveurs du Sénégal », une soirée au rooftop ou un déjeuner d'affaires.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <x-button :href="route('menu.public')" variant="primary">Voir la carte</x-button>
                <x-button :href="'tel:'.config('hotel.contact.phone_href')" variant="outline">Appeler le restaurant</x-button>
                <x-button :href="route('contact')" variant="ghost">Écrire un message</x-button>
            </div>
        </x-container>
    </section>

@endsection
