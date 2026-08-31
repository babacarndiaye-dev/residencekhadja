@extends('admin.layout')
@section('title', 'Nouveau devis')

@section('content')
@php
    $existing = $quote->exists ? $quote->items->map(fn ($i) => [
        'category' => $i->category, 'label' => $i->label, 'quantity' => (float) $i->quantity,
        'unit' => $i->unit, 'unit_price' => (int) $i->unit_price,
    ])->values() : collect();
@endphp
<div class="flex flex-col gap-5"
     x-data="quoteBuilder({
        items: {{ $existing->toJson() }},
        pax: {{ (int) old('pax', $quote->pax) }},
        discount: {{ (int) old('discount_amount', $quote->discount_amount) }},
        taxRate: {{ (float) old('tax_rate', $quote->tax_rate) }},
        depositRate: {{ (float) old('deposit_rate', $quote->deposit_rate) }},
        catering: {{ \Illuminate\Support\Js::from($catering) }},
        equipment: {{ \Illuminate\Support\Js::from($equipment) }},
     })">

    <a href="{{ $lead ? route('admin.events.leads.show', $lead) : route('admin.events.pipeline') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Retour</a>

    <form method="POST" action="{{ $quote->exists ? route('admin.events.quotes.update', $quote) : route('admin.events.quotes.store') }}" class="flex flex-col gap-5">
        @csrf
        @if ($quote->exists) @method('PUT') @endif
        @if ($lead)<input type="hidden" name="event_lead_id" value="{{ $lead->id }}">@endif

        <div class="grid gap-4 rounded-2xl bg-white p-5 shadow-card sm:grid-cols-4">
            <label class="text-xs font-semibold text-nuit-500 sm:col-span-2">Intitulé du devis *
                <input name="title" value="{{ old('title', $quote->title) }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            </label>
            <label class="text-xs font-semibold text-nuit-500">Participants
                <input type="number" name="pax" min="0" x-model.number="pax" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            </label>
            <label class="text-xs font-semibold text-nuit-500">Validité
                <input type="date" name="valid_until" value="{{ old('valid_until', optional($quote->valid_until)->toDateString()) }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            </label>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-card">
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <h3 class="mr-auto font-display text-lg font-semibold text-nuit-900">Lignes du devis</h3>
                <button type="button" x-on:click="addRow()" class="rounded-full border border-nuit-200 px-3 py-1.5 text-xs font-semibold text-nuit-700 hover:border-terracotta-400">+ Ligne libre</button>
                <select x-on:change="addCatering($event.target.value); $event.target.value=''" class="rounded-lg border border-nuit-200 px-2 py-1.5 text-xs">
                    <option value="">+ Forfait restauration…</option>
                    @foreach ($catering as $p)<option value="{{ $p['code'] }}">{{ $p['name'] }} ({{ number_format($p['per_pax_price'], 0, ',', ' ') }}/pax)</option>@endforeach
                </select>
                <select x-on:change="addEquipment($event.target.value); $event.target.value=''" class="rounded-lg border border-nuit-200 px-2 py-1.5 text-xs">
                    <option value="">+ Matériel technique…</option>
                    @foreach ($equipment as $e)<option value="{{ $e['code'] }}">{{ $e['name'] }} ({{ number_format($e['price'], 0, ',', ' ') }}/{{ $e['unit'] }})</option>@endforeach
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wider text-nuit-400">
                        <tr><th class="py-2">Catégorie</th><th>Désignation</th><th class="w-20">Qté</th><th class="w-20">Unité</th><th class="w-32">P.U. (FCFA)</th><th class="w-32 text-right">Total</th><th></th></tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, i) in items" :key="i">
                            <tr class="border-t border-sable-100">
                                <td class="py-1.5 pr-2">
                                    <select :name="`items[${i}][category]`" x-model="row.category" class="w-full rounded-lg border border-nuit-200 px-2 py-1.5 text-xs">
                                        @foreach ($categories as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                                    </select>
                                </td>
                                <td class="py-1.5 pr-2"><input :name="`items[${i}][label]`" x-model="row.label" class="w-full rounded-lg border border-nuit-200 px-2 py-1.5 text-xs"></td>
                                <td class="py-1.5 pr-2"><input type="number" step="0.5" min="0" :name="`items[${i}][quantity]`" x-model.number="row.quantity" class="w-full rounded-lg border border-nuit-200 px-2 py-1.5 text-xs"></td>
                                <td class="py-1.5 pr-2"><input :name="`items[${i}][unit]`" x-model="row.unit" class="w-full rounded-lg border border-nuit-200 px-2 py-1.5 text-xs"></td>
                                <td class="py-1.5 pr-2"><input type="number" min="0" :name="`items[${i}][unit_price]`" x-model.number="row.unit_price" class="w-full rounded-lg border border-nuit-200 px-2 py-1.5 text-xs"></td>
                                <td class="py-1.5 text-right font-medium" x-text="fmt(row.quantity * row.unit_price)"></td>
                                <td class="py-1.5 text-right"><button type="button" x-on:click="items.splice(i,1)" class="text-xs text-terracotta-600">✕</button></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-2 text-sm">
                    <label class="flex items-center justify-between">Remise (FCFA)
                        <input type="number" name="discount_amount" min="0" x-model.number="discount" class="w-40 rounded-lg border border-nuit-200 px-2 py-1 text-sm">
                    </label>
                    <label class="flex items-center justify-between">TVA
                        <input type="number" step="0.01" min="0" max="1" name="tax_rate" x-model.number="taxRate" class="w-40 rounded-lg border border-nuit-200 px-2 py-1 text-sm">
                    </label>
                    <label class="flex items-center justify-between">Acompte (part)
                        <input type="number" step="0.05" min="0" max="1" name="deposit_rate" x-model.number="depositRate" class="w-40 rounded-lg border border-nuit-200 px-2 py-1 text-sm">
                    </label>
                </div>
                <dl class="flex flex-col gap-1 rounded-xl bg-sable-100 p-4 text-sm">
                    <div class="flex justify-between"><dt class="text-nuit-500">Sous-total</dt><dd x-text="fmt(subtotal())"></dd></div>
                    <div class="flex justify-between"><dt class="text-nuit-500">Remise</dt><dd x-text="'- ' + fmt(Math.min(discount, subtotal()))"></dd></div>
                    <div class="flex justify-between"><dt class="text-nuit-500">TVA</dt><dd x-text="fmt(tax())"></dd></div>
                    <div class="flex justify-between border-t border-sable-300 pt-1 font-semibold text-nuit-900"><dt>Total</dt><dd x-text="fmt(total())"></dd></div>
                    <div class="flex justify-between text-terracotta-700"><dt>Acompte</dt><dd x-text="fmt(Math.round(total() * depositRate))"></dd></div>
                </dl>
            </div>
        </div>

        <label class="text-xs font-semibold text-nuit-500">Conditions / mentions
            <textarea name="terms" rows="3" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">{{ old('terms', $quote->terms) }}</textarea>
        </label>

        <div><button class="rounded-full bg-nuit-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-nuit-800">{{ $quote->exists ? 'Enregistrer le devis' : 'Créer le devis' }}</button></div>
    </form>
</div>

<script>
    function quoteBuilder(cfg) {
        return {
            items: cfg.items.length ? cfg.items : [],
            pax: cfg.pax || 0,
            discount: cfg.discount || 0,
            taxRate: cfg.taxRate ?? 0.18,
            depositRate: cfg.depositRate ?? 0.30,
            catering: cfg.catering, equipment: cfg.equipment,
            addRow() { this.items.push({ category: 'divers', label: '', quantity: 1, unit: '', unit_price: 0 }); },
            addCatering(code) {
                const p = this.catering.find(x => x.code === code); if (!p) return;
                this.items.push({ category: p.category, label: p.name, quantity: this.pax || 1, unit: 'pax', unit_price: p.per_pax_price });
            },
            addEquipment(code) {
                const e = this.equipment.find(x => x.code === code); if (!e) return;
                this.items.push({ category: 'technique', label: e.name, quantity: 1, unit: e.unit, unit_price: e.price });
            },
            subtotal() { return this.items.reduce((s, r) => s + Math.round((r.quantity || 0) * (r.unit_price || 0)), 0); },
            tax() { return Math.round(Math.max(0, this.subtotal() - Math.min(this.discount, this.subtotal())) * this.taxRate); },
            total() { return Math.max(0, this.subtotal() - Math.min(this.discount, this.subtotal())) + this.tax(); },
            fmt(n) { return new Intl.NumberFormat('fr-FR').format(Math.round(n || 0)) + ' FCFA'; },
        };
    }
</script>
@endsection
