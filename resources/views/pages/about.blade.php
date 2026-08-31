@extends('layouts.app')

@section('title', "L'hôtel")
@section('description', "Découvrez l'Hôtel Résidence Khadija à Thiès : notre histoire, la Teranga sénégalaise et un établissement premium pensé pour les affaires et la famille.")

@php $hotel = config('hotel'); @endphp

@section('content')

    <x-page-hero eyebrow="L'hôtel" title="La Teranga, élevée au rang d'art de recevoir"
                 :image="asset('img/hotel/facade.jpg')">
        {{ $hotel['intro'] }}
    </x-page-hero>

    <section class="py-20 sm:py-28">
        <x-container class="grid gap-14 lg:grid-cols-[1fr_1.1fr] lg:items-center">
            <img src="{{ asset('img/hotel/lobby.jpg') }}"
                 alt="Lobby et salon d'accueil de l'Hôtel Résidence Khadija" width="1200" height="800"
                 loading="lazy" decoding="async" class="reveal aspect-[4/5] w-full rounded-2xl object-cover shadow-card">
            <div class="reveal flex flex-col gap-6">
                <x-section-heading eyebrow="Notre histoire" title="Un hôtel de Thiès, résolument ancré et tourné vers demain">
                    Née d'une famille attachée à Thiès, la Résidence Khadija est pensée comme une maison :
                    on y est accueilli, jamais reçu. Le bâtiment mêle l'artisanat local — tissage, bois,
                    laiton — à une architecture contemporaine où la lumière et l'air circulent.
                </x-section-heading>
                <p class="text-ink-soft leading-relaxed">
                    Nous avons fait le choix d'un établissement à taille humaine, doté des services d'un
                    grand hôtel : restauration d'exception, espaces de travail, bien-être, salles de
                    séminaire et une conciergerie qui connaît la région par cœur.
                </p>
                <div class="grid grid-cols-2 gap-6 pt-2 sm:grid-cols-4">
                    @foreach ($stats as $stat)
                        <div>
                            <div class="font-display text-3xl font-semibold text-terracotta-600">{{ $stat['value'] }}</div>
                            <div class="mt-1 text-xs uppercase tracking-wider text-nuit-500">{{ $stat['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-container>
    </section>

    <section class="bg-sable-100 py-20 sm:py-28">
        <x-container>
            <x-section-heading eyebrow="Nos engagements" title="Ce qui guide chacun de nos gestes" class="reveal" />
            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['t' => 'Teranga sincère', 'd' => "Un accueil personnalisé, le thé offert, l'attention au détail. L'hospitalité sans calcul."],
                    ['t' => 'Ancrage local', 'd' => "Produits du marché de Thiès, artisans de la région, personnel formé et fidélisé."],
                    ['t' => 'Confort premium', 'd' => "Literie d'exception, insonorisation, climatisation individuelle, Wi-Fi fibre partout."],
                    ['t' => 'Efficacité business', 'd' => "Check-in privatif, Business Corner 24h/24, salles équipées et facturation entreprise."],
                    ['t' => 'Bien-être', 'd' => "Piscine, fitness, spa et rooftop : prendre soin de soi fait partie du séjour."],
                    ['t' => 'Technologie discrète', 'd' => "Réservation directe, QR menu, room service digital — au service de l'accueil, jamais l'inverse."],
                ] as $value)
                    <article class="reveal flex flex-col gap-3 rounded-2xl bg-white p-6 shadow-card">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-terracotta-100 text-terracotta-600">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 1.5 12.6 7l6 .5-4.5 3.9 1.4 5.8L10 14.9 4.5 17.2l1.4-5.8L1.4 7.5l6-.5L10 1.5Z"/></svg>
                        </span>
                        <h3 class="text-xl text-nuit-900">{{ $value['t'] }}</h3>
                        <p class="text-sm leading-relaxed text-ink-soft">{{ $value['d'] }}</p>
                    </article>
                @endforeach
            </div>
        </x-container>
    </section>

    <section class="py-20 sm:py-28">
        <x-container>
            <x-section-heading eyebrow="Teranga" title="Expériences signature" class="reveal" />
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($experiences['items'] as $xp)
                    <article class="reveal group overflow-hidden rounded-2xl bg-white shadow-card">
                        <div class="aspect-[4/3] overflow-hidden">
                            <img src="{{ $xp['image'] }}" alt="{{ $xp['name'] }}" width="600" height="450"
                                 loading="lazy" decoding="async"
                                 class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
                        </div>
                        <div class="flex flex-col gap-2 p-5">
                            <h3 class="text-lg text-nuit-900">{{ $xp['name'] }}</h3>
                            <p class="text-sm leading-relaxed text-ink-soft">{{ $xp['desc'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </x-container>
    </section>

    <x-cta-band />

@endsection
