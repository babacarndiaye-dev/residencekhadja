@extends('layouts.app')

@section('title', 'Page introuvable')
@push('head')<meta name="robots" content="noindex,follow">@endpush

@section('content')
<section class="py-24 sm:py-32">
    <x-container size="narrow" class="flex flex-col items-center gap-6 text-center">
        <span class="eyebrow">Erreur 404</span>
        <h1 class="font-display text-4xl font-semibold text-nuit-900 sm:text-5xl">Cette page a pris le large</h1>
        <p class="max-w-md text-ink-soft">
            Le lien est peut-être ancien ou mal recopié. Retrouvez votre chemin ci-dessous —
            ou laissez-nous vous accueillir directement.
        </p>
        <div class="flex flex-wrap justify-center gap-3">
            <x-button :href="route('home')" variant="primary">Accueil</x-button>
            <x-button :href="route('rooms.index')" variant="outline">Chambres & Suites</x-button>
            <x-button :href="route('booking.start')" variant="outline">Réserver</x-button>
        </div>
    </x-container>
</section>
@endsection
