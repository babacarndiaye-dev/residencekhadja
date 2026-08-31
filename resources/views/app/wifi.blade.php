@extends('app.layout')
@section('title', 'Wi-Fi')

@section('content')
<div class="flex flex-col gap-4">
    <section class="rounded-2xl bg-white p-6 text-center shadow-card">
        <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-terracotta-50 text-terracotta-600"><x-icon name="wifi" :size="28" /></span>
        <h1 class="mt-3 font-display text-lg font-semibold text-nuit-900">Wi-Fi de l’hôtel</h1>

        <dl class="mt-4 flex flex-col gap-3 text-left">
            <div class="rounded-xl bg-sable-50 p-3">
                <dt class="text-xs text-nuit-400">Réseau (SSID)</dt>
                <dd class="font-mono text-lg font-semibold text-nuit-900">{{ $wifi['ssid'] }}</dd>
            </div>
            <div class="rounded-xl bg-sable-50 p-3">
                <dt class="text-xs text-nuit-400">Mot de passe</dt>
                <dd class="font-mono text-lg font-semibold text-nuit-900">{{ $wifi['password'] }}</dd>
            </div>
        </dl>
        <p class="mt-3 text-xs text-nuit-400">Connexion illimitée pendant toute la durée de votre séjour.</p>
    </section>
</div>
@endsection
