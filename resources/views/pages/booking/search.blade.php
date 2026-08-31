@extends('layouts.app')

@section('title', 'Réserver — vos dates')
@section('description', "Vérifiez les disponibilités de l'Hôtel Résidence Khadija et réservez au meilleur tarif direct.")
@push('head')<meta name="robots" content="noindex,follow">@endpush

@section('content')

    {{-- ============================ HERO ============================ --}}
    <section class="relative isolate overflow-hidden bg-nuit-900">
        <img src="{{ asset('img/hotel/facade.jpg') }}" alt="" width="1200" height="1095"
             fetchpriority="high" decoding="async"
             class="absolute inset-0 -z-10 h-full w-full object-cover opacity-70">
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-nuit-900 via-nuit-900/80 to-nuit-900/50"></div>

        <x-container class="flex flex-col gap-6 pb-24 pt-12 sm:pt-16">
            <x-booking-steps current="search" tone="light" />
            <div class="max-w-2xl">
                <span class="eyebrow text-terracotta-300">Réservation directe</span>
                <h1 class="mt-3 font-display text-3xl font-semibold text-white sm:text-4xl lg:text-5xl">
                    Quand souhaitez-vous venir ?
                </h1>
                <p class="mt-3 text-lg leading-relaxed text-white/80">
                    Indiquez vos dates et le nombre de voyageurs. Vous choisirez votre chambre à l'étape suivante.
                </p>
            </div>
        </x-container>
    </section>

    {{-- ================= WIDGET (chevauche le hero) ================= --}}
    <section class="relative z-10 -mt-16">
        <x-container class="flex flex-col gap-4">
            @if ($errors->any())
                <div class="rounded-xl border border-terracotta-300 bg-terracotta-50 p-4 text-sm text-terracotta-800 shadow-card">
                    <p class="flex items-center gap-2 font-semibold"><x-icon name="error" :size="18" fill class="text-terracotta-600" /> Merci de corriger :</p>
                    <ul class="mt-1 list-inside list-disc">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <x-booking-widget tone="light" :booking="$booking" />
        </x-container>
    </section>

    {{-- ==================== AVANTAGES + AIDE ==================== --}}
    <section class="py-14 sm:py-20">
        <x-container class="grid gap-8 lg:grid-cols-[1.5fr_1fr] lg:items-start">
            <div class="flex flex-col gap-4">
                <h2 class="font-display text-lg font-semibold text-nuit-900">Réserver en direct, c'est mieux</h2>
                @foreach ([
                    ['verified', 'Meilleur tarif garanti', "Le prix le plus juste, sans commission d'agence."],
                    ['event_available', 'Annulation gratuite', "Jusqu'à 48 h avant l'arrivée sur le tarif flexible."],
                    ['storefront', 'Sans paiement en ligne', "Cette demande est transmise à la réception ; le règlement se fait à l'hôtel."],
                    ['workspace_premium', 'Attentions Khadija Privilege', 'Surclassement, late check-out et expériences Teranga selon disponibilité.'],
                ] as [$icon, $t, $d])
                    <div class="flex items-start gap-4 rounded-2xl border border-sable-200 bg-white p-5 shadow-card">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-terracotta-50 text-terracotta-600">
                            <x-icon name="{{ $icon }}" :size="22" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-nuit-900">{{ $t }}</p>
                            <p class="mt-1 text-sm leading-relaxed text-nuit-500">{{ $d }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <aside class="rounded-2xl bg-nuit-900 p-6 text-white/80">
                <h2 class="flex items-center gap-2 font-display text-lg font-semibold text-white">
                    <x-icon name="support_agent" :size="20" /> Besoin d'aide ?
                </h2>
                <p class="mt-2 text-sm">Notre équipe réservation répond 24h/24.</p>
                <div class="mt-4 flex flex-col gap-2.5 text-sm">
                    <a href="tel:{{ config('hotel.contact.phone_href') }}" class="flex items-center gap-2 hover:text-white">
                        <x-icon name="call" :size="16" class="text-terracotta-300" /> {{ config('hotel.contact.phone') }}
                    </a>
                    <a href="https://wa.me/{{ config('hotel.contact.whatsapp_href') }}" class="flex items-center gap-2 hover:text-white">
                        <x-icon name="chat" :size="16" class="text-terracotta-300" /> WhatsApp : {{ config('hotel.contact.whatsapp') }}
                    </a>
                    <a href="mailto:{{ config('hotel.contact.reservations_email') }}" class="flex items-center gap-2 hover:text-white">
                        <x-icon name="mail" :size="16" class="text-terracotta-300" /> {{ config('hotel.contact.reservations_email') }}
                    </a>
                </div>
                <div class="mt-6 rounded-xl bg-white/5 p-4 text-xs">
                    <p class="font-semibold text-white">Groupes & entreprises</p>
                    <p class="mt-1">À partir de 5 chambres ou pour un tarif société,
                        <a href="{{ route('contact') }}" class="underline hover:text-white">contactez le service commercial</a>.</p>
                </div>
            </aside>
        </x-container>
    </section>
@endsection
