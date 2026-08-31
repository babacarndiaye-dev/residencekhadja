@extends('layouts.app')

@section('title', 'Demande de réservation confirmée')
@push('head')<meta name="robots" content="noindex,nofollow">@endpush

@php
    $primary = $reservation;                       // 1re chambre du groupe (compat)
    $multi = $reservations->count() > 1;
    $unpaid = $groupDeposit > 0 && $reservations->sum(fn ($r) => $r->paidAmount()) < $groupDeposit;
    $paid = $reservations->sum(fn ($r) => $r->paidAmount());
@endphp

@section('content')
<section class="py-12 sm:py-16">
    <x-container size="narrow">
        <x-booking-steps current="done" class="mb-8" />

        <div class="flex flex-col items-center gap-4 text-center">
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-terracotta-100 text-terracotta-600">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 13 4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <h1 class="font-display text-3xl font-semibold text-nuit-900 sm:text-4xl">Merci, {{ $primary->guest->first_name }} !</h1>
            <p class="max-w-lg text-ink-soft">
                Votre demande de réservation{{ $multi ? ' ('.$reservations->count().' chambres)' : '' }} est enregistrée.
                Notre équipe la confirme par e-mail sous 24 heures à l'adresse
                <strong class="text-nuit-900">{{ $primary->guest->email }}</strong>.
            </p>
            <p class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold tracking-wider text-white">
                Référence : {{ $groupRef }}
            </p>
        </div>

        <div class="mt-10 flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-card sm:p-8">
            <h2 class="font-display text-xl font-semibold text-nuit-900">Récapitulatif</h2>
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-nuit-400">Séjour</dt><dd class="font-medium text-nuit-900">{{ pretty_date($primary->check_in) }} → {{ pretty_date($primary->check_out) }} ({{ $primary->nights() }} nuit{{ $primary->nights() > 1 ? 's' : '' }})</dd></div>
                <div><dt class="text-nuit-400">Voyageurs</dt><dd class="font-medium text-nuit-900">{{ $primary->adults }} adulte(s), {{ $primary->children }} enfant(s) · {{ $reservations->count() }} ch.</dd></div>
                <div><dt class="text-nuit-400">Tarif</dt><dd class="font-medium text-nuit-900">{{ $primary->ratePlan->name ?? 'Flexible' }}</dd></div>
                <div><dt class="text-nuit-400">Total estimé</dt><dd class="font-medium text-nuit-900">{{ money($groupTotal) }} (dépôt {{ money($groupDeposit) }})</dd></div>
            </dl>

            <div class="border-t border-sable-200 pt-4">
                <p class="mb-2 text-sm font-semibold text-nuit-900">Chambres</p>
                <ul class="flex flex-col gap-2">
                    @foreach ($reservations as $r)
                        <li class="flex items-center justify-between gap-3 rounded-xl bg-sable-50 px-3 py-2.5 text-sm">
                            <span class="flex items-center gap-2.5">
                                <img src="{{ $r->roomCategory->images[0] ?? '' }}" alt="" width="44" height="44" class="h-10 w-10 rounded-lg object-cover">
                                <span>
                                    <span class="block font-semibold text-nuit-900">{{ $r->roomCategory->name }}</span>
                                    <span class="block text-xs text-nuit-400">{{ $r->reference }}</span>
                                </span>
                            </span>
                            <span class="font-medium text-nuit-900">{{ money($r->total) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if (! empty($primary->extras))
                <div class="border-t border-sable-200 pt-3 text-sm">
                    <dt class="text-nuit-400">Services</dt>
                    <dd class="mt-1 text-nuit-900">{{ implode(', ', $primary->extras) }}</dd>
                </div>
            @endif

            @if ($unpaid)
                <div class="border-t border-sable-200 pt-4">
                    <p class="text-sm font-semibold text-nuit-900">Sécurisez votre réservation dès maintenant</p>
                    <p class="mt-1 text-xs text-nuit-400">Réglez l'acompte de {{ money($groupDeposit) }} en ligne (carte, Orange Money, Wave, Free Money).</p>
                    <form method="POST" action="{{ route('booking.pay', $groupRef) }}" class="mt-3">
                        @csrf
                        <button class="rounded-full bg-terracotta-500 px-6 py-3 text-sm font-semibold text-white hover:bg-terracotta-600">
                            Payer l'acompte — {{ money($groupDeposit) }}
                        </button>
                    </form>
                </div>
            @elseif ($paid > 0)
                <p class="border-t border-sable-200 pt-3 text-sm font-semibold text-emerald-700">Acompte de {{ money($paid) }} réglé ✓ — réservation confirmée.</p>
            @endif

            <p class="border-t border-sable-200 pt-3 text-xs text-nuit-400">
                Sans règlement de l'acompte, votre demande reste soumise à confirmation par l'hôtel sous 24 h.
            </p>
        </div>

        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <x-button :href="route('home')" variant="dark">Retour à l'accueil</x-button>
            <x-button :href="'https://wa.me/'.config('hotel.contact.whatsapp_href')" variant="outline">Ajouter une précision sur WhatsApp</x-button>
        </div>
    </x-container>
</section>
@endsection
