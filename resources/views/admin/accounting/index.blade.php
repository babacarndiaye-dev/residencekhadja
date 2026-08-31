@extends('admin.layout')
@section('title', 'Comptabilité')

@section('content')
<div class="flex flex-col gap-6">

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-4 shadow-card">
        <div>
            <p class="text-xs uppercase tracking-wider text-nuit-400">Exercice en cours</p>
            <p class="font-display text-lg font-semibold text-nuit-900">
                {{ $fiscalYear->label }}
                <span class="ml-1 rounded-full px-2 py-0.5 text-xs font-semibold {{ $fiscalYear->isClosed() ? 'bg-nuit-100 text-nuit-700' : 'bg-emerald-100 text-emerald-800' }}">{{ $fiscalYear->statusLabel() }}</span>
            </p>
            <p class="text-xs text-nuit-400">{{ $fiscalYear->starts_on->format('d/m/Y') }} → {{ $fiscalYear->ends_on->format('d/m/Y') }} · {{ $entryCount }} écriture(s)</p>
        </div>
        <a href="{{ route('admin.accounting.fiscal_years') }}" class="rounded-full border border-nuit-200 px-4 py-1.5 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Exercices &amp; clôture</a>
    </div>

    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
        @php $tiles = [
            ['route' => 'admin.accounting.entries', 'label' => 'Écritures', 'sub' => 'Journal, saisie manuelle'],
            ['route' => 'admin.accounting.general_ledger', 'label' => 'Grand livre', 'sub' => 'Tous les comptes'],
            ['route' => 'admin.accounting.balance', 'label' => 'Balance générale', 'sub' => 'Débit / crédit / solde'],
            ['route' => 'admin.accounting.income_statement', 'label' => 'Compte de résultat', 'sub' => 'Produits − charges'],
            ['route' => 'admin.accounting.balance_sheet', 'label' => 'Bilan', 'sub' => 'Actif / passif'],
            ['route' => 'admin.accounting.vat', 'label' => 'TVA', 'sub' => 'Collectée / déductible'],
        ]; @endphp
        @foreach ($tiles as $t)
            <a href="{{ route($t['route']) }}" class="rounded-2xl bg-white p-4 shadow-card transition hover:-translate-y-0.5">
                <p class="font-semibold text-nuit-900">{{ $t['label'] }}</p>
                <p class="mt-0.5 text-xs text-nuit-400">{{ $t['sub'] }}</p>
            </a>
        @endforeach
        <a href="{{ route('admin.accounting.export') }}" class="rounded-2xl bg-nuit-900 p-4 text-white shadow-card transition hover:-translate-y-0.5">
            <p class="font-semibold">Export CSV</p>
            <p class="mt-0.5 text-xs text-white/60">Écritures détaillées</p>
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($accounts as $type => $group)
            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h2 class="mb-2 text-sm font-semibold uppercase tracking-wider text-nuit-500">{{ $types[$type] ?? $type }}</h2>
                <ul class="flex flex-col gap-1 text-sm">
                    @foreach ($group as $acc)
                        <li>
                            <a href="{{ route('admin.accounting.ledger', $acc) }}" class="flex justify-between hover:text-terracotta-600">
                                <span><span class="font-mono text-xs text-nuit-400">{{ $acc->code }}</span> {{ $acc->name }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>

    <section class="rounded-2xl bg-white p-5 shadow-card">
        <h2 class="mb-2 text-sm font-semibold uppercase tracking-wider text-nuit-500">Journaux</h2>
        <div class="flex flex-wrap gap-2 text-sm">
            @foreach ($journals as $code => $label)
                <a href="{{ route('admin.accounting.entries', ['journal' => $code]) }}" class="rounded-lg bg-sable-100 px-3 py-1.5 hover:bg-sable-200">{{ $code }} — {{ $label }}</a>
            @endforeach
        </div>
    </section>
</div>
@endsection
