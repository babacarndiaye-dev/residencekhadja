@extends('layouts.app')

@section('title', 'Galerie')
@section('description', "Photos de l'Hôtel Résidence Khadija : chambres, restauration, espaces, séminaires et la ville de Thiès.")

@section('content')

    <x-page-hero eyebrow="Galerie" title="Un aperçu de la maison"
                 :image="asset('img/hotel/piscine.jpg')" />

    <section class="py-16 sm:py-24" x-data="{ filter: 'Tous' }">
        <x-container>
            <div class="reveal mb-8 flex flex-wrap gap-2">
                @foreach ($filters as $f)
                    <button type="button" x-on:click="filter = '{{ $f }}'"
                            :class="filter === '{{ $f }}' ? 'bg-nuit-900 text-white' : 'bg-white text-nuit-700 ring-1 ring-nuit-200 hover:ring-terracotta-400'"
                            class="rounded-full px-4 py-2 text-sm font-medium transition">
                        {{ $f }}
                    </button>
                @endforeach
            </div>

            <div class="columns-2 gap-3 sm:columns-3 lg:columns-4 [&>*]:mb-3">
                @foreach ($photos as $photo)
                    <figure x-show="filter === 'Tous' || filter === '{{ $photo['group'] }}'" x-transition.opacity
                            class="overflow-hidden rounded-xl">
                        <img src="{{ $photo['src'] }}" alt="{{ $photo['alt'] }}" width="600" height="750"
                             loading="lazy" decoding="async" class="w-full object-cover">
                    </figure>
                @endforeach
            </div>
        </x-container>
    </section>

@endsection
