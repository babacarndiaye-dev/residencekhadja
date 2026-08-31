@extends('admin.layout')
@section('title', $item->exists ? 'Article — '.$item->name : 'Nouvel article')

@section('content')
<div class="mx-auto max-w-xl">
    <a href="{{ route('admin.stock.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Stocks</a>

    <form method="POST" action="{{ $item->exists ? route('admin.stock.item.update', $item) : route('admin.stock.item.store') }}"
          class="mt-4 flex flex-col gap-4 rounded-2xl bg-white p-5 shadow-card">
        @csrf
        @if ($item->exists) @method('PUT') @endif

        <label class="text-sm font-semibold text-nuit-800">Nom
            <input type="text" name="name" value="{{ old('name', $item->name) }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
        </label>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="text-sm font-semibold text-nuit-800">Catégorie
                <select name="stock_category_id" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                    @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(old('stock_category_id', $item->stock_category_id) == $c->id)>{{ $c->name }}</option>@endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-nuit-800">Unité
                <select name="unit" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                    @foreach ($units as $u)<option value="{{ $u }}" @selected(old('unit', $item->unit) === $u)>{{ $u }}</option>@endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-nuit-800">SKU
                <input type="text" name="sku" value="{{ old('sku', $item->sku) }}" placeholder="auto" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
            </label>
            <label class="text-sm font-semibold text-nuit-800">Seuil d’alerte
                <input type="number" step="0.001" name="min_qty" value="{{ old('min_qty', $item->min_qty) }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
            </label>
        </div>
        <label class="text-sm font-semibold text-nuit-800">Notes
            <textarea name="notes" rows="2" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">{{ old('notes', $item->notes) }}</textarea>
        </label>
        <label class="flex items-center gap-2 text-sm text-nuit-700"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-nuit-300 text-terracotta-500"> Actif</label>

        @if ($item->exists)
            <div class="rounded-lg bg-sable-100 p-3 text-xs text-nuit-600">
                CUMP : <strong>{{ money($item->avg_cost) }}</strong> · en stock :
                @foreach ($item->levels as $lvl)<span class="mr-2">{{ $lvl->warehouse->name }} {{ rtrim(rtrim(number_format($lvl->quantity, 3, ',', ' '), '0'), ',') }}</span>@endforeach
            </div>
        @endif

        <button class="rounded-full bg-terracotta-500 px-6 py-2.5 text-sm font-semibold text-white hover:bg-terracotta-600">Enregistrer</button>
    </form>
</div>
@endsection
