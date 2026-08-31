@extends('admin.layout')
@section('title', 'Décisionnel')

@section('content')
<div class="flex flex-col gap-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        @include('admin.bi._period', ['routeName' => 'admin.bi.dashboard'])
        <div class="flex gap-2">
            <a href="{{ route('admin.bi.reports', ['period' => $period]) }}" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Rapports</a>
            <a href="{{ route('admin.bi.schedules') }}" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Planifications</a>
        </div>
    </div>
    <p class="text-sm text-nuit-500">Période : {{ $from->format('d/m/Y') }} → {{ $to->format('d/m/Y') }} ({{ $o['occupancy']['days'] }} j)</p>

    @if ($alerts->isNotEmpty())
        <div class="rounded-2xl border border-terracotta-300 bg-terracotta-50 p-4 text-sm text-terracotta-800">
            <p class="font-semibold">KPI hors objectif</p>
            <ul class="mt-1 flex flex-wrap gap-x-6 gap-y-1">
                @foreach ($alerts as $a)
                    <li>{{ $a['label'] }} : <b>{{ $a['unit'] === 'FCFA' ? money($a['value']) : $a['value'].' '.$a['unit'] }}</b> (cible {{ $a['unit'] === 'FCFA' ? money($a['target']) : $a['target'].' '.$a['unit'] }})</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- KPI --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($o['kpi'] as $k)
            <div class="rounded-2xl bg-white p-5 shadow-card">
                <p class="text-xs uppercase tracking-wider text-nuit-400">{{ $k['label'] }}</p>
                <p class="mt-1 font-display text-2xl font-semibold {{ $k['good'] ? 'text-nuit-900' : 'text-terracotta-600' }}">
                    {{ $k['unit'] === 'FCFA' ? money($k['value']) : $k['value'].' '.$k['unit'] }}
                </p>
                <p class="text-xs text-nuit-400">cible {{ $k['unit'] === 'FCFA' ? money($k['target']) : $k['target'].' '.$k['unit'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Tendances 30 j --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach (['total_revenue' => 'Recettes', 'occupancy' => 'Occupation', 'adr' => 'ADR', 'covers' => 'Commandes'] as $key => $label)
            <div class="rounded-2xl bg-white p-4 shadow-card">
                <p class="text-xs font-semibold text-nuit-500">{{ $label }} · 30 j</p>
                <div class="mt-2">{!! $spark[$key] !!}</div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Revenus --}}
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Recettes & résultat</h2>
            <dl class="flex flex-col gap-1.5 text-sm">
                <div class="flex justify-between"><dt class="text-nuit-500">Hébergement</dt><dd>{{ money($o['revenue']['room_revenue']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-nuit-500">Restauration</dt><dd>{{ money($o['revenue']['fnb_revenue']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-nuit-500">Autres</dt><dd>{{ money($o['revenue']['other_revenue']) }}</dd></div>
                <div class="flex justify-between border-t border-sable-200 pt-1 font-semibold"><dt>Total recettes</dt><dd>{{ money($o['revenue']['total_income']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-nuit-500">Total dépenses</dt><dd>{{ money($o['revenue']['total_expense']) }}</dd></div>
                <div class="flex justify-between font-semibold {{ $o['revenue']['result'] >= 0 ? 'text-emerald-700' : 'text-terracotta-600' }}"><dt>Résultat</dt><dd>{{ money($o['revenue']['result']) }}</dd></div>
                <div class="flex justify-between border-t border-sable-200 pt-1"><dt class="text-nuit-500">Trésorerie</dt><dd>{{ money($o['treasury']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-nuit-500">Créances / Dettes</dt><dd>{{ money($o['ap_ar']['receivables']) }} / {{ money($o['ap_ar']['payables']) }}</dd></div>
            </dl>
        </section>

        {{-- Opérations --}}
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Opérations</h2>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-nuit-400">Chambres vendues</dt><dd class="font-semibold">{{ $o['occupancy']['rooms_sold'] }}</dd></div>
                <div><dt class="text-nuit-400">Séjour moyen</dt><dd class="font-semibold">{{ $o['pms']['avg_length_of_stay'] }} n</dd></div>
                <div><dt class="text-nuit-400">Délai résa moyen</dt><dd class="font-semibold">{{ $o['pms']['avg_lead_time'] }} j</dd></div>
                <div><dt class="text-nuit-400">Ménage fait</dt><dd class="font-semibold">{{ $o['operations']['hk_completion_rate'] }} %</dd></div>
                <div><dt class="text-nuit-400">Contrôle qualité</dt><dd class="font-semibold">{{ $o['operations']['qc_pass_rate'] }} %</dd></div>
                <div><dt class="text-nuit-400">Maintenance ouverte</dt><dd class="font-semibold {{ $o['operations']['maintenance_critical'] ? 'text-terracotta-600' : '' }}">{{ $o['operations']['maintenance_open'] }}</dd></div>
                <div><dt class="text-nuit-400">Valeur stock</dt><dd class="font-semibold">{{ money($o['stock']['stock_value']) }}</dd></div>
                <div><dt class="text-nuit-400">Articles sous seuil</dt><dd class="font-semibold {{ $o['stock']['items_below_threshold'] ? 'text-terracotta-600' : '' }}">{{ $o['stock']['items_below_threshold'] }}</dd></div>
            </dl>
        </section>

        {{-- CRM & MICE --}}
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">CRM & Commercial</h2>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-nuit-400">Nouveaux membres</dt><dd class="font-semibold">{{ $o['crm']['new_members'] }}</dd></div>
                <div><dt class="text-nuit-400">Clients fidèles (répétition)</dt><dd class="font-semibold">{{ $o['crm']['repeat_guest_rate'] }} %</dd></div>
                <div><dt class="text-nuit-400">Engagement points (passif)</dt><dd class="font-semibold">{{ money($o['crm']['points_liability']) }}</dd></div>
                <div><dt class="text-nuit-400">Affaires MICE créées</dt><dd class="font-semibold">{{ $o['events']['leads_created'] }}</dd></div>
                <div><dt class="text-nuit-400">Pipeline ouvert</dt><dd class="font-semibold">{{ money($o['events']['pipeline_open_value']) }}</dd></div>
                <div><dt class="text-nuit-400">CA événements confirmés</dt><dd class="font-semibold">{{ money($o['events']['confirmed_revenue']) }}</dd></div>
            </dl>
        </section>

        {{-- Paiements --}}
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Paiement en ligne</h2>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-nuit-400">Transactions</dt><dd class="font-semibold">{{ $o['payments']['intents'] }}</dd></div>
                <div><dt class="text-nuit-400">Taux de succès</dt><dd class="font-semibold">{{ $o['payments']['success_rate'] }} %</dd></div>
                <div><dt class="text-nuit-400">Encaissé en ligne</dt><dd class="font-semibold">{{ money($o['payments']['online_amount']) }}</dd></div>
                <div><dt class="text-nuit-400">Part du CA</dt><dd class="font-semibold">{{ $o['payments']['online_share'] }} %</dd></div>
                <div><dt class="text-nuit-400">Remboursé</dt><dd class="font-semibold">{{ money($o['payments']['refunded']) }}</dd></div>
                <div><dt class="text-nuit-400">Ticket moyen F&B</dt><dd class="font-semibold">{{ money($o['fnb']['avg_check']) }}</dd></div>
            </dl>
        </section>
    </div>

    @if ($o['fnb']['top_items']->isNotEmpty())
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Top ventes restaurant</h2>
            <table class="w-full text-sm">
                <tbody>
                    @foreach ($o['fnb']['top_items'] as $it)
                        <tr class="border-b border-sable-100">
                            <td class="py-1.5">{{ $it->name }}</td>
                            <td class="py-1.5 text-right text-nuit-500">{{ (int) $it->qty }} u</td>
                            <td class="py-1.5 text-right font-semibold">{{ money($it->revenue) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
</div>
@endsection
