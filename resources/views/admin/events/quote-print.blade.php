<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $quote->reference }} · {{ config('hotel.name') }}</title>
    @vite(['resources/css/app.css'])
    <style>@media print { .no-print { display: none !important; } } body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }</style>
</head>
<body class="mx-auto max-w-3xl bg-white p-8 text-ink">
    <div class="no-print mb-6 flex justify-between">
        <a href="{{ route('admin.events.quotes.show', $quote) }}" class="text-sm text-nuit-500">← Retour</a>
        <button onclick="window.print()" class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Imprimer / PDF</button>
    </div>

    <header class="flex items-start justify-between border-b border-sable-300 pb-4">
        <div class="flex items-center gap-3">
            <img src="{{ \App\Support\Branding::logo() }}" alt="" class="h-12 w-12">
            <div>
                <p class="font-display text-lg font-semibold text-nuit-900">{{ config('hotel.name') }}</p>
                <p class="text-xs text-nuit-500">{{ config('hotel.contact.address_line') }}, {{ config('hotel.city') }} · {{ config('hotel.contact.phone') }}</p>
            </div>
        </div>
        <div class="text-right">
            <p class="font-display text-xl font-semibold text-nuit-900">DEVIS</p>
            <p class="font-mono text-sm">{{ $quote->reference }}</p>
            <p class="text-xs text-nuit-500">{{ $quote->created_at->format('d/m/Y') }}</p>
        </div>
    </header>

    <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
        <div>
            <p class="text-xs uppercase tracking-wider text-nuit-400">Client</p>
            <p class="font-semibold text-nuit-900">{{ $quote->lead->company ?? $quote->lead->contact_name ?? '—' }}</p>
            @if ($quote->lead)<p class="text-xs text-nuit-500">{{ $quote->lead->contact_name }} · {{ $quote->lead->contact_email }} · {{ $quote->lead->contact_phone }}</p>@endif
        </div>
        <div>
            <p class="text-xs uppercase tracking-wider text-nuit-400">Prestation</p>
            <p class="font-semibold text-nuit-900">{{ $quote->title }}</p>
            <p class="text-xs text-nuit-500">{{ $quote->pax }} participants @if($quote->valid_until)· valable jusqu'au {{ $quote->valid_until->format('d/m/Y') }}@endif</p>
        </div>
    </div>

    <table class="mt-6 w-full text-sm">
        <thead class="border-y border-sable-300 text-left text-xs uppercase tracking-wider text-nuit-500">
            <tr><th class="py-2">Désignation</th><th class="py-2 text-right">Qté</th><th class="py-2 text-right">P.U.</th><th class="py-2 text-right">Total</th></tr>
        </thead>
        <tbody>
            @foreach ($quote->items as $it)
                <tr class="border-b border-sable-100">
                    <td class="py-2">{{ $it->label }}<span class="block text-xs text-nuit-400">{{ $it->categoryLabel() }}</span></td>
                    <td class="py-2 text-right">{{ rtrim(rtrim(number_format($it->quantity, 2), '0'), '.') }} {{ $it->unit }}</td>
                    <td class="py-2 text-right">{{ money($it->unit_price) }}</td>
                    <td class="py-2 text-right">{{ money($it->total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <dl class="mt-4 ml-auto flex max-w-xs flex-col gap-1 text-sm">
        <div class="flex justify-between"><dt class="text-nuit-500">Sous-total</dt><dd>{{ money($quote->subtotal) }}</dd></div>
        @if ($quote->discount_amount)<div class="flex justify-between"><dt class="text-nuit-500">Remise</dt><dd>- {{ money($quote->discount_amount) }}</dd></div>@endif
        <div class="flex justify-between"><dt class="text-nuit-500">TVA</dt><dd>{{ money($quote->tax_amount) }}</dd></div>
        <div class="flex justify-between border-t border-sable-300 pt-1 text-base font-semibold text-nuit-900"><dt>Total TTC</dt><dd>{{ money($quote->total) }}</dd></div>
        <div class="flex justify-between text-terracotta-700"><dt>Acompte à la commande</dt><dd>{{ money($quote->deposit_amount) }}</dd></div>
    </dl>

    <div class="mt-6 rounded-xl bg-sable-100 p-4 text-xs text-nuit-600">
        {{ $quote->terms ?: "Devis soumis à disponibilité des espaces à la date de confirmation. Acompte de ".rtrim(rtrim(number_format($quote->deposit_rate * 100, 2), '0'), '.')." % exigible à la signature. Solde payable avant l'événement." }}
    </div>

    <p class="mt-8 text-xs text-nuit-400">{{ config('hotel.name') }} — {{ config('hotel.contact.email') }}</p>
</body>
</html>
