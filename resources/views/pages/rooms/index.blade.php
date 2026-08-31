@extends('layouts.app')

@section('title', 'Chambres & Suites')
@section('description', "Chambres Supérieure et Deluxe, Suites Junior, Exécutive et Teranga : découvrez les hébergements premium de l'Hôtel Résidence Khadija à Thiès.")

@section('content')

    <x-page-hero eyebrow="Chambres & Suites" title="Cinq façons de bien dormir à Thiès"
                 :image="asset('img/hotel/suite-executive.jpg')">
        Des matières naturelles, une literie d'exception et tout l'équipement pour travailler.
        Le meilleur tarif est toujours ici, en réservation directe.
    </x-page-hero>

    <section class="py-16 sm:py-24">
        <x-container>
            <div class="reveal mb-10 flex flex-wrap items-center gap-2">
                <a href="{{ route('rooms.index') }}"
                   class="rounded-full px-4 py-2 text-sm font-medium {{ ! $active ? 'bg-nuit-900 text-white' : 'bg-white text-nuit-700 ring-1 ring-nuit-200 hover:ring-terracotta-400' }}">
                    Tout
                </a>
                @foreach ($categories as $key => $label)
                    <a href="{{ route('rooms.index', ['categorie' => $key]) }}"
                       class="rounded-full px-4 py-2 text-sm font-medium {{ $active === $key ? 'bg-nuit-900 text-white' : 'bg-white text-nuit-700 ring-1 ring-nuit-200 hover:ring-terracotta-400' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            @if ($rooms->isEmpty())
                <p class="rounded-2xl bg-white p-10 text-center text-nuit-500 shadow-card">
                    Aucune chambre dans cette catégorie pour le moment.
                </p>
            @else
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($rooms as $room)
                        <div class="reveal"><x-room-card :room="$room" /></div>
                    @endforeach
                </div>
            @endif
        </x-container>
    </section>

    <x-cta-band />

@endsection
