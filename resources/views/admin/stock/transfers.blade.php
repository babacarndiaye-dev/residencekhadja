@extends('admin.layout')
@section('title', 'Transferts inter-magasin')

@php $canEdit = auth()->user()->hasRole('stock'); @endphp

@section('content')
<div class="flex flex-col gap-5">
    <a href="{{ route('admin.stock.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Stocks</a>

    @if ($canEdit)
        <form method="POST" action="{{ route('admin.stock.transfer.store') }}" class="grid gap-3 rounded-2xl bg-white p-5 shadow-card sm:grid-cols-4">
            @csrf
            <h2 class="font-display text-lg font-semibold text-nuit-900 sm:col-span-4">Nouveau transfert</h2>
            <select name="stock_item_id" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm sm:col-span-2">
                <option value="">Article…</option>
                @foreach ($items as $it)<option value="{{ $it->id }}">{{ $it->name }}</option>@endforeach
            </select>
            <input type="number" step="0.001" name="quantity" placeholder="Quantité" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <div></div>
            <select name="from_warehouse_id" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="">Depuis…</option>
                @foreach ($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
            </select>
            <select name="to_warehouse_id" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="">Vers…</option>
                @foreach ($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
            </select>
            <input type="text" name="note" placeholder="Note" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <button class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Transférer</button>
        </form>
    @endif

    <div class="flex flex-col gap-3">
        @forelse ($groups as $group => $lines)
            @php $out = $lines->firstWhere('direction', 'out'); $in = $lines->firstWhere('direction', 'in'); @endphp
            <div class="rounded-2xl bg-white p-4 text-sm shadow-card">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="font-medium text-nuit-900">{{ $out?->item->name }}</span>
                    <span class="text-nuit-500">{{ $out?->warehouse->name }} → {{ $in?->warehouse->name }}</span>
                    <span class="font-semibold">{{ rtrim(rtrim(number_format($out?->quantity ?? 0, 3, ',', ' '), '0'), ',') }} {{ $out?->item->unit }}</span>
                    <span class="text-xs text-nuit-400">{{ $out?->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        @empty
            <p class="rounded-2xl bg-white p-8 text-center text-nuit-400 shadow-card">Aucun transfert.</p>
        @endforelse
    </div>
</div>
@endsection
