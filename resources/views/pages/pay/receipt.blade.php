@extends('layouts.app')

@section('title', $intent->isPaid() ? 'Paiement confirmé' : 'Paiement')
@push('head')<meta name="robots" content="noindex,nofollow">@endpush

@section('content')
<section class="py-12 sm:py-16">
    <x-container size="narrow">
        <div class="mx-auto flex max-w-md flex-col items-center gap-4 text-center">
            @if ($intent->isPaid())
                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 13 4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <h1 class="font-display text-3xl font-semibold text-nuit-900">Paiement confirmé</h1>
                <p class="text-ink-soft">Merci ! Votre paiement de <strong class="text-nuit-900">{{ money($intent->amount) }}</strong> a bien été reçu.</p>
            @elseif ($intent->status === 'refunded')
                <h1 class="font-display text-3xl font-semibold text-nuit-900">Paiement remboursé</h1>
                <p class="text-ink-soft">Ce paiement de {{ money($intent->amount) }} a été remboursé.</p>
            @else
                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-sable-200 text-nuit-500">…</span>
                <h1 class="font-display text-3xl font-semibold text-nuit-900">Paiement {{ mb_strtolower($intent->statusLabel()) }}</h1>
                @if ($intent->isOpen())
                    <a href="{{ route('pay.checkout', $intent->reference) }}" class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Reprendre le paiement</a>
                @endif
            @endif

            <div class="mt-4 w-full rounded-2xl bg-white p-6 text-left text-sm shadow-card">
                <dl class="grid gap-2">
                    <div class="flex justify-between"><dt class="text-nuit-400">Référence</dt><dd class="font-mono">{{ $intent->reference }}</dd></div>
                    <div class="flex justify-between"><dt class="text-nuit-400">Objet</dt><dd>{{ $intent->purposeLabel() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-nuit-400">Montant</dt><dd class="font-semibold text-nuit-900">{{ money($intent->amount) }}</dd></div>
                    @if ($intent->method)<div class="flex justify-between"><dt class="text-nuit-400">Moyen</dt><dd>{{ config('payments.methods.'.$intent->method, $intent->method) }}</dd></div>@endif
                    @if ($intent->paid_at)<div class="flex justify-between"><dt class="text-nuit-400">Date</dt><dd>{{ $intent->paid_at->format('d/m/Y H:i') }}</dd></div>@endif
                </dl>
            </div>

            <x-button :href="route('home')" variant="dark" class="mt-4">Retour à l'accueil</x-button>
        </div>
    </x-container>
</section>
@endsection
