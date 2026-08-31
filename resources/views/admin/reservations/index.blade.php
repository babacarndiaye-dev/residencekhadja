@extends('admin.layout')
@section('title', 'Réservations')

@section('content')
<div class="flex flex-col gap-5">

    <form method="GET" class="flex flex-wrap items-end gap-3 rounded-2xl bg-white p-4 shadow-card">
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">
            Recherche
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Réf., nom, e-mail"
                   class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">
            Statut
            <select name="status" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="">Tous</option>
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">
            Arrivée du
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">
            au
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white hover:bg-nuit-800">Filtrer</button>
        <a href="{{ route('admin.reservations.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">Réinitialiser</a>
        @if (auth()->user()->hasRole('reception'))
            <a href="{{ route('admin.reservations.create') }}" class="ml-auto rounded-full bg-terracotta-500 px-5 py-2 text-sm font-semibold text-white hover:bg-terracotta-600">+ Réservation sur place</a>
        @endif
    </form>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr>
                    <th class="px-4 py-3">Référence</th>
                    <th class="px-4 py-3">Client</th>
                    <th class="px-4 py-3">Catégorie</th>
                    <th class="px-4 py-3">Séjour</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reservations as $r)
                    @php
                        $g = $r->group_reference ? ($groups[$r->group_reference] ?? null) : null;
                        $gStatuses = $g ? explode(',', $g->statuses) : [];
                    @endphp
                    <tr class="border-t border-sable-200 hover:bg-sable-50">
                        <td class="px-4 py-3 font-mono text-xs">
                            {{ $g ? $r->group_reference : $r->reference }}
                            @if ($g)<span class="ml-1 rounded-full bg-nuit-900 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $g->rooms }} ch.</span>@endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-medium text-nuit-900">{{ $r->guest->fullName() }}</span>
                            <span class="block text-xs text-nuit-400">{{ $r->guest->email }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if ($g)
                                {{ $g->rooms }} chambres<span class="block text-xs text-nuit-400">séjour groupé</span>
                            @else
                                {{ $r->roomCategory->name }}<span class="block text-xs text-nuit-400">Ch. {{ $r->room->number ?? '—' }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-nuit-600">{{ $r->check_in->format('d/m/Y') }}<br>→ {{ $r->check_out->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ money($g ? $g->total : $r->total) }}</td>
                        <td class="px-4 py-3">
                            @if ($g && count($gStatuses) > 1)
                                <span class="rounded-full bg-sable-200 px-2 py-0.5 text-xs text-nuit-600">Statuts multiples</span>
                            @else
                                <x-admin.badge :status="$g ? $gStatuses[0] : $r->status" />
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.reservations.show', $r) }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Ouvrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-nuit-400">Aucune réservation ne correspond.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $reservations->links() }}</div>
</div>
@endsection
