@extends('admin.layout')
@section('title', 'Mon PIN caisse')

@section('content')
<div class="mx-auto flex max-w-md flex-col gap-5">
    <a href="{{ route('admin.pos.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Caisse</a>

    <div class="rounded-2xl bg-white p-6 shadow-card">
        <h1 class="font-display text-lg font-semibold text-nuit-900">Mon PIN caisse</h1>
        <p class="mt-1 text-sm text-nuit-500">
            Ce code ({{ config('pos.pin.min') }} à {{ config('pos.pin.max') }} chiffres) vous permet d'autoriser au comptoir une remise
            élevée, une annulation ou un remboursement. Il est personnel — chaque autorisation est tracée à votre nom.
        </p>

        @if (auth()->user()->hasPosPin())
            <p class="mt-3 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">Un PIN est actuellement défini. Saisissez-en un nouveau pour le remplacer.</p>
        @endif

        <form method="POST" action="{{ route('admin.pos.pin.update') }}" class="mt-4 flex flex-col gap-4">
            @csrf
            @method('PUT')
            <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Nouveau PIN
                <input type="password" name="pos_pin" inputmode="numeric" autocomplete="off" required
                       minlength="{{ config('pos.pin.min') }}" maxlength="{{ config('pos.pin.max') }}"
                       class="rounded-lg border border-nuit-200 px-3 py-2.5 text-center font-display text-xl tracking-[0.3em]">
            </label>
            <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Confirmer
                <input type="password" name="pos_pin_confirmation" inputmode="numeric" autocomplete="off" required
                       class="rounded-lg border border-nuit-200 px-3 py-2.5 text-center font-display text-xl tracking-[0.3em]">
            </label>
            <button class="rounded-full bg-nuit-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-terracotta-500">Enregistrer</button>
        </form>
    </div>
</div>
@endsection
