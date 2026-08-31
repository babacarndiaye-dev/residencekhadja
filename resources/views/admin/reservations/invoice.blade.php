<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Facture {{ $reservation->invoice_number ?? $reservation->groupKey() }}</title>
    @vite(['resources/css/app.css'])
    <style>@media print { .no-print { display: none !important; } body { background: #fff; } }</style>
</head>
@php
    $members = $members ?? collect([$reservation]);
    $roomTotal = (int) $members->sum('room_total');
    $extrasTotal = (int) $members->sum('extras_total');
    $discountTotal = (int) $members->sum('discount_amount');
    $taxTotal = (int) $members->sum('tax_amount');
    $touristTotal = (int) $members->sum('tourist_tax');
    $charges = $members->flatMap(fn ($m) => $m->charges);
    $staysTotal = (int) $members->sum('total');
    $chargesTotal = (int) $charges->sum('amount');
    $grandTotal = $staysTotal + $chargesTotal;
    $paidTotal = (int) $members->flatMap(fn ($m) => $m->payments)->sum('amount');
    $balance = $grandTotal - $paidTotal;
    $promo = $members->first(fn ($m) => filled($m->promo_code))?->promo_code;
    $nights = $reservation->nights();
@endphp
<body class="bg-sable-100 p-6 text-ink">
<div class="mx-auto max-w-2xl">
    <div class="no-print mb-4 flex justify-between">
        <a href="{{ route('admin.reservations.show', $reservation) }}" class="text-sm text-nuit-600 hover:text-terracotta-600">← Retour</a>
        <button onclick="window.print()" class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Imprimer / PDF</button>
    </div>

    <div class="rounded-2xl bg-white p-8 shadow-card">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ \App\Support\Branding::logo() }}" alt="" width="44" height="44" class="h-11 w-11">
                <div>
                    <p class="font-display text-lg font-semibold text-nuit-900">{{ $reservation->hotel->name }}</p>
                    <p class="text-xs text-nuit-500">{{ $reservation->hotel->address }} · {{ $reservation->hotel->city }}</p>
                    <p class="text-xs text-nuit-500">{{ $reservation->hotel->phone }} · {{ $reservation->hotel->email }}</p>
                </div>
            </div>
            <div class="text-right text-sm">
                <p class="font-display text-xl font-semibold text-nuit-900">FACTURE</p>
                <p class="text-nuit-600">{{ $reservation->invoice_number ?? '—' }}</p>
                <p class="text-xs text-nuit-400">{{ now()->format('d/m/Y') }}</p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-xs uppercase tracking-wider text-nuit-400">Client</p>
                <p class="font-medium text-nuit-900">{{ $reservation->guest->civility }} {{ $reservation->guest->fullName() }}</p>
                <p class="text-nuit-600">{{ $reservation->guest->email }}</p>
                @if ($reservation->guest->company)<p class="text-nuit-600">{{ $reservation->guest->company }}</p>@endif
            </div>
            <div class="text-right">
                <p class="text-xs uppercase tracking-wider text-nuit-400">Séjour</p>
                <p class="text-nuit-900">{{ $reservation->groupKey() }}</p>
                <p class="text-nuit-600">{{ $reservation->check_in->format('d/m/Y') }} → {{ $reservation->check_out->format('d/m/Y') }} ({{ $nights }} nuit(s))</p>
                <p class="text-nuit-600">{{ $members->count() }} chambre(s)</p>
            </div>
        </div>

        <table class="mt-6 w-full text-sm">
            <thead class="border-b border-nuit-200 text-left text-xs uppercase tracking-wider text-nuit-400">
                <tr><th class="py-2">Désignation</th><th class="py-2 text-right">Montant</th></tr>
            </thead>
            <tbody class="text-nuit-800">
                @foreach ($members as $m)
                    <tr class="border-b border-sable-200">
                        <td class="py-2">
                            Hébergement — {{ $m->roomCategory->name }}
                            @if ($m->room)· Ch. {{ $m->room->number }}@endif
                            × {{ $nights }} nuit(s)
                        </td>
                        <td class="py-2 text-right">{{ money($m->room_total) }}</td>
                    </tr>
                @endforeach
                @if ($extrasTotal)<tr class="border-b border-sable-200"><td class="py-2">Services additionnels</td><td class="py-2 text-right">{{ money($extrasTotal) }}</td></tr>@endif
                @if ($discountTotal)<tr class="border-b border-sable-200 text-terracotta-700"><td class="py-2">Remise {{ $promo }}</td><td class="py-2 text-right">−{{ money($discountTotal) }}</td></tr>@endif
                <tr class="border-b border-sable-200"><td class="py-2">Taxes &amp; services</td><td class="py-2 text-right">{{ money($taxTotal) }}</td></tr>
                <tr class="border-b border-sable-200"><td class="py-2">Taxe de séjour</td><td class="py-2 text-right">{{ money($touristTotal) }}</td></tr>
                @foreach ($charges as $charge)
                    <tr class="border-b border-sable-200"><td class="py-2">{{ $charge->label }}</td><td class="py-2 text-right">{{ money($charge->amount) }}</td></tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-nuit-900 font-semibold text-nuit-900"><td class="py-2">Total TTC</td><td class="py-2 text-right">{{ money($grandTotal) }}</td></tr>
                <tr class="text-nuit-600"><td class="py-1">Déjà réglé</td><td class="py-1 text-right">{{ money($paidTotal) }}</td></tr>
                <tr class="font-semibold {{ $balance > 0 ? 'text-terracotta-700' : 'text-emerald-700' }}"><td class="py-1">Solde dû</td><td class="py-1 text-right">{{ money($balance) }}</td></tr>
            </tfoot>
        </table>

        <p class="mt-8 text-center text-xs text-nuit-400">Merci de votre visite — {{ $reservation->hotel->name }} · {{ config('hotel.tagline') }}</p>
    </div>
</div>
</body>
</html>
