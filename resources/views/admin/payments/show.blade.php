@extends('admin.layout')
@section('title', $intent->reference)

@php
    $target = $intent->payable;
    $link = match (true) {
        $target instanceof \App\Models\Reservation => route('admin.reservations.show', $target),
        $target instanceof \App\Models\Order => route('admin.orders.show', $target),
        $target instanceof \App\Models\Event => route('admin.events.show', $target),
        default => null,
    };
@endphp

@section('content')
<div class="mx-auto flex max-w-2xl flex-col gap-6">
    <a href="{{ route('admin.payments.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Paiements</a>

    <div class="rounded-2xl bg-white p-6 shadow-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="font-mono text-sm text-nuit-500">{{ $intent->reference }}</p>
                <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ money($intent->amount) }}</p>
                <p class="text-sm text-nuit-500">{{ $intent->purposeLabel() }}</p>
            </div>
            <span class="rounded-full px-3 py-0.5 text-xs font-semibold {{ ['paid' => 'bg-emerald-100 text-emerald-800', 'failed' => 'bg-terracotta-100 text-terracotta-800', 'refunded' => 'bg-laiton-100 text-laiton-800'][$intent->status] ?? 'bg-sable-200 text-nuit-600' }}">{{ $intent->statusLabel() }}</span>
        </div>

        <dl class="mt-5 grid gap-2 text-sm sm:grid-cols-2">
            <div><dt class="text-nuit-400">Payeur</dt><dd class="text-nuit-900">{{ $intent->payer_name ?: '—' }} {{ $intent->payer_email }}</dd></div>
            <div><dt class="text-nuit-400">Prestataire</dt><dd class="text-nuit-900">{{ $intent->provider }} @if($intent->provider_ref)· {{ $intent->provider_ref }}@endif</dd></div>
            <div><dt class="text-nuit-400">Moyen</dt><dd class="text-nuit-900">{{ $intent->method ? config('payments.methods.'.$intent->method, $intent->method) : '—' }}</dd></div>
            <div><dt class="text-nuit-400">Créé</dt><dd class="text-nuit-900">{{ $intent->created_at->format('d/m/Y H:i') }}</dd></div>
            @if ($intent->paid_at)<div><dt class="text-nuit-400">Payé</dt><dd class="text-nuit-900">{{ $intent->paid_at->format('d/m/Y H:i') }}</dd></div>@endif
            @if ($intent->expires_at && $intent->isOpen())<div><dt class="text-nuit-400">Expire</dt><dd class="text-nuit-900">{{ $intent->expires_at->format('d/m/Y H:i') }}</dd></div>@endif
            @if ($intent->failure_reason)<div class="sm:col-span-2"><dt class="text-nuit-400">Motif d'échec</dt><dd class="text-terracotta-700">{{ $intent->failure_reason }}</dd></div>@endif
        </dl>

        @if ($link)<a href="{{ $link }}" class="mt-4 inline-block text-xs font-semibold text-terracotta-600 hover:underline">Voir l'objet lié ↗</a>@endif

        @if ($intent->isOpen())
            <div class="mt-5 rounded-xl bg-sable-100 p-4 text-sm">
                <p class="font-semibold text-nuit-800">Lien de paiement client</p>
                <p class="mt-1 break-all font-mono text-xs text-nuit-600">{{ route('pay.checkout', $intent->reference) }}</p>
            </div>
        @endif

        <div class="mt-5 flex flex-wrap gap-2">
            @if ($intent->isOpen())
                <form method="POST" action="{{ route('admin.payments.mark', $intent->reference) }}" class="flex items-center gap-2">
                    @csrf
                    <select name="method" class="rounded-lg border border-nuit-200 px-2 py-1.5 text-xs">
                        @foreach (config('payments.methods') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                    </select>
                    <button class="rounded-full bg-nuit-900 px-4 py-1.5 text-xs font-semibold text-white">Marquer encaissé</button>
                </form>
                <form method="POST" action="{{ route('admin.payments.cancel', $intent->reference) }}">@csrf<button class="rounded-full border border-nuit-200 px-4 py-1.5 text-xs font-semibold text-nuit-500">Annuler</button></form>
            @endif
            @if ($intent->isRefundable())
                <form method="POST" action="{{ route('admin.payments.refund', $intent->reference) }}" onsubmit="return confirm('Rembourser {{ money($intent->amount) }} ?')">
                    @csrf
                    <button class="rounded-full border border-terracotta-300 px-4 py-1.5 text-xs font-semibold text-terracotta-700 hover:bg-terracotta-50">Rembourser</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
