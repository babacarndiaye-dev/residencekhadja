<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Ticket {{ $order->reference }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f4f5f3; font-family: ui-monospace, 'SF Mono', Menlo, monospace; color: #202628; }
        .ticket { width: 80mm; margin: 12px auto; background: #fff; padding: 6mm 5mm; }
        h1 { font-size: 13px; text-align: center; margin: 0 0 2px; }
        .muted { color: #555; font-size: 11px; }
        .center { text-align: center; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 8px; }
        td { padding: 2px 0; vertical-align: top; }
        .r { text-align: right; }
        .rule { border-top: 1px dashed #999; margin: 6px 0; }
        .tot { font-size: 15px; font-weight: 700; }
        .no-print { text-align: center; margin: 10px; }
        button { border: 0; border-radius: 999px; background: #202628; color: #fff; padding: 8px 18px; font: inherit; }
        @media print { .no-print { display: none; } body { background: #fff; } .ticket { margin: 0; } }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print"><button onclick="window.print()">Imprimer</button></div>
    <div class="ticket">
        <h1>{{ config('hotel.name') }}</h1>
        <p class="center muted">{{ $order->venue->name ?? 'Restaurant' }} · {{ \App\Http\Controllers\Admin\PosController::SALE_TYPES[$order->sale_type] ?? $order->sale_type }}</p>
        <div class="rule"></div>
        <p class="muted">
            Ticket : <strong>{{ $order->reference }}</strong><br>
            {{ $order->created_at->format('d/m/Y H:i') }}
            @if ($order->table_label) · Table {{ $order->table_label }} @endif
            @if ($order->guest_name) · {{ $order->guest_name }} @endif<br>
            Caissier : {{ $order->cashier->name ?? '—' }}
            @if ($order->server) · Serveur : {{ $order->server->name }} @endif
        </p>
        <table>
            @foreach ($order->items as $it)
                <tr>
                    <td>{{ $it->quantity }}× {{ $it->name }}
                        @if ($it->options)<br><span class="muted">{{ collect($it->options)->pluck('name')->join(', ') }}</span>@endif
                    </td>
                    <td class="r">{{ number_format($it->line_total, 0, ',', ' ') }}</td>
                </tr>
            @endforeach
        </table>
        <div class="rule"></div>
        <table>
            <tr><td>Sous-total</td><td class="r">{{ number_format($order->subtotal, 0, ',', ' ') }}</td></tr>
            @if ($order->discount)<tr><td>Remise</td><td class="r">−{{ number_format($order->discount, 0, ',', ' ') }}</td></tr>@endif
            @if ($order->service_charge)<tr><td>Service</td><td class="r">{{ number_format($order->service_charge, 0, ',', ' ') }}</td></tr>@endif
            @if ($order->tax)<tr><td>TVA</td><td class="r">{{ number_format($order->tax, 0, ',', ' ') }}</td></tr>@endif
            <tr class="tot"><td>TOTAL</td><td class="r">{{ number_format($order->total, 0, ',', ' ') }} FCFA</td></tr>
        </table>
        <div class="rule"></div>
        @php $mLabels = ['especes' => 'Espèces', 'carte' => 'Carte bancaire', 'wave' => 'Wave', 'orange_money' => 'Orange Money', 'free_money' => 'Free Money', 'chambre' => 'Chambre']; @endphp
        <table>
            @forelse ($order->payments as $p)
                <tr>
                    <td>{{ $p->amount < 0 ? 'Remboursement' : 'Réglé' }} · {{ $mLabels[$p->method] ?? $p->method }}
                        @if ($p->method === 'chambre' && $order->room) (Ch. {{ $order->room->number }})@endif
                    </td>
                    <td class="r">{{ number_format($p->amount, 0, ',', ' ') }}</td>
                </tr>
                @if ($p->method === 'especes' && $p->tendered)
                    <tr><td class="muted">Reçu {{ number_format($p->tendered, 0, ',', ' ') }} · Rendu {{ number_format(max(0, $p->tendered - $p->amount), 0, ',', ' ') }}</td><td></td></tr>
                @endif
            @empty
                <tr><td class="muted">À régler</td><td></td></tr>
            @endforelse
        </table>
        @if ($order->isRefunded())<p class="muted">Remboursé le {{ $order->refunded_at->format('d/m/Y H:i') }} — {{ $order->refund_reason }}</p>@endif
        <p class="center muted" style="margin-top:10px;">Merci de votre visite !</p>
    </div>
</body>
</html>
