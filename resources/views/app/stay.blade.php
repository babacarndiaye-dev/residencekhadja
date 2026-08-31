@extends('app.layout')
@section('title', 'Mon séjour')

@section('content')
<div class="flex flex-col gap-4">

    <section class="rounded-2xl bg-white p-5 shadow-card">
        <h1 class="font-display text-lg font-semibold text-nuit-900">Réservation {{ $stay->reference }}</h1>
        <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
            <div><dt class="text-nuit-400">Arrivée</dt><dd class="font-medium text-nuit-900">{{ pretty_date($stay->check_in) }}</dd></div>
            <div><dt class="text-nuit-400">Départ</dt><dd class="font-medium text-nuit-900">{{ pretty_date($stay->check_out) }}</dd></div>
            <div><dt class="text-nuit-400">Nuits</dt><dd class="font-medium text-nuit-900">{{ $nights }}</dd></div>
            <div><dt class="text-nuit-400">Statut</dt><dd class="font-medium text-nuit-900">{{ $stay->statusLabel() }}</dd></div>
            <div><dt class="text-nuit-400">Chambre</dt><dd class="font-medium text-nuit-900">{{ $stay->roomCategory->name }}{{ $stay->room ? ' · '.$stay->room->number : '' }}</dd></div>
            <div><dt class="text-nuit-400">Voyageurs</dt><dd class="font-medium text-nuit-900">{{ $stay->adults }} ad. {{ $stay->children ? $stay->children.' enf.' : '' }}</dd></div>
        </dl>
    </section>

    <section class="rounded-2xl bg-white p-5 shadow-card">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-nuit-500">Folio</h2>
        <table class="w-full text-sm">
            <tbody>
                <tr class="border-b border-sable-100"><td class="py-1.5 text-nuit-500">Hébergement & taxes</td><td class="py-1.5 text-right">{{ money($stay->total) }}</td></tr>
                @forelse ($stay->charges as $c)
                    <tr class="border-b border-sable-100"><td class="py-1.5 text-nuit-500">{{ $c->label }}</td><td class="py-1.5 text-right">{{ money($c->amount) }}</td></tr>
                @empty
                @endforelse
                <tr class="border-b border-sable-100"><td class="py-1.5 font-semibold">Total consommé</td><td class="py-1.5 text-right font-semibold">{{ money($stay->grandTotal()) }}</td></tr>
                @foreach ($stay->payments as $p)
                    <tr><td class="py-1.5 text-emerald-700">Règlement {{ $p->received_at->format('d/m') }}</td><td class="py-1.5 text-right text-emerald-700">− {{ money($p->amount) }}</td></tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-3 flex items-center justify-between border-t border-sable-200 pt-3">
            <span class="text-sm font-semibold text-nuit-900">Solde à régler</span>
            <span class="font-display text-xl font-bold {{ $balance > 0 ? 'text-terracotta-600' : 'text-emerald-700' }}">{{ money($balance) }}</span>
        </div>

        @if ($balance > 0)
            <form method="POST" action="{{ route('guest.pay') }}" class="mt-3">
                @csrf
                <button class="w-full rounded-full bg-terracotta-500 px-6 py-3.5 text-base font-semibold text-white hover:bg-terracotta-600">
                    Régler {{ money($balance) }} en ligne
                </button>
            </form>
            <p class="mt-1 text-center text-[0.7rem] text-nuit-400">Carte · Orange Money · Wave · Free Money</p>
        @else
            <p class="mt-3 text-center text-sm font-semibold text-emerald-700">Votre séjour est intégralement réglé ✓</p>
        @endif
    </section>

    <a href="{{ route('guest.menu') }}" class="flex items-center justify-center gap-2 rounded-2xl bg-white p-4 text-sm font-semibold text-nuit-700 shadow-card">
        <x-icon name="restaurant" :size="18" class="text-terracotta-500" /> Commander en chambre
    </a>
</div>
@endsection
