<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ ($live ?? false) ? 'Rapport X' : 'Rapport Z' }} — {{ $session->account->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f4f5f3; font-family: ui-monospace, 'SF Mono', Menlo, monospace; color: #202628; }
        .sheet { width: 80mm; margin: 12px auto; background: #fff; padding: 6mm 5mm; }
        h1 { font-size: 14px; text-align: center; margin: 0; letter-spacing: .08em; }
        .muted { color: #555; font-size: 11px; }
        .center { text-align: center; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 6px; }
        td { padding: 2px 0; vertical-align: top; }
        .r { text-align: right; }
        .rule { border-top: 1px dashed #999; margin: 6px 0; }
        .tot { font-size: 14px; font-weight: 700; }
        .neg { color: #b23b3b; }
        .pos { color: #2f7d5d; }
        .no-print { text-align: center; margin: 10px; }
        button { border: 0; border-radius: 999px; background: #202628; color: #fff; padding: 8px 18px; font: inherit; }
        @media print { .no-print { display: none; } body { background: #fff; } .sheet { margin: 0; } }
    </style>
</head>
@php
    $mLabels = ['especes' => 'Espèces', 'carte' => 'Carte', 'wave' => 'Wave', 'orange_money' => 'Orange Money', 'free_money' => 'Free Money', 'chambre' => 'Chambre'];
    $tLabels = \App\Http\Controllers\Admin\PosController::SALE_TYPES;
@endphp
<body onload="window.print()">
    <div class="no-print"><button onclick="window.print()">Imprimer</button></div>
    <div class="sheet">
        <h1>{{ ($live ?? false) ? 'RAPPORT X' : 'RAPPORT Z' }}</h1>
        <p class="center muted">{{ config('hotel.name') }} · {{ $session->account->name }}</p>
        <div class="rule"></div>
        <p class="muted">
            Ouverte : {{ $session->opened_at->format('d/m/Y H:i') }} · {{ $session->openedBy->name ?? '—' }}<br>
            @if ($session->closed_at)Clôturée : {{ $session->closed_at->format('d/m/Y H:i') }} · {{ $session->closedBy->name ?? '—' }}<br>@endif
            Fond de caisse : {{ number_format($report['opening_float'], 0, ',', ' ') }}
        </p>

        <div class="rule"></div>
        <table>
            <tr><td>Tickets</td><td class="r">{{ $report['orders_count'] }}</td></tr>
            <tr><td>Ventes brutes</td><td class="r">{{ number_format($report['gross_sales'], 0, ',', ' ') }}</td></tr>
            @if ($report['discounts'])<tr><td>Remises accordées</td><td class="r">−{{ number_format($report['discounts'], 0, ',', ' ') }}</td></tr>@endif
            @if ($report['refunds'])<tr class="neg"><td>Remboursements</td><td class="r">−{{ number_format($report['refunds'], 0, ',', ' ') }}</td></tr>@endif
        </table>

        <div class="rule"></div>
        <p class="muted">RÈGLEMENTS PAR MOYEN</p>
        <table>
            @forelse ($report['by_method'] as $method => $amount)
                <tr><td>{{ $mLabels[$method] ?? $method }}</td><td class="r">{{ number_format($amount, 0, ',', ' ') }}</td></tr>
            @empty
                <tr><td class="muted">—</td><td></td></tr>
            @endforelse
        </table>

        <div class="rule"></div>
        <p class="muted">VENTES PAR TYPE</p>
        <table>
            @foreach ($report['by_type'] as $type => $amount)
                <tr><td>{{ $tLabels[$type] ?? $type }}</td><td class="r">{{ number_format($amount, 0, ',', ' ') }}</td></tr>
            @endforeach
        </table>

        <div class="rule"></div>
        <table>
            <tr class="tot"><td>ESPÈCES THÉORIQUES</td><td class="r">{{ number_format($report['cash_expected'], 0, ',', ' ') }}</td></tr>
            @if (! ($live ?? false))
                <tr><td>Espèces comptées</td><td class="r">{{ number_format($report['counted_amount'], 0, ',', ' ') }}</td></tr>
                <tr class="{{ $report['variance'] == 0 ? '' : ($report['variance'] > 0 ? 'pos' : 'neg') }}">
                    <td>Écart</td><td class="r">{{ $report['variance'] > 0 ? '+' : '' }}{{ number_format($report['variance'], 0, ',', ' ') }}</td>
                </tr>
            @endif
        </table>
        @if (! ($live ?? false) && $session->note)<p class="muted">Note : {{ $session->note }}</p>@endif

        @if ($session->closing_denominations)
            <div class="rule"></div>
            <p class="muted">COMPTAGE</p>
            <table>
                @foreach ($session->closing_denominations as $value => $count)
                    <tr><td>{{ number_format($value, 0, ',', ' ') }} × {{ $count }}</td><td class="r">{{ number_format($value * $count, 0, ',', ' ') }}</td></tr>
                @endforeach
            </table>
        @endif

        <p class="center muted" style="margin-top:10px;">{{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
