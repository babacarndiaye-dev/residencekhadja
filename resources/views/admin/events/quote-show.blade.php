@extends('admin.layout')
@section('title', $quote->reference)

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <a href="{{ $quote->lead ? route('admin.events.leads.show', $quote->lead) : route('admin.events.pipeline') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Retour</a>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.events.quotes.print', $quote) }}" target="_blank" class="rounded-full border border-nuit-200 px-4 py-1.5 text-xs font-semibold text-nuit-700 hover:border-terracotta-400">Imprimer ↗</a>
            @if ($quote->isEditable())
                <a href="{{ route('admin.events.quotes.edit', $quote) }}" class="rounded-full border border-nuit-200 px-4 py-1.5 text-xs font-semibold text-nuit-700 hover:border-terracotta-400">Modifier</a>
            @endif
            @if ($quote->status === 'draft')
                <form method="POST" action="{{ route('admin.events.quotes.send', $quote) }}">@csrf<button class="rounded-full border border-nuit-200 px-4 py-1.5 text-xs font-semibold text-nuit-700 hover:border-terracotta-400">Marquer envoyé</button></form>
            @endif
            @if (in_array($quote->status, ['draft', 'sent']))
                <form method="POST" action="{{ route('admin.events.quotes.accept', $quote) }}" onsubmit="return confirm('Accepter ce devis et créer l’événement en option ?')">@csrf<button class="rounded-full bg-emerald-600 px-4 py-1.5 text-xs font-semibold text-white">Accepter → événement</button></form>
                <form method="POST" action="{{ route('admin.events.quotes.decline', $quote) }}">@csrf<button class="rounded-full border border-nuit-200 px-4 py-1.5 text-xs font-semibold text-nuit-500">Refuser</button></form>
            @endif
        </div>
    </div>

    <div class="rounded-2xl bg-white p-6 shadow-card">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="font-display text-2xl font-semibold text-nuit-900">{{ $quote->title }}</h2>
                <p class="text-sm text-nuit-500">{{ $quote->reference }} · {{ $quote->pax }} pax ·
                    <span class="rounded-full bg-sable-200 px-2 py-0.5 text-xs font-semibold">{{ $quote->statusLabel() }}</span>
                    @if ($quote->valid_until) · valable jusqu'au {{ $quote->valid_until->format('d/m/Y') }}@endif
                </p>
            </div>
            @if ($quote->event)
                <a href="{{ route('admin.events.show', $quote->event) }}" class="rounded-full bg-nuit-900 px-4 py-1.5 text-xs font-semibold text-white">Voir l'événement {{ $quote->event->reference }} ↗</a>
            @endif
        </div>

        <table class="mt-5 w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wider text-nuit-400">
                <tr><th class="py-2">Catégorie</th><th>Désignation</th><th class="text-right">Qté</th><th class="text-right">P.U.</th><th class="text-right">Total</th></tr>
            </thead>
            <tbody>
                @foreach ($quote->items as $it)
                    <tr class="border-t border-sable-100">
                        <td class="py-2 text-xs text-nuit-500">{{ $it->categoryLabel() }}</td>
                        <td class="py-2">{{ $it->label }}</td>
                        <td class="py-2 text-right">{{ rtrim(rtrim(number_format($it->quantity, 2), '0'), '.') }} {{ $it->unit }}</td>
                        <td class="py-2 text-right">{{ money($it->unit_price) }}</td>
                        <td class="py-2 text-right font-medium">{{ money($it->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <dl class="mt-4 ml-auto flex max-w-xs flex-col gap-1 text-sm">
            <div class="flex justify-between"><dt class="text-nuit-500">Sous-total</dt><dd>{{ money($quote->subtotal) }}</dd></div>
            @if ($quote->discount_amount)<div class="flex justify-between"><dt class="text-nuit-500">Remise</dt><dd>- {{ money($quote->discount_amount) }}</dd></div>@endif
            <div class="flex justify-between"><dt class="text-nuit-500">TVA ({{ rtrim(rtrim(number_format($quote->tax_rate * 100, 2), '0'), '.') }} %)</dt><dd>{{ money($quote->tax_amount) }}</dd></div>
            <div class="flex justify-between border-t border-sable-300 pt-1 font-semibold text-nuit-900"><dt>Total</dt><dd>{{ money($quote->total) }}</dd></div>
            <div class="flex justify-between text-terracotta-700"><dt>Acompte ({{ rtrim(rtrim(number_format($quote->deposit_rate * 100, 2), '0'), '.') }} %)</dt><dd>{{ money($quote->deposit_amount) }}</dd></div>
        </dl>

        @if ($quote->terms)<p class="mt-4 rounded-xl bg-sable-100 p-4 text-xs text-nuit-600">{{ $quote->terms }}</p>@endif
    </div>
</div>
@endsection
