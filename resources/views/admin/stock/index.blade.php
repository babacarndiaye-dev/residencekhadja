@extends('admin.layout')
@section('title', 'Stocks')

@php $canEdit = auth()->user()->hasRole('stock'); @endphp

@section('content')
<div class="flex flex-col gap-5">

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Valeur du stock</p>
            <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ money($totalValue) }}</p>
        </div>
        <a href="{{ route('admin.stock.index', ['ruptures' => 1]) }}" class="rounded-2xl bg-white p-5 shadow-card hover:shadow-luxe">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Sous le seuil</p>
            <p class="mt-1 font-display text-2xl font-semibold {{ $lowCount ? 'text-terracotta-600' : 'text-nuit-900' }}">{{ $lowCount }}</p>
        </a>
        <div class="flex flex-wrap items-center gap-2 rounded-2xl bg-white p-5 shadow-card">
            <a href="{{ route('admin.stock.movements') }}" class="rounded-full border border-nuit-200 px-3 py-1.5 text-xs font-semibold text-nuit-700 hover:border-terracotta-400">Mouvements</a>
            <a href="{{ route('admin.stock.transfers') }}" class="rounded-full border border-nuit-200 px-3 py-1.5 text-xs font-semibold text-nuit-700 hover:border-terracotta-400">Transferts</a>
            <a href="{{ route('admin.stock.inventory.index') }}" class="rounded-full border border-nuit-200 px-3 py-1.5 text-xs font-semibold text-nuit-700 hover:border-terracotta-400">Inventaires</a>
            @if ($canEdit)<a href="{{ route('admin.stock.suppliers') }}" class="rounded-full border border-nuit-200 px-3 py-1.5 text-xs font-semibold text-nuit-700 hover:border-terracotta-400">Fournisseurs</a>@endif
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.stock.index') }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-nuit-900 text-white' => ! $activeCategory && ! $onlyLow, 'bg-white text-nuit-700 shadow-card' => $activeCategory || $onlyLow])>Tous</a>
        @foreach ($categories as $c)
            <a href="{{ route('admin.stock.index', ['categorie' => $c->id]) }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-nuit-900 text-white' => $activeCategory == $c->id, 'bg-white text-nuit-700 shadow-card' => $activeCategory != $c->id])>{{ $c->name }}</a>
        @endforeach
        @if ($canEdit)<a href="{{ route('admin.stock.item.create') }}" class="ml-auto rounded-full bg-terracotta-500 px-4 py-1.5 text-sm font-semibold text-white hover:bg-terracotta-600">+ Article</a>@endif
    </div>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Article</th><th class="px-4 py-3">Catégorie</th><th class="px-4 py-3">En stock</th><th class="px-4 py-3">Seuil</th><th class="px-4 py-3">CUMP</th><th class="px-4 py-3">Valeur</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr class="border-t border-sable-200 {{ $item->isBelowThreshold() ? 'bg-terracotta-50/50' : '' }}">
                        <td class="px-4 py-3">
                            <span class="font-medium text-nuit-900">{{ $item->name }}</span>
                            <span class="block text-xs text-nuit-400">{{ $item->sku }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-nuit-500">{{ $item->category->name }}</td>
                        <td class="px-4 py-3">
                            <span class="font-semibold {{ $item->isBelowThreshold() ? 'text-terracotta-600' : 'text-nuit-900' }}">{{ rtrim(rtrim(number_format($item->onHand(), 3, ',', ' '), '0'), ',') }}</span>
                            <span class="text-xs text-nuit-400"> {{ $item->unit }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-nuit-500">{{ rtrim(rtrim(number_format($item->min_qty, 3, ',', ' '), '0'), ',') }}</td>
                        <td class="px-4 py-3 text-xs">{{ money($item->avg_cost) }}</td>
                        <td class="px-4 py-3">{{ money($item->stockValue()) }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($canEdit)<a href="{{ route('admin.stock.item.edit', $item) }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Éditer</a>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-nuit-400">Aucun article.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
