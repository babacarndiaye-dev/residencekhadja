@extends('layouts.app')

@section('title', 'La carte')
@section('description', "La carte de l'Hôtel Résidence Khadija : Saveurs du Sénégal, grillades, desserts et boissons maison. Prix indicatifs en FCFA.")

@section('content')

    <x-page-hero eyebrow="Restaurant" title="La carte" crumb="La carte"
                 :image="'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=1600&q=65'">
        Un aperçu de nos plats. À table, scannez le QR pour commander ; en chambre, pour le room service.
    </x-page-hero>

    <section class="py-16 sm:py-24">
        <x-container size="narrow" class="flex flex-col gap-12">
            @foreach ($categories as $cat)
                <div>
                    <div class="flex items-baseline justify-between gap-3 border-b border-sable-300 pb-2">
                        <h2 class="font-display text-2xl text-nuit-900">{{ $cat->name }}</h2>
                        <span class="text-xs text-nuit-400">{{ $cat->venues->pluck('name')->join(' · ') }}</span>
                    </div>
                    <ul class="mt-4 flex flex-col divide-y divide-sable-200">
                        @foreach ($cat->items as $item)
                            <li class="flex gap-4 py-4">
                                @if ($item->image)
                                    <img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}" width="80" height="80" loading="lazy" class="h-16 w-16 shrink-0 rounded-lg object-cover">
                                @endif
                                <div class="flex-1">
                                    <div class="flex justify-between gap-3">
                                        <h3 class="font-semibold text-nuit-900">{{ $item->name }} @if ($item->is_signature)<span class="text-xs text-terracotta-600">· signature</span>@endif</h3>
                                        <span class="shrink-0 font-semibold text-nuit-900">{{ money($item->price) }}</span>
                                    </div>
                                    @if ($item->description)<p class="mt-0.5 text-sm text-ink-soft">{{ $item->description }}</p>@endif
                                    @if ($item->allergens)<p class="mt-1 text-xs text-nuit-400">Allergènes : {{ implode(', ', $item->allergens) }}</p>@endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <p class="text-xs text-nuit-400">Prix en FCFA, taxes comprises. Carte susceptible d’évoluer selon les arrivages.</p>
        </x-container>
    </section>

@endsection
