@extends('admin.layout')
@section('title', 'Maintenance')

@section('content')
<div class="flex flex-col gap-5">

    <div class="grid gap-4 sm:grid-cols-4">
        @php $tiles = [
            ['Tickets ouverts', $summary['open'], route('admin.maintenance.tickets', ['status' => 'open'])],
            ['Critiques', $summary['critical'], route('admin.maintenance.tickets', ['priority' => 'critical'])],
            ['Préventif dû', $summary['preventive_due'], route('admin.maintenance.plans')],
            ['Coût du mois', money($summary['cost_month'] ?? 0), null],
        ]; @endphp
        @foreach ($tiles as [$label, $value, $link])
            <a @if($link) href="{{ $link }}" @endif class="rounded-2xl bg-white p-5 shadow-card {{ $link ? 'hover:shadow-luxe' : '' }}">
                <p class="text-xs uppercase tracking-wider text-nuit-400">{{ $label }}</p>
                <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ $value }}</p>
            </a>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.maintenance.equipment') }}" class="text-sm font-semibold text-terracotta-600 hover:underline">Équipements</a>
        <a href="{{ route('admin.maintenance.plans') }}" class="text-sm font-semibold text-terracotta-600 hover:underline">Plans préventifs</a>
        <a href="{{ route('admin.maintenance.ticket.create') }}" class="ml-auto rounded-full bg-terracotta-500 px-4 py-2 text-sm font-semibold text-white hover:bg-terracotta-600">+ Nouveau ticket</a>
    </div>

    <form method="GET" class="flex flex-wrap items-end gap-3 rounded-2xl bg-white p-4 shadow-card">
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Recherche
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Réf., titre" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Statut
            <select name="status" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="">Tous</option>
                @foreach ($statuses as $k => $label)<option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $label }}</option>@endforeach
            </select>
        </label>
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Priorité
            <select name="priority" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="">Toutes</option>
                @foreach ($priorities as $k => $label)<option value="{{ $k }}" @selected(($filters['priority'] ?? '') === $k)>{{ $label }}</option>@endforeach
            </select>
        </label>
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Type
            <select name="type" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="">Tous</option>
                <option value="corrective" @selected(($filters['type'] ?? '') === 'corrective')>Correctif</option>
                <option value="preventive" @selected(($filters['type'] ?? '') === 'preventive')>Préventif</option>
            </select>
        </label>
        <button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Filtrer</button>
        <a href="{{ route('admin.maintenance.tickets') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">Réinitialiser</a>
    </form>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Réf.</th><th class="px-4 py-3">Objet</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Priorité</th><th class="px-4 py-3">Technicien</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3">Coût</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody>
                @forelse ($tickets as $t)
                    <tr class="border-t border-sable-200">
                        <td class="px-4 py-3 font-mono text-xs">{{ $t->reference }}</td>
                        <td class="px-4 py-3">
                            <span class="font-medium text-nuit-900">{{ $t->title }}</span>
                            <span class="block text-xs text-nuit-400">{{ $t->equipment->name ?? ($t->room ? 'Ch. '.$t->room->number : 'Général') }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs">{{ $t->type === 'preventive' ? 'Préventif' : 'Correctif' }}</td>
                        <td class="px-4 py-3"><x-admin.badge :status="$t->priority" :label="$t->priorityLabel()" /></td>
                        <td class="px-4 py-3 text-xs text-nuit-600">{{ $t->technician->name ?? '—' }}</td>
                        <td class="px-4 py-3"><x-admin.badge :status="$t->status" :label="$t->statusLabel()" /></td>
                        <td class="px-4 py-3 text-xs">{{ $t->totalCost() ? money($t->totalCost()) : '—' }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.maintenance.ticket', $t) }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Ouvrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-nuit-400">Aucun ticket.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $tickets->links() }}</div>
</div>
@endsection
