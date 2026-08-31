@extends('layouts.app')

@section('title', 'Offres & séjours')
@section('description', "Meilleur tarif direct, Escale Affaires, Week-end Teranga, Long Séjour : les offres de l'Hôtel Résidence Khadija à Thiès, sans intermédiaire.")

@section('content')

    <x-page-hero eyebrow="Offres" title="Réservez en direct, gagnez à chaque fois"
                 :image="asset('img/hotel/suite-teranga.jpg')">
        Pas de commission d'agence, pas de tarif gonflé : le prix le plus juste est toujours ici,
        assorti d'attentions réservées à nos clients directs.
    </x-page-hero>

    <section class="py-16 sm:py-24">
        <x-container>
            <div class="grid gap-6 md:grid-cols-2">
                @foreach ($offers as $offer)
                    <div class="reveal"><x-offer-card :offer="$offer" /></div>
                @endforeach
            </div>

            <p class="reveal mt-10 text-sm text-nuit-500">
                Offres soumises à disponibilité et non cumulables sauf mention contraire.
                Les tarifs entreprise sont accessibles via un compte société — <a href="{{ route('contact') }}" class="text-terracotta-600 underline">nous contacter</a>.
            </p>
        </x-container>
    </section>

    <x-cta-band />

@endsection
