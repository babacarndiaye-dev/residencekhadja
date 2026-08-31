<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Contrat {{ $event->reference }}</title>
    @vite(['resources/css/app.css'])
    <style>@media print { .no-print { display: none !important; } } body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }</style>
</head>
<body class="mx-auto max-w-3xl bg-white p-8 text-ink">
    <div class="no-print mb-6 flex justify-between">
        <a href="{{ route('admin.events.show', $event) }}" class="text-sm text-nuit-500">← Retour</a>
        <button onclick="window.print()" class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Imprimer / PDF</button>
    </div>

    <header class="flex items-center gap-3 border-b border-sable-300 pb-4">
        <img src="{{ \App\Support\Branding::logo() }}" alt="" class="h-12 w-12">
        <div>
            <p class="font-display text-lg font-semibold text-nuit-900">{{ config('hotel.name') }}</p>
            <p class="text-xs text-nuit-500">{{ config('hotel.contact.address_line') }}, {{ config('hotel.city') }}</p>
        </div>
        <div class="ml-auto text-right">
            <p class="font-display text-lg font-semibold">CONTRAT ÉVÉNEMENTIEL</p>
            <p class="font-mono text-sm">{{ $event->reference }}</p>
        </div>
    </header>

    <h1 class="mt-6 font-display text-xl font-semibold text-nuit-900">{{ $event->name }}</h1>
    <div class="mt-3 grid grid-cols-2 gap-4 text-sm">
        <p><span class="text-nuit-400">Client :</span> <b>{{ $event->client_name }}</b></p>
        <p><span class="text-nuit-400">Type :</span> {{ $event->typeLabel() }}</p>
        <p><span class="text-nuit-400">Dates :</span> {{ $event->starts_at->format('d/m/Y H\hi') }} → {{ $event->ends_at->format('d/m/Y H\hi') }}</p>
        <p><span class="text-nuit-400">Participants :</span> {{ $event->pax }}</p>
        <p><span class="text-nuit-400">Chambres bloquées :</span> {{ $event->rooms_to_block }}</p>
        <p><span class="text-nuit-400">Statut :</span> {{ $event->statusLabel() }}</p>
    </div>

    <h2 class="mt-6 font-display text-lg font-semibold text-nuit-900">Espaces</h2>
    <ul class="mt-1 text-sm">
        @forelse ($event->spaceBookings as $b)
            <li class="border-b border-sable-100 py-1">{{ $b->space->name }} — {{ $b->starts_at->format('d/m H\hi') }} → {{ $b->ends_at->format('d/m H\hi') }}@if($b->layout) · {{ $b->layout }}@endif</li>
        @empty
            <li class="py-1 text-nuit-400">À préciser.</li>
        @endforelse
    </ul>

    @if ($event->quote)
        <h2 class="mt-6 font-display text-lg font-semibold text-nuit-900">Prestations & montants</h2>
        <table class="mt-1 w-full text-sm">
            <thead class="border-y border-sable-300 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="py-2">Désignation</th><th class="py-2 text-right">Total</th></tr>
            </thead>
            <tbody>
                @foreach ($event->quote->items as $it)
                    <tr class="border-b border-sable-100"><td class="py-2">{{ $it->label }}</td><td class="py-2 text-right">{{ money($it->total) }}</td></tr>
                @endforeach
            </tbody>
        </table>
        <dl class="mt-3 ml-auto flex max-w-xs flex-col gap-1 text-sm">
            <div class="flex justify-between"><dt class="text-nuit-500">Total TTC</dt><dd class="font-semibold">{{ money($event->quote->total) }}</dd></div>
            <div class="flex justify-between text-terracotta-700"><dt>Acompte</dt><dd>{{ money($event->quote->deposit_amount) }} {{ $event->deposit_paid ? '(réglé)' : '' }}</dd></div>
            <div class="flex justify-between"><dt class="text-nuit-500">Solde</dt><dd>{{ money($event->balanceDue()) }}</dd></div>
        </dl>
    @endif

    <div class="mt-8 grid grid-cols-2 gap-8 text-sm">
        <div><p class="text-nuit-400">Pour le client</p><div class="mt-10 border-t border-nuit-300 pt-1 text-xs">Nom, date et signature</div></div>
        <div><p class="text-nuit-400">Pour {{ config('hotel.name') }}</p><div class="mt-10 border-t border-nuit-300 pt-1 text-xs">Nom, date et signature</div></div>
    </div>
</body>
</html>
