<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Facture {{ $order->invoice_number }}</title>
    @vite(['resources/css/app.css'])
    <style>@media print { .no-print { display: none !important; } body { background: #fff; } }</style>
</head>
@php
    $mLabels = ['especes' => 'Espèces', 'carte' => 'Carte bancaire', 'wave' => 'Wave', 'orange_money' => 'Orange Money', 'free_money' => 'Free Money', 'chambre' => 'Chambre'];
    $tLabels = \App\Http\Controllers\Admin\PosController::SALE_TYPES;
    $guest = $order->reservation?->guest;
@endphp
<body class="bg-sable-100 p-6 text-ink">
<div class="mx-auto max-w-2xl">
    <div class="no-print mb-4 flex justify-between">
        <a href="{{ route('admin.orders.show', $order) }}" class="text-sm text-nuit-600 hover:text-terracotta-600">← Retour</a>
        <button onclick="window.print()" class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Imprimer / PDF</button>
    </div>

    <div class="rounded-2xl bg-white p-8 shadow-card">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ \App\Support\Branding::logo() }}" alt="" width="44" height="44" class="h-11 w-11">
                <div>
                    <p class="font-display text-lg font-semibold text-nuit-900">{{ $hotel->name }}</p>
                    <p class="text-xs text-nuit-500">{{ $hotel->address }} · {{ $hotel->city }}</p>
                    <p class="text-xs text-nuit-500">{{ $hotel->phone }} · {{ $hotel->email }}</p>
                </div>
            </div>
            <div class="text-right text-sm">
                <p class="font-display text-xl font-semibold text-nuit-900">FACTURE</p>
                <p class="text-nuit-600">{{ $order->invoice_number }}</p>
                <p class="text-xs text-nuit-400">{{ ($order->invoiced_at ?? now())->format('d/m/Y') }}</p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-xs uppercase tracking-wider text-nuit-400">Client</p>
                <p class="font-medium text-nuit-900">{{ $guest ? $guest->fullName() : ($order->guest_name ?: 'Client comptoir') }}</p>
                @if ($guest?->email)<p class="text-nuit-600">{{ $guest->email }}</p>@endif
                @if ($order->room)<p class="text-nuit-600">Chambre {{ $order->room->number }}</p>@endif
            </div>
            <div class="text-right">
                <p class="text-xs uppercase tracking-wider text-nuit-400">Prestation</p>
                <p class="text-nuit-900">{{ $order->venue->name ?? 'Restaurant' }}</p>
                <p class="text-nuit-600">{{ $tLabels[$order->sale_type] ?? $order->sale_type }}</p>
                <p class="text-nuit-600">Ticket {{ $order->reference }}{{ $order->table_label ? ' · '.$order->table_label : '' }}</p>
            </div>
        </div>

        <table class="mt-6 w-full text-sm">
            <thead class="border-b border-nuit-200 text-left text-xs uppercase tracking-wider text-nuit-400">
                <tr><th class="py-2">Désignation</th><th class="py-2 text-right">Qté</th><th class="py-2 text-right">Montant</th></tr>
            </thead>
            <tbody class="text-nuit-800">
                @foreach ($order->items as $it)
                    <tr class="border-b border-sable-200">
                        <td class="py-2">{{ $it->name }}
                            @if ($it->options)<span class="block text-xs text-nuit-400">{{ collect($it->options)->pluck('name')->join(', ') }}</span>@endif
                        </td>
                        <td class="py-2 text-right">{{ $it->quantity }}</td>
                        <td class="py-2 text-right">{{ money($it->line_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr><td class="py-1" colspan="2">Sous-total</td><td class="py-1 text-right">{{ money($order->subtotal) }}</td></tr>
                @if ($order->discount)<tr class="text-terracotta-700"><td class="py-1" colspan="2">Remise</td><td class="py-1 text-right">−{{ money($order->discount) }}</td></tr>@endif
                @if ($order->service_charge)<tr><td class="py-1" colspan="2">Service</td><td class="py-1 text-right">{{ money($order->service_charge) }}</td></tr>@endif
                <tr><td class="py-1" colspan="2">TVA</td><td class="py-1 text-right">{{ money($order->tax) }}</td></tr>
                <tr class="border-t-2 border-nuit-900 font-semibold text-nuit-900"><td class="py-2" colspan="2">Total TTC</td><td class="py-2 text-right">{{ money($order->total) }}</td></tr>
            </tfoot>
        </table>

        <div class="mt-4 text-sm text-nuit-600">
            @forelse ($order->payments as $p)
                <p>{{ $p->amount < 0 ? 'Remboursement' : 'Réglé' }} — {{ $mLabels[$p->method] ?? $p->method }} : {{ money($p->amount) }}</p>
            @empty
                <p>{{ $order->payment_status === 'charged_to_room' ? 'Imputé sur la chambre '.($order->room->number ?? '') : 'À régler' }}</p>
            @endforelse
        </div>

        <p class="mt-8 text-center text-xs text-nuit-400">Merci de votre visite — {{ $hotel->name }} · {{ config('hotel.tagline') }}</p>
    </div>
</div>
</body>
</html>
