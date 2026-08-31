@extends('admin.layout')
@section('title', 'Nouvelle demande d’achat')

@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('admin.purchases.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Achats</a>

    <form method="POST" action="{{ route('admin.purchases.store') }}"
          x-data="{ lines: [{ stock_item_id: '', quantity: 1, unit_price: 0 }],
                    items: {{ Illuminate\Support\Js::from($items->map(fn($i) => ['id' => $i->id, 'name' => $i->name, 'cost' => $i->avg_cost])) }},
                    get total() { return this.lines.reduce((s, l) => s + (l.quantity * l.unit_price), 0); } }"
          class="mt-4 flex flex-col gap-4 rounded-2xl bg-white p-5 shadow-card">
        @csrf

        <div class="grid gap-4 sm:grid-cols-3">
            <label class="text-sm font-semibold text-nuit-800">Fournisseur
                <select name="supplier_id" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                    @foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-nuit-800">Magasin destinataire
                <select name="warehouse_id" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                    @foreach ($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-nuit-800">Livraison souhaitée
                <input type="date" name="expected_on" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
            </label>
        </div>

        <div>
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-nuit-800">Lignes</h3>
                <button type="button" x-on:click="lines.push({ stock_item_id: '', quantity: 1, unit_price: 0 })" class="rounded-full border border-nuit-200 px-3 py-1 text-xs font-semibold text-nuit-700">+ Ligne</button>
            </div>
            <template x-for="(l, i) in lines" :key="i">
                <div class="mt-2 flex items-center gap-2">
                    <select :name="`lines[${i}][stock_item_id]`" x-model="l.stock_item_id"
                            x-on:change="l.unit_price = (items.find(x => x.id == l.stock_item_id) || {}).cost || 0"
                            required class="flex-1 rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                        <option value="">Article…</option>
                        <template x-for="it in items" :key="it.id"><option :value="it.id" x-text="it.name"></option></template>
                    </select>
                    <input type="number" step="0.001" :name="`lines[${i}][quantity]`" x-model.number="l.quantity" placeholder="Qté" required class="w-24 rounded-lg border border-nuit-200 px-2 py-2 text-sm">
                    <input type="number" :name="`lines[${i}][unit_price]`" x-model.number="l.unit_price" placeholder="PU" required class="w-28 rounded-lg border border-nuit-200 px-2 py-2 text-sm">
                    <button type="button" x-on:click="lines.splice(i, 1)" x-show="lines.length > 1" class="text-xs text-red-600">✕</button>
                </div>
            </template>
            <p class="mt-3 text-right text-sm text-nuit-600">Sous-total HT : <strong x-text="new Intl.NumberFormat('fr-FR').format(total) + ' FCFA'"></strong></p>
        </div>

        <label class="text-sm font-semibold text-nuit-800">Note
            <textarea name="note" rows="2" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal"></textarea>
        </label>

        <button class="rounded-full bg-terracotta-500 px-6 py-2.5 text-sm font-semibold text-white hover:bg-terracotta-600">Créer la demande</button>
    </form>
</div>
@endsection
