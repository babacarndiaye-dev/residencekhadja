@extends('layouts.app')

@section('title', 'Espaces & bien-être')
@section('description', "Piscine extérieure, rooftop panoramique, salle de fitness, spa et Business Corner : les espaces de l'Hôtel Résidence Khadija à Thiès.")

@section('content')

    <x-page-hero eyebrow="Espaces & bien-être" title="Respirer, se dépenser, travailler"
                 :image="asset('img/hotel/piscine-2.jpg')">
        Des lieux ouverts aux résidents, conçus pour prolonger le séjour au-delà de la chambre.
    </x-page-hero>

    <section class="py-16 sm:py-24">
        <x-container class="flex flex-col gap-16">
            @foreach ($leisure as $i => $space)
                <article class="reveal grid gap-8 lg:grid-cols-2 lg:items-center {{ $i % 2 ? 'lg:[&>figure]:order-2' : '' }}">
                    <figure class="overflow-hidden rounded-2xl shadow-card">
                        <img src="{{ $space['image'] }}" alt="{{ $space['name'] }}" width="900" height="640"
                             loading="lazy" decoding="async" class="aspect-[7/5] w-full object-cover">
                    </figure>
                    <div class="flex flex-col gap-3">
                        <h2 class="text-3xl text-nuit-900">{{ $space['name'] }}</h2>
                        <p class="text-ink-soft leading-relaxed">{{ $space['desc'] }}</p>
                        <p class="text-sm font-medium text-terracotta-600">{{ $space['info'] }}</p>
                    </div>
                </article>
            @endforeach
        </x-container>
    </section>

    <x-cta-band title="Un séjour, tous les espaces inclus"
                text="Piscine, fitness et Business Corner sont en accès libre pour les résidents. Réservez votre chambre en direct." />

@endsection
