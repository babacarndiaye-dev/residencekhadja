@extends('admin.layout')
@section('title', 'Mouvements de stock')

@php $canEdit = auth()->user()->hasRole('stock'); @endphp

@section('content')
<div class="flex flex-col gap-5">
    <a href="{{ route('admin.stock.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Stocks</a>

    @if ($canEdit)
        <form method="POST" action="{{ route('admin.stock.movement.store') }}" class="grid gap-3 rounded-2xl bg-white p-5 shadow-card sm:grid-cols-3">
            @csrf
            <h2 class="font-display text-lg font-semibold text-nuit-900 sm:col-span-3">Entrée / sortie</h2>
            <select name="stock_item_id" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="">Article…</option>
                @foreach ($items as $it)<option value="{{ $it->id }}">{{ $it->name }}</option>@endforeach
            </select>
            <select name="warehouse_id" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="">Magasin…</option>
                @foreach ($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
            </select>
            <select name="direction" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="in">Entrée</option>
                <option value="out">Sortie</option>
            </select>
            <select name="reason" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                @foreach ($reasons as $k => $label)<option value="{{ $k }}" @selected($k==='consumption')>{{ $label }}</option>@endforeach
            </select>
            <input type="number" step="0.001" name="quantity" placeholder="Quantité" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <input type="number" name="unit_cost" placeholder="Coût unitaire (entrée)" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <input type="text" name="reference" placeholder="Référence" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm sm:col-span-2">
            <button class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Enregistrer</button>
        </form>
    @endif

    <form method="GET" class="flex flex-wrap gap-3 rounded-2xl bg-white p-4 shadow-card">
        <select name="item" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <option value="">Tous les articles</option>
            @foreach ($items as $it)<option value="{{ $it->id }}" @selected(($filters['item'] ?? '') == $it->id)>{{ $it->name }}</option>@endforeach
        </select>
        <select name="warehouse" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <option value="">Tous magasins</option>
            @foreach ($warehouses as $w)<option value="{{ $w->id }}" @selected(($filters['warehouse'] ?? '') == $w->id)>{{ $w->name }}</option>@endforeach
        </select>
        <select name="direction" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <option value="">Sens</option>
            <option value="in" @selected(($filters['direction'] ?? '') === 'in')>Entrées</option>
            <option value="out" @selected(($filters['direction'] ?? '') === 'out')>Sorties</option>
        </select>
        <button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Filtrer</button>
    </form>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Article</th><th class="px-4 py-3">Magasin</th><th class="px-4 py-3">Sens</th><th class="px-4 py-3">Qté</th><th class="px-4 py-3">Motif</th><th class="px-4 py-3">Par</th></tr>
            </thead>
            <tbody>
                @forelse ($movements as $m)
                    <tr class="border-t border-sable-200">
                        <td class="px-4 py-2.5 text-xs text-nuit-500">{{ $m->created_at->format('d/m H:i') }}</td>
                        <td class="px-4 py-2.5">{{ $m->item->name }}</td>
                        <td class="px-4 py-2.5 text-xs text-nuit-500">{{ $m->warehouse->name }}</td>
                        <td class="px-4 py-2.5">
                            <span class="{{ $m->direction === 'in' ? 'text-emerald-700' : 'text-terracotta-700' }} font-semibold">{{ $m->direction === 'in' ? '+' : '−' }}</span>
                        </td>
                        <td class="px-4 py-2.5">{{ rtrim(rtrim(number_format($m->quantity, 3, ',', ' '), '0'), ',') }} {{ $m->item->unit }}</td>
                        <td class="px-4 py-2.5 text-xs text-nuit-500">{{ $m->reasonLabel() }}@if($m->reference) · {{ $m->reference }}@endif</td>
                        <td class="px-4 py-2.5 text-xs text-nuit-400">{{ $m->user->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-nuit-400">Aucun mouvement.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $movements->links() }}</div>
</div>
@endsection
