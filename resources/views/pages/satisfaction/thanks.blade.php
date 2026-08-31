@extends('layouts.app')

@section('title', 'Merci !')
@push('head')<meta name="robots" content="noindex,nofollow">@endpush

@section('content')
<section class="bg-sable-100 py-16 sm:py-28">
    <x-container size="narrow">
        <div class="reveal rounded-2xl bg-white p-8 text-center shadow-card sm:p-12">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-terracotta-50 text-2xl text-terracotta-600">★</div>
            <h1 class="mt-5 text-2xl text-nuit-900 sm:text-3xl">Merci pour votre retour</h1>

            @if ($promoter && $googleUrl)
                <p class="mx-auto mt-3 max-w-md text-sm text-nuit-500">
                    Ravi que votre séjour vous ait plu ! Un mot public aide énormément d'autres voyageurs
                    à nous découvrir — cela ne prend qu'un instant.
                </p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <a href="{{ $googleUrl }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 bg-terracotta-500 px-6 py-3 text-sm font-semibold text-white hover:bg-terracotta-600">
                        Partager sur Google
                    </a>
                    @if ($tripadvisorUrl)
                        <a href="{{ $tripadvisorUrl }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-2 border border-nuit-200 px-6 py-3 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">
                            Sur Tripadvisor
                        </a>
                    @endif
                </div>
            @else
                <p class="mx-auto mt-3 max-w-md text-sm text-nuit-500">
                    Votre avis a bien été transmis à l'équipe. Nous en tenons compte pour améliorer
                    chaque séjour — et nous espérons vous revoir bientôt à Thiès.
                </p>
            @endif

            <div class="mt-8">
                <x-button :href="route('home')" variant="light">Retour à l'accueil</x-button>
            </div>
        </div>
    </x-container>
</section>
@endsection
