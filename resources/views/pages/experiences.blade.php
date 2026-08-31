@extends('layouts.app')

@section('title', 'Expériences & Teranga')
@section('description', "Rituel du thé, marché de Thiès, atelier percussions, escapade au Lac Rose : vivez le Sénégal autrement avec les expériences de l'Hôtel Résidence Khadija.")

@section('content')

    <x-page-hero eyebrow="Teranga" title="Vivre Thiès, au-delà de la chambre"
                 crumb="Expériences"
                 :image="asset('img/hotel/lobby.jpg')">
        {{ $experiences['teranga_pitch'] }}
    </x-page-hero>

    <section class="py-16 sm:py-24">
        <x-container>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($experiences['items'] as $xp)
                    <article class="reveal group overflow-hidden rounded-2xl bg-white shadow-card">
                        <div class="aspect-[4/3] overflow-hidden">
                            <img src="{{ $xp['image'] }}" alt="{{ $xp['name'] }}" width="700" height="525"
                                 loading="lazy" decoding="async"
                                 class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
                        </div>
                        <div class="flex flex-col gap-2 p-6">
                            <h2 class="text-xl text-nuit-900">{{ $xp['name'] }}</h2>
                            <p class="text-sm leading-relaxed text-ink-soft">{{ $xp['desc'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>

            <p class="reveal mt-10 text-sm text-nuit-500">
                Toutes les expériences se réservent à la conciergerie, la veille pour le lendemain.
            </p>
        </x-container>
    </section>

    <x-cta-band title="Composez votre séjour Teranga"
                text="Dites-nous ce qui vous ferait plaisir : notre conciergerie construit votre programme sur mesure." />

@endsection
