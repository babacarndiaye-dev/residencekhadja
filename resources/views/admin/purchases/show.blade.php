@extends('admin.layout')
@section('title', 'Achat '.$order->reference)

@php
    $u = auth()->user();
    $canStock = $u->hasRole('stock');
    $canApprove = $u->hasRole('direction');
    $canPay = $u->hasRole('finance');
@endphp

@section('content')
<div class="flex flex-col gap-6">
    <a href="{{ route('admin.purchases.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Achats</a>

    <div class="flex flex-wrap items-center gap-3">
        <h2 class="font-display text-2xl font-semibold text-nuit-900">{{ $order->reference }}</h2>
        <x-admin.badge :status="$order->status" />
        <span class="text-sm text-nuit-500">{{ $order->supplier->name }} → {{ $order->warehouse->name }}</span>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr] lg:items-start">
        <div class="flex flex-col gap-6">
            <section class="overflow-x-auto rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Lignes</h3>
                <table class="w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wider text-nuit-400">
                        <tr><th class="py-2">Article</th><th class="py-2">Commandé</th><th class="py-2">Reçu</th><th class="py-2">PU</th><th class="py-2 text-right">Total</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($order->lines as $l)
                            <tr class="border-t border-sable-100">
                                <td class="py-2">{{ $l->item->name }}</td>
                                <td class="py-2">{{ rtrim(rtrim(number_format($l->quantity, 3, ',', ' '), '0'), ',') }} {{ $l->item->unit }}</td>
                                <td class="py-2 {{ $l->received_qty > 0 ? 'text-emerald-700' : 'text-nuit-400' }}">{{ rtrim(rtrim(number_format($l->received_qty, 3, ',', ' '), '0'), ',') }}</td>
                                <td class="py-2">{{ money($l->unit_price) }}</td>
                                <td class="py-2 text-right">{{ money($l->line_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="text-sm">
                        <tr><td colspan="4" class="pt-3 text-right text-nuit-500">Sous-total HT</td><td class="pt-3 text-right">{{ money($order->subtotal) }}</td></tr>
                        <tr><td colspan="4" class="text-right text-nuit-500">TVA</td><td class="text-right">{{ money($order->tax) }}</td></tr>
                        <tr class="font-semibold text-nuit-900"><td colspan="4" class="pt-1 text-right">Total TTC</td><td class="pt-1 text-right">{{ money($order->total) }}</td></tr>
                    </tfoot>
                </table>
                @if ($order->note)<p class="mt-3 rounded-lg bg-sable-100 p-3 text-xs text-nuit-600">{{ $order->note }}</p>@endif
            </section>

            @if ($order->receipts->isNotEmpty())
                <section class="rounded-2xl bg-white p-5 shadow-card">
                    <h3 class="mb-2 font-display text-lg font-semibold text-nuit-900">Réceptions</h3>
                    <ul class="flex flex-col gap-1 text-sm text-nuit-600">
                        @foreach ($order->receipts as $r)
                            <li>{{ $r->reference }} — {{ $r->received_at->format('d/m/Y H:i') }} · {{ $r->lines->count() }} ligne(s)</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($order->invoices->isNotEmpty())
                <section class="rounded-2xl bg-white p-5 shadow-card">
                    <h3 class="mb-2 font-display text-lg font-semibold text-nuit-900">Factures fournisseur</h3>
                    @foreach ($order->invoices as $inv)
                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-sable-100 py-2 text-sm">
                            <span>{{ $inv->reference }} · {{ money($inv->total) }} <x-admin.badge :status="$inv->status" :label="$inv->statusLabel()" /></span>
                            @if ($canPay && $inv->balance() > 0)
                                <form method="POST" action="{{ route('admin.purchases.invoice.pay', $inv) }}" class="flex items-center gap-2">
                                    @csrf
                                    <select name="finance_account_id" class="rounded-lg border border-nuit-200 px-2 py-1 text-xs">
                                        @foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
                                    </select>
                                    <select name="method" class="rounded-lg border border-nuit-200 px-2 py-1 text-xs">
                                        @foreach ($methods as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                                    </select>
                                    <input type="number" name="amount" value="{{ $inv->balance() }}" class="w-24 rounded-lg border border-nuit-200 px-2 py-1 text-xs">
                                    <button class="rounded-full bg-nuit-900 px-3 py-1 text-xs font-semibold text-white">Régler</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </section>
            @endif
        </div>

        <div class="flex flex-col gap-4 lg:sticky lg:top-6">
            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Workflow</h3>
                <ol class="mb-4 flex flex-col gap-1 text-xs text-nuit-500">
                    @foreach (['Demande', 'Validation', 'Commande', 'Réception', 'Facture'] as $i => $step)
                        <li>{{ $i + 1 }}. {{ $step }}</li>
                    @endforeach
                </ol>

                @if ($canStock && $order->status === 'draft')
                    <form method="POST" action="{{ route('admin.purchases.transition', [$order, 'submit']) }}" class="mb-2">@csrf
                        <button class="w-full rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Soumettre pour validation</button>
                    </form>
                @endif
                @if ($canApprove && $order->status === 'submitted')
                    <form method="POST" action="{{ route('admin.purchases.approve', $order) }}" class="mb-2">@csrf
                        <button class="w-full rounded-full bg-terracotta-500 px-4 py-2 text-sm font-semibold text-white hover:bg-terracotta-600">Valider la demande</button>
                    </form>
                @endif
                @if ($canStock && $order->status === 'approved')
                    <form method="POST" action="{{ route('admin.purchases.transition', [$order, 'order']) }}" class="mb-2">@csrf
                        <button class="w-full rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Passer commande</button>
                    </form>
                @endif
                @if ($canStock && in_array($order->status, ['draft', 'submitted', 'approved', 'ordered'], true))
                    <form method="POST" action="{{ route('admin.purchases.transition', [$order, 'cancel']) }}" onsubmit="return confirm('Annuler la commande ?')">@csrf
                        <button class="w-full rounded-full border border-red-200 px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Annuler</button>
                    </form>
                @endif
            </section>

            @if ($canStock && in_array($order->status, ['ordered', 'partially_received', 'approved'], true))
                <form method="POST" action="{{ route('admin.purchases.receive', $order) }}" class="rounded-2xl bg-white p-5 shadow-card">
                    @csrf
                    <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Réceptionner</h3>
                    @foreach ($order->lines as $l)
                        @if ($l->outstandingQty() > 0)
                            <label class="mb-2 flex items-center justify-between gap-2 text-sm">
                                <span>{{ $l->item->name }} <span class="text-xs text-nuit-400">(reste {{ rtrim(rtrim(number_format($l->outstandingQty(), 3, ',', ' '), '0'), ',') }})</span></span>
                                <input type="number" step="0.001" name="qty[{{ $l->id }}]" value="{{ $l->outstandingQty() }}" min="0" max="{{ $l->outstandingQty() }}" class="w-24 rounded-lg border border-nuit-200 px-2 py-1 text-sm">
                            </label>
                        @endif
                    @endforeach
                    <button class="mt-2 w-full rounded-full bg-terracotta-500 px-4 py-2 text-sm font-semibold text-white hover:bg-terracotta-600">Valider la réception</button>
                </form>
            @endif

            @if ($canStock && in_array($order->status, ['partially_received', 'received', 'ordered'], true))
                <form method="POST" action="{{ route('admin.purchases.invoice.store', $order) }}" class="rounded-2xl bg-white p-5 shadow-card">
                    @csrf
                    <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Enregistrer la facture</h3>
                    <div class="grid gap-2">
                        <input type="text" name="reference" placeholder="N° facture fournisseur" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="amount_ht" placeholder="Montant HT" value="{{ $order->subtotal }}" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                            <input type="number" name="tax" placeholder="TVA" value="{{ $order->tax }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="date" name="issued_on" value="{{ now()->toDateString() }}" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                            <input type="date" name="due_on" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                        </div>
                    </div>
                    <button class="mt-2 w-full rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Enregistrer</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
