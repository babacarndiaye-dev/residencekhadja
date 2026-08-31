@extends('admin.layout')
@section('title', 'Rapports caisse')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-display text-lg font-semibold text-nuit-900">Rapports caisse</h1>
            <p class="text-sm text-nuit-500">{{ $from->translatedFormat('d M Y') }} → {{ $to->translatedFormat('d M Y') }}</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <select name="period" onchange="this.form.submit()" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                @foreach (['today' => "Aujourd'hui", 'yesterday' => 'Hier', '7d' => '7 jours', '30d' => '30 jours', 'mtd' => 'Mois en cours', '90d' => '90 jours'] as $k => $label)
                    <option value="{{ $k }}" @selected($period === $k)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- KPI --}}
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ([
            ['CA brut', money($sales['gross'])],
            ['Net (− remb.)', money($sales['net'])],
            ['Tickets', $sales['orders']],
            ['Panier moyen', money($sales['avg_check'])],
            ['Remises', money($sales['discounts'])],
        ] as [$label, $value])
            <div class="rounded-2xl bg-white p-4 shadow-card">
                <p class="text-xs font-semibold uppercase tracking-wider text-nuit-400">{{ $label }}</p>
                <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-sm font-semibold uppercase tracking-wider text-nuit-500">Par type de vente</h2>
            <table class="w-full text-sm">
                <tbody>
                    @forelse ($sales['by_type'] as $type => $row)
                        <tr class="border-t border-sable-200"><td class="py-2">{{ $saleTypes[$type] ?? $type }}</td><td class="py-2 text-right text-nuit-500">{{ $row['orders'] }} tickets</td><td class="py-2 text-right font-semibold">{{ money($row['total']) }}</td></tr>
                    @empty
                        <tr><td class="py-4 text-center text-nuit-400">—</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-sm font-semibold uppercase tracking-wider text-nuit-500">Par moyen de paiement</h2>
            <table class="w-full text-sm">
                <tbody>
                    @php $mLabels = ['especes' => 'Espèces', 'carte' => 'Carte', 'wave' => 'Wave', 'orange_money' => 'Orange Money', 'free_money' => 'Free Money', 'chambre' => 'Chambre']; @endphp
                    @forelse ($sales['by_method'] as $method => $amount)
                        <tr class="border-t border-sable-200"><td class="py-2">{{ $mLabels[$method] ?? $method }}</td><td class="py-2 text-right font-semibold">{{ money($amount) }}</td></tr>
                    @empty
                        <tr><td class="py-4 text-center text-nuit-400">—</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>

    <section class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <div class="flex items-center justify-between p-5 pb-0">
            <h2 class="font-display text-sm font-semibold uppercase tracking-wider text-nuit-500">Meilleures ventes &amp; marge</h2>
            <a href="{{ route('admin.bi.report', 'pos_bestsellers') }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Export CSV ↗</a>
        </div>
        <table class="mt-3 w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-5 py-3">Article</th><th class="px-5 py-3 text-right">Qté</th><th class="px-5 py-3 text-right">CA</th><th class="px-5 py-3 text-right">Coût</th><th class="px-5 py-3 text-right">Marge</th><th class="px-5 py-3 text-right">Marge %</th></tr>
            </thead>
            <tbody>
                @forelse ($bestsellers as $b)
                    <tr class="border-t border-sable-200">
                        <td class="px-5 py-2.5">{{ $b['name'] }}</td>
                        <td class="px-5 py-2.5 text-right">{{ $b['qty'] }}</td>
                        <td class="px-5 py-2.5 text-right">{{ money($b['revenue']) }}</td>
                        <td class="px-5 py-2.5 text-right text-nuit-500">{{ $b['cost'] ? money($b['cost']) : '—' }}</td>
                        <td class="px-5 py-2.5 text-right font-semibold {{ $b['margin'] >= 0 ? 'text-emerald-700' : 'text-terracotta-700' }}">{{ money($b['margin']) }}</td>
                        <td class="px-5 py-2.5 text-right">{{ $b['margin_pct'] === null ? '—' : $b['margin_pct'].' %' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-nuit-400">Aucune vente sur la période.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <div class="flex items-center justify-between p-5 pb-0">
            <h2 class="font-display text-sm font-semibold uppercase tracking-wider text-nuit-500">Performance serveurs</h2>
            <a href="{{ route('admin.bi.report', 'pos_servers') }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Export CSV ↗</a>
        </div>
        <table class="mt-3 w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-5 py-3">Serveur</th><th class="px-5 py-3 text-right">Tickets</th><th class="px-5 py-3 text-right">CA</th><th class="px-5 py-3 text-right">Remises</th><th class="px-5 py-3 text-right">Annul.</th><th class="px-5 py-3 text-right">Remb.</th></tr>
            </thead>
            <tbody>
                @forelse ($servers as $s)
                    <tr class="border-t border-sable-200">
                        <td class="px-5 py-2.5">{{ $s['server'] }}</td>
                        <td class="px-5 py-2.5 text-right">{{ $s['tickets'] }}</td>
                        <td class="px-5 py-2.5 text-right font-semibold">{{ money($s['revenue']) }}</td>
                        <td class="px-5 py-2.5 text-right text-nuit-500">{{ money($s['discounts']) }}</td>
                        <td class="px-5 py-2.5 text-right">{{ $s['cancelled'] }}</td>
                        <td class="px-5 py-2.5 text-right">{{ $s['refunded'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-nuit-400">Aucune vente attribuée à un serveur.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
@endsection
