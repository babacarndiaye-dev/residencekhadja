@extends('admin.layout')
@section('title', 'Codes promo')

@section('content')
<div class="flex flex-col gap-6">

    <section class="rounded-2xl bg-white p-5 shadow-card">
        <h2 class="mb-4 font-display text-lg font-semibold text-nuit-900">Nouveau code promo</h2>
        <form method="POST" action="{{ route('admin.crm.promos.store') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @csrf
            <label class="text-xs font-semibold text-nuit-500">Code
                <input name="code" required placeholder="ANNIV15" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm uppercase">
            </label>
            <label class="text-xs font-semibold text-nuit-500">Type
                <select name="type" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    <option value="percent">Pourcentage (%)</option>
                    <option value="amount">Montant (FCFA)</option>
                </select>
            </label>
            <label class="text-xs font-semibold text-nuit-500">Valeur
                <input type="number" name="value" min="1" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            </label>
            <label class="text-xs font-semibold text-nuit-500 sm:col-span-2">Libellé (affiché au devis)
                <input name="label" required placeholder="Offre anniversaire -15 %" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            </label>
            <label class="text-xs font-semibold text-nuit-500">Utilisations max
                <input type="number" name="max_redemptions" min="1" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            </label>
            <label class="text-xs font-semibold text-nuit-500">Début
                <input type="date" name="starts_on" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            </label>
            <label class="text-xs font-semibold text-nuit-500">Fin
                <input type="date" name="ends_on" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            </label>
            <div class="sm:col-span-2 lg:col-span-3">
                <button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white hover:bg-nuit-800">Créer le code</button>
            </div>
        </form>
        <p class="mt-2 text-xs text-nuit-400">Les codes actifs sont immédiatement utilisables dans le tunnel de réservation.</p>
    </section>

    <section class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Code</th><th class="px-4 py-3">Remise</th><th class="px-4 py-3">Validité</th><th class="px-4 py-3">Utilisations</th><th class="px-4 py-3">Campagnes</th><th class="px-4 py-3">État</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($promos as $p)
                    <tr class="border-t border-sable-200">
                        <td class="px-4 py-3 font-mono font-semibold">{{ $p->code }}<span class="block text-xs font-sans font-normal text-nuit-400">{{ $p->label }}</span></td>
                        <td class="px-4 py-3">{{ $p->type === 'percent' ? $p->value.' %' : money($p->value) }}</td>
                        <td class="px-4 py-3 text-xs text-nuit-500">
                            {{ optional($p->starts_on)->format('d/m/y') ?: '—' }} → {{ optional($p->ends_on)->format('d/m/y') ?: '∞' }}
                        </td>
                        <td class="px-4 py-3">{{ $p->redeemed_count }}@if($p->max_redemptions) / {{ $p->max_redemptions }}@endif</td>
                        <td class="px-4 py-3">{{ $p->campaigns_count }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $p->isRedeemable() ? 'bg-emerald-100 text-emerald-800' : 'bg-sable-200 text-nuit-500' }}">
                                {{ $p->active ? ($p->isRedeemable() ? 'actif' : 'expiré') : 'inactif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.crm.promos.toggle', $p) }}">
                                @csrf
                                <button class="text-xs font-semibold text-terracotta-600 hover:underline">{{ $p->active ? 'Désactiver' : 'Activer' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-nuit-400">Aucun code promo.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
@endsection
