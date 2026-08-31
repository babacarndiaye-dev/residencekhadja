@extends('admin.layout')
@section('title', 'Fournisseurs & magasins')

@section('content')
<div class="flex flex-col gap-6">
    <a href="{{ route('admin.stock.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Stocks</a>

    <section>
        <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Fournisseurs</h2>
        <form method="POST" action="{{ route('admin.stock.suppliers.store') }}" class="mb-4 grid gap-3 rounded-2xl bg-white p-4 shadow-card sm:grid-cols-4">
            @csrf
            <input type="text" name="name" placeholder="Nom" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <input type="text" name="contact_name" placeholder="Contact" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <input type="text" name="phone" placeholder="Téléphone" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <div class="flex gap-2">
                <input type="text" name="payment_terms" placeholder="Délai" class="flex-1 rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <button class="rounded-full bg-nuit-900 px-4 py-2 text-xs font-semibold text-white">Ajouter</button>
            </div>
        </form>
        <div class="flex flex-col gap-2">
            @foreach ($suppliers as $s)
                <form method="POST" action="{{ route('admin.stock.suppliers.update', $s) }}" class="grid items-center gap-2 rounded-2xl bg-white p-3 shadow-card sm:grid-cols-[1.4fr_1fr_1fr_auto_auto]">
                    @csrf @method('PUT')
                    <input type="text" name="name" value="{{ $s->name }}" class="rounded-lg border border-nuit-200 px-3 py-1.5 text-sm font-medium">
                    <input type="text" name="phone" value="{{ $s->phone }}" placeholder="Téléphone" class="rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">
                    <input type="text" name="payment_terms" value="{{ $s->payment_terms }}" placeholder="Délai" class="rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">
                    <span class="text-xs text-nuit-400">{{ $s->purchase_orders_count }} cmd · dû {{ money($s->outstanding()) }}</span>
                    <div class="flex items-center gap-2">
                        <label class="flex items-center gap-1 text-xs text-nuit-600"><input type="checkbox" name="is_active" value="1" @checked($s->is_active) class="rounded border-nuit-300 text-terracotta-500"> Actif</label>
                        <button class="rounded-full bg-nuit-900 px-3 py-1.5 text-xs font-semibold text-white">OK</button>
                    </div>
                    <input type="hidden" name="contact_name" value="{{ $s->contact_name }}">
                </form>
            @endforeach
        </div>
    </section>

    <section>
        <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Magasins</h2>
        <form method="POST" action="{{ route('admin.stock.warehouses.store') }}" class="mb-3 grid gap-3 rounded-2xl bg-white p-4 shadow-card sm:grid-cols-4">
            @csrf
            <input type="text" name="name" placeholder="Nom" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <input type="text" name="code" placeholder="Code" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <select name="type" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                @foreach ($warehouseTypes as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
            </select>
            <button class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Créer</button>
        </form>
        <div class="flex flex-wrap gap-2">
            @foreach ($warehouses as $w)
                <span class="rounded-xl bg-white px-4 py-2 text-sm shadow-card">{{ $w->name }} <span class="text-xs text-nuit-400">· {{ $w->typeLabel() }}</span></span>
            @endforeach
        </div>
    </section>
</div>
@endsection
