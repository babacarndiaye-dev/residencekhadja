@extends('app.layout')
@section('title', 'Accueil')

@section('content')
<div class="flex flex-col gap-4">

    {{-- Carte de bienvenue --}}
    <section class="relative overflow-hidden rounded-3xl bg-nuit-900 p-5 text-white shadow-luxe">
        <span class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-terracotta-500/20 blur-2xl"></span>
        <p class="text-[0.7rem] uppercase tracking-[0.22em] text-white/45">Bienvenue</p>
        <h1 class="font-display text-2xl font-semibold text-white">{{ $stay->guest->first_name }}</h1>
        <p class="mt-1 text-sm text-white/65">
            {{ pretty_date($stay->check_in) }} → {{ pretty_date($stay->check_out) }} · {{ $stay->roomCategory->name }}
        </p>
        <div class="mt-4 flex items-center justify-between rounded-2xl bg-white/10 px-4 py-2.5">
            <span class="flex items-center gap-1.5 text-sm text-white/60"><x-icon name="meeting_room" :size="18" /> Chambre</span>
            <span class="font-display text-lg font-semibold">{{ $stay->room->number ?? 'à l’arrivée' }}</span>
        </div>
    </section>

    {{-- Solde / demandes --}}
    <div class="grid grid-cols-2 gap-3">
        <a href="{{ route('guest.stay') }}" class="rounded-2xl bg-white p-4 shadow-card">
            <span class="flex items-center gap-1.5 text-xs text-nuit-400"><x-icon name="account_balance_wallet" :size="16" /> Solde du séjour</span>
            <p class="mt-1.5 font-display text-xl font-semibold {{ $balance > 0 ? 'text-terracotta-600' : 'text-emerald-700' }}">{{ money($balance) }}</p>
        </a>
        <a href="{{ route('guest.requests') }}" class="rounded-2xl bg-white p-4 shadow-card">
            <span class="flex items-center gap-1.5 text-xs text-nuit-400"><x-icon name="pending_actions" :size="16" /> En cours</span>
            <p class="mt-1.5 font-display text-xl font-semibold text-nuit-900">{{ $openRequests }}</p>
        </a>
    </div>

    {{-- Accès au catalogue --}}
    <a href="{{ route('guest.requests') }}"
       class="flex items-center gap-3 overflow-hidden rounded-2xl bg-gradient-to-br from-terracotta-500 to-terracotta-700 p-4 text-white shadow-card">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-white/15"><x-icon name="room_service" :size="24" /></span>
        <span class="min-w-0 flex-1">
            <span class="block font-display text-base font-semibold">Services en chambre</span>
            <span class="block text-xs text-white/80">Room service, ménage, taxi, réveil… imputés au folio</span>
        </span>
        <x-icon name="chevron_right" :size="22" />
    </a>

    {{-- Raccourcis --}}
    <section class="rounded-2xl bg-white p-4 shadow-card">
        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wider text-nuit-500">Accès rapide</h2>
        <div class="grid grid-cols-3 gap-2.5">
            @foreach ($quickActions as $a)
                @php
                    $href = match (true) {
                        ! empty($a['cat']) => route('guest.requests', ['cat' => $a['cat']]),
                        ($a['to'] ?? null) === 'wifi' => route('guest.wifi'),
                        ($a['to'] ?? null) === 'loyalty' => route('guest.loyalty'),
                        default => route('guest.requests'),
                    };
                @endphp
                <a href="{{ $href }}" class="flex flex-col items-center gap-1.5 rounded-2xl bg-sable-50 py-3.5 text-center ring-1 ring-sable-200/60 hover:bg-sable-100">
                    <span class="grid h-10 w-10 place-items-center rounded-full bg-terracotta-50 text-terracotta-600"><x-icon name="{{ $a['icon'] }}" :size="20" /></span>
                    <span class="text-[0.68rem] font-semibold leading-tight text-nuit-700">{{ $a['label'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <a href="https://wa.me/{{ config('hotel.contact.whatsapp_href') }}"
       class="flex items-center justify-center gap-2 rounded-2xl bg-white p-4 text-sm font-semibold text-nuit-700 shadow-card">
        <x-icon name="chat" :size="18" class="text-emerald-600" /> Écrire à la conciergerie sur WhatsApp
    </a>
</div>
@endsection
