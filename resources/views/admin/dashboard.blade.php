@extends('admin.layout')
@section('title', 'Tableau de bord')

@section('content')
<div class="flex flex-col gap-6">

    <p class="text-sm text-nuit-500">{{ ucfirst(pretty_date($today)) }} — Front Office</p>

    {{-- KPIs --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $tiles = [
                ['Taux d’occupation', $occupancy.' %', $roomsSoldToday.' / '.$sellableRooms.' chambres'],
                ['Arrivées du jour', $arrivals->count(), $pendingCount.' à confirmer'],
                ['Départs du jour', $departures->count(), $inHouse->count().' clients en séjour'],
                ['ADR / RevPAR', money($adr), 'RevPAR '.money($revpar)],
            ];
        @endphp
        @foreach ($tiles as [$label, $value, $hint])
            <div class="rounded-2xl bg-white p-5 shadow-card">
                <p class="text-xs uppercase tracking-wider text-nuit-400">{{ $label }}</p>
                <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ $value }}</p>
                <p class="mt-1 text-xs text-nuit-500">{{ $hint }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        {{-- Arrivées --}}
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-display text-lg font-semibold text-nuit-900">Arrivées</h2>
                <a href="{{ route('admin.reservations.index', ['from' => $today->toDateString(), 'to' => $today->toDateString()]) }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Tout voir</a>
            </div>
            @forelse ($arrivals as $r)
                <div class="flex items-center justify-between gap-3 border-t border-sable-200 py-2.5 text-sm">
                    <div>
                        <a href="{{ route('admin.reservations.show', $r) }}" class="font-medium text-nuit-900 hover:text-terracotta-600">{{ $r->guest->fullName() }}</a>
                        <p class="text-xs text-nuit-400">{{ $r->reference }} · {{ $r->roomCategory->name }} · {{ $r->nights() }} nuit(s){{ $r->arrival_time ? ' · '.$r->arrival_time : '' }}</p>
                    </div>
                    <x-admin.badge :status="$r->status" />
                </div>
            @empty
                <p class="border-t border-sable-200 py-4 text-sm text-nuit-400">Aucune arrivée prévue aujourd’hui.</p>
            @endforelse
        </section>

        {{-- Départs --}}
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Départs</h2>
            @forelse ($departures as $r)
                <div class="flex items-center justify-between gap-3 border-t border-sable-200 py-2.5 text-sm">
                    <div>
                        <a href="{{ route('admin.reservations.show', $r) }}" class="font-medium text-nuit-900 hover:text-terracotta-600">{{ $r->guest->fullName() }}</a>
                        <p class="text-xs text-nuit-400">{{ $r->reference }} · Ch. {{ $r->room->number ?? '—' }} · Solde {{ money($r->balance()) }}</p>
                    </div>
                    <x-admin.badge :status="$r->status" />
                </div>
            @empty
                <p class="border-t border-sable-200 py-4 text-sm text-nuit-400">Aucun départ prévu aujourd’hui.</p>
            @endforelse
        </section>
    </div>

    {{-- Restauration --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <a href="{{ route('admin.kds.index') }}" class="rounded-2xl bg-white p-5 shadow-card hover:shadow-luxe">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Commandes en cours</p>
            <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ $openOrders }}</p>
            <p class="mt-1 text-xs text-terracotta-600">Ouvrir le KDS →</p>
        </a>
        <a href="{{ route('admin.service.index') }}" class="rounded-2xl bg-white p-5 shadow-card hover:shadow-luxe">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Demandes en salle</p>
            <p class="mt-1 font-display text-2xl font-semibold {{ $openServiceRequests ? 'text-terracotta-600' : 'text-nuit-900' }}">{{ $openServiceRequests }}</p>
            <p class="mt-1 text-xs text-terracotta-600">Voir le service →</p>
        </a>
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-xs uppercase tracking-wider text-nuit-400">CA restaurant du jour</p>
            <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ money($restaurantRevenueToday) }}</p>
        </div>
    </div>

    {{-- Opérations --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <a href="{{ route('admin.housekeeping.index') }}" class="rounded-2xl bg-white p-5 shadow-card hover:shadow-luxe">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Ménage du jour</p>
            <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ $hkTasksDone }} / {{ $hkTasksToday }}</p>
            <p class="mt-1 text-xs text-terracotta-600">Plan de ménage →</p>
        </a>
        <a href="{{ route('admin.maintenance.tickets') }}" class="rounded-2xl bg-white p-5 shadow-card hover:shadow-luxe">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Tickets maintenance</p>
            <p class="mt-1 font-display text-2xl font-semibold {{ $maintenanceCritical ? 'text-terracotta-600' : 'text-nuit-900' }}">{{ $maintenanceOpen }}</p>
            <p class="mt-1 text-xs text-nuit-500">{{ $maintenanceCritical }} critique(s)</p>
        </a>
        <a href="{{ route('admin.maintenance.plans') }}" class="rounded-2xl bg-white p-5 shadow-card hover:shadow-luxe">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Préventif à générer</p>
            <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ $preventiveDue }}</p>
            <p class="mt-1 text-xs text-terracotta-600">Plans préventifs →</p>
        </a>
    </div>

    {{-- Finance & stocks --}}
    <div class="grid gap-4 sm:grid-cols-4">
        <a href="{{ route('admin.finance.dashboard') }}" class="rounded-2xl bg-white p-5 shadow-card hover:shadow-luxe">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Trésorerie</p>
            <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ money($treasury) }}</p>
        </a>
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Résultat du mois</p>
            <p class="mt-1 font-display text-2xl font-semibold {{ ($monthIncome - $monthExpense) >= 0 ? 'text-emerald-700' : 'text-terracotta-700' }}">{{ money($monthIncome - $monthExpense) }}</p>
            <p class="mt-1 text-xs text-nuit-400">+{{ money($monthIncome) }} / −{{ money($monthExpense) }}</p>
        </div>
        <a href="{{ route('admin.stock.index') }}" class="rounded-2xl bg-white p-5 shadow-card hover:shadow-luxe">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Valeur du stock</p>
            <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ money($stockValue) }}</p>
        </a>
        <a href="{{ route('admin.stock.index', ['ruptures' => 1]) }}" class="rounded-2xl bg-white p-5 shadow-card hover:shadow-luxe">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Articles sous le seuil</p>
            <p class="mt-1 font-display text-2xl font-semibold {{ $stockLow ? 'text-terracotta-600' : 'text-nuit-900' }}">{{ $stockLow }}</p>
        </a>
        <a href="{{ route('admin.hr.dashboard') }}" class="rounded-2xl bg-white p-5 shadow-card hover:shadow-luxe">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Effectif</p>
            <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ $headcount }}</p>
            <p class="mt-1 text-xs {{ $pendingLeave ? 'text-terracotta-600' : 'text-nuit-400' }}">{{ $pendingLeave }} congé(s) à traiter</p>
        </a>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_1.4fr]">
        {{-- État des chambres --}}
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-display text-lg font-semibold text-nuit-900">État des chambres</h2>
                <a href="{{ route('admin.rooms.index') }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Gérer</a>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach (\App\Models\Room::STATUSES as $key => $label)
                    <span class="flex items-center gap-2 rounded-lg bg-sable-100 px-3 py-1.5 text-sm">
                        <x-admin.badge :status="$key" :label="$label" />
                        <strong class="text-nuit-900">{{ $roomStatus[$key] ?? 0 }}</strong>
                    </span>
                @endforeach
            </div>
        </section>

        {{-- Dernières réservations --}}
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Dernières réservations</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wider text-nuit-400">
                        <tr><th class="py-2">Réf.</th><th>Client</th><th>Séjour</th><th>Total</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($recent as $r)
                            <tr class="border-t border-sable-200">
                                <td class="py-2 font-mono text-xs">{{ $r->reference }}</td>
                                <td>{{ $r->guest->fullName() }}</td>
                                <td class="text-xs text-nuit-500">{{ $r->check_in->format('d/m') }} → {{ $r->check_out->format('d/m') }}</td>
                                <td>{{ money($r->total) }}</td>
                                <td class="text-right"><a href="{{ route('admin.reservations.show', $r) }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Ouvrir</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
