@extends('layouts.app')

@section('title', 'Paiement sécurisé')
@push('head')<meta name="robots" content="noindex,nofollow">@endpush

@section('content')
<section class="py-12 sm:py-16">
    <x-container size="narrow">
        <div class="mx-auto max-w-md">
            <div class="flex items-center gap-2 text-sm text-nuit-500">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Paiement sécurisé · {{ config('payments.providers.'.config('payments.driver').'.label') }}
            </div>

            <div class="mt-4 rounded-2xl bg-white p-6 shadow-card sm:p-8">
                <p class="text-sm text-nuit-400">{{ $intent->purposeLabel() }}</p>
                <p class="mt-1 font-display text-3xl font-semibold text-nuit-900">{{ money($intent->amount) }}</p>
                <p class="mt-1 text-xs text-nuit-400">Réf. {{ $intent->reference }}@if($intent->payer_name) · {{ $intent->payer_name }}@endif</p>

                @if ($expired)
                    <div class="mt-5 rounded-xl border border-terracotta-300 bg-terracotta-50 p-4 text-sm text-terracotta-800">
                        Ce lien de paiement a expiré. Demandez-en un nouveau à l'hôtel.
                    </div>
                @else
                    @if ($errors->any())
                        <div class="mt-4 rounded-xl border border-terracotta-300 bg-terracotta-50 p-3 text-sm text-terracotta-800">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('pay.process', $intent->reference) }}" class="mt-5 flex flex-col gap-4">
                        @csrf
                        <fieldset>
                            <legend class="text-xs font-semibold uppercase tracking-wider text-nuit-400">Moyen de paiement</legend>
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                @foreach ($methods as $key => $label)
                                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-nuit-200 px-3 py-2 text-sm has-[:checked]:border-terracotta-500 has-[:checked]:bg-terracotta-50">
                                        <input type="radio" name="method" value="{{ $key }}" @checked($loop->first) required>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <label class="text-xs font-semibold text-nuit-500">Numéro de carte / téléphone (simulateur)
                            <input name="card" inputmode="numeric" autocomplete="off" placeholder="4111 1111 1111 1111"
                                   class="mt-1 w-full rounded-xl border border-nuit-200 px-4 py-3 text-lg tracking-widest focus:border-terracotta-500 focus:outline-none">
                        </label>

                        <button name="action" value="pay" class="rounded-full bg-nuit-900 px-6 py-3.5 text-base font-semibold text-white hover:bg-terracotta-500">
                            Payer {{ money($intent->amount) }}
                        </button>
                        <button name="action" value="fail" class="text-xs font-semibold text-nuit-400 underline">
                            Simuler un échec de paiement
                        </button>
                    </form>

                    <p class="mt-4 text-center text-[0.7rem] text-nuit-400">
                        Environnement de test — aucun montant réel n'est débité.
                    </p>
                @endif
            </div>
        </div>
    </x-container>
</section>
@endsection
