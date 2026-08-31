@extends('admin.layout')
@section('title', 'Écran d’accueil')

@php
    $animationLabels = [
        'cinematic' => 'Cinématique — séquence complète (halo, reflet, textes échelonnés)',
        'fade' => 'Fondu — sobre, tout apparaît en douceur',
        'zoom' => 'Zoom — léger agrandissement, sans reflet',
        'minimal' => 'Minimal — logo seul, très court',
    ];
@endphp

@section('content')
<div class="mx-auto flex max-w-5xl flex-col gap-5"
     x-data="{
        enabled: {{ $s['enabled'] ? 'true' : 'false' }},
        animation: @js($s['animation']),
        welcome: @js($s['welcome_text']),
        name: @js($s['hotel_name']),
        signature: @js($s['signature']),
        from: @js($s['background_from']),
        to: @js($s['background_to']),
        glow: {{ $s['glow'] ? 'true' : 'false' }},
        seconds: {{ number_format($s['duration_ms'] / 1000, 1, '.', '') }},
        get previewUrl() {
            const p = new URLSearchParams({
                welcome_text: this.welcome, hotel_name: this.name, signature: this.signature,
                animation: this.animation, background_from: this.from, background_to: this.to,
                glow: this.glow ? 1 : 0, duration_ms: Math.round(this.seconds * 1000), t: this._nonce,
            });
            return '{{ route('admin.splash.preview') }}?' + p.toString();
        },
        _nonce: Date.now(),
        replay() { this._nonce = Date.now(); },
     }">

    <a href="{{ route('admin.settings.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Paramètres</a>

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="font-display text-2xl font-semibold text-nuit-900">Écran d’accueil</h2>
            <p class="text-sm text-nuit-500">Introduction premium jouée <strong>une seule fois par visite</strong> (session de navigation), puis transition douce vers le site.</p>
        </div>
        <span class="rounded-full px-3 py-1 text-xs font-semibold"
              :class="enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-sable-100 text-nuit-500'"
              x-text="enabled ? 'Activé' : 'Désactivé'"></span>
    </div>

    <form method="POST" action="{{ route('admin.splash.update') }}" enctype="multipart/form-data" class="grid gap-5 lg:grid-cols-[1.05fr_.95fr]">
        @csrf @method('PUT')

        {{-- ------------------------------------------------ Réglages --}}
        <div class="flex flex-col gap-5">

            <section class="rounded-2xl bg-white p-5 shadow-card">
                <label class="flex items-center gap-3">
                    <input type="hidden" name="enabled" value="0">
                    <input type="checkbox" name="enabled" value="1" x-model="enabled" class="h-4 w-4 rounded border-nuit-300 text-terracotta-500 focus:ring-terracotta-400">
                    <span class="text-sm font-semibold text-nuit-800">Activer l’écran d’accueil sur la vitrine</span>
                </label>
            </section>

            <section class="flex flex-col gap-4 rounded-2xl bg-white p-5 shadow-card">
                <h3 class="font-display text-lg font-semibold text-nuit-900">Contenu</h3>

                <div>
                    <p class="text-sm font-semibold text-nuit-800">Logo</p>
                    <div class="mt-2 flex items-center gap-4">
                        <div class="grid h-16 w-16 shrink-0 place-items-center rounded-lg bg-nuit-900 p-2">
                            <img src="{{ $s['logo_url'] }}" alt="Logo actuel" class="max-h-full max-w-full object-contain">
                        </div>
                        <div class="flex-1">
                            <input type="file" name="logo" accept="image/svg+xml,image/png,image/webp,image/jpeg" class="w-full text-xs">
                            <p class="mt-1 text-xs text-nuit-400">SVG, PNG ou WebP · 1 Mo max. Vide = logo officiel du site.</p>
                            @if ($s['logo_path'])
                                <label class="mt-1 flex items-center gap-2 text-xs text-nuit-600">
                                    <input type="checkbox" name="remove_logo" value="1" class="rounded border-nuit-300 text-terracotta-500"> Revenir au logo officiel
                                </label>
                            @endif
                        </div>
                    </div>
                    @error('logo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <label class="text-sm font-semibold text-nuit-800">Texte « Bienvenue »
                    <input type="text" name="welcome_text" x-model="welcome" maxlength="40" placeholder="Bienvenue"
                           class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                    @error('welcome_text')<span class="mt-1 block text-xs font-normal text-red-600">{{ $message }}</span>@enderror
                </label>

                <label class="text-sm font-semibold text-nuit-800">Nom de l’hôtel
                    <input type="text" name="hotel_name" x-model="name" maxlength="80" placeholder="{{ config('hotel.name') }}"
                           class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                    <span class="mt-1 block text-xs font-normal text-nuit-400">Vide = « {{ config('hotel.name') }} ».</span>
                    @error('hotel_name')<span class="mt-1 block text-xs font-normal text-red-600">{{ $message }}</span>@enderror
                </label>

                <label class="text-sm font-semibold text-nuit-800">Signature
                    <input type="text" name="signature" x-model="signature" maxlength="120" placeholder="Votre confort, notre priorité"
                           class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                    @error('signature')<span class="mt-1 block text-xs font-normal text-red-600">{{ $message }}</span>@enderror
                </label>
            </section>

            <section class="flex flex-col gap-4 rounded-2xl bg-white p-5 shadow-card">
                <h3 class="font-display text-lg font-semibold text-nuit-900">Animation</h3>

                <label class="text-sm font-semibold text-nuit-800">Type d’animation
                    <select name="animation" x-model="animation" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                        @foreach ($animationLabels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('animation')<span class="mt-1 block text-xs font-normal text-red-600">{{ $message }}</span>@enderror
                </label>

                <label class="text-sm font-semibold text-nuit-800">
                    Durée : <span x-text="Number(seconds).toFixed(1)"></span> s
                    <input type="range" name="duration_seconds" x-model="seconds" min="1" max="6" step="0.1"
                           class="mt-2 w-full accent-terracotta-500">
                    @error('duration_seconds')<span class="mt-1 block text-xs font-normal text-red-600">{{ $message }}</span>@enderror
                </label>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="text-sm font-semibold text-nuit-800">Fond — départ
                        <span class="mt-1 flex items-center gap-2 font-normal">
                            <input type="color" name="background_from" x-model="from" class="h-9 w-14 rounded border border-nuit-200">
                            <span class="font-mono text-xs text-nuit-500" x-text="from"></span>
                        </span>
                        @error('background_from')<span class="mt-1 block text-xs font-normal text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="text-sm font-semibold text-nuit-800">Fond — fin
                        <span class="mt-1 flex items-center gap-2 font-normal">
                            <input type="color" name="background_to" x-model="to" class="h-9 w-14 rounded border border-nuit-200">
                            <span class="font-mono text-xs text-nuit-500" x-text="to"></span>
                        </span>
                        @error('background_to')<span class="mt-1 block text-xs font-normal text-red-600">{{ $message }}</span>@enderror
                    </label>
                </div>

                <label class="flex items-center gap-3">
                    <input type="hidden" name="glow" value="0">
                    <input type="checkbox" name="glow" value="1" x-model="glow" class="h-4 w-4 rounded border-nuit-300 text-terracotta-500 focus:ring-terracotta-400">
                    <span class="text-sm font-semibold text-nuit-800">Effet lumineux en arrière-plan
                        <span class="block text-xs font-normal text-nuit-400">Halo doux. Automatiquement allégé sur mobile et si « animations réduites ».</span>
                    </span>
                </label>
            </section>

            <div class="sticky bottom-0 flex items-center gap-3 rounded-2xl bg-white/95 p-3 shadow-card backdrop-blur">
                <button class="rounded-xl bg-nuit-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-nuit-800">Enregistrer</button>
                <span class="text-xs text-nuit-400">L’aperçu ci-contre reflète vos modifications en direct (avant enregistrement).</span>
            </div>
        </div>

        {{-- ------------------------------------------------ Aperçu --}}
        <div class="lg:sticky lg:top-4 lg:self-start">
            <div class="rounded-2xl bg-white p-4 shadow-card">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="font-display text-lg font-semibold text-nuit-900">Aperçu</h3>
                    <button type="button" x-on:click="replay()"
                            class="inline-flex items-center gap-1.5 rounded-full border border-nuit-200 px-3 py-1.5 text-xs font-semibold text-nuit-700 hover:border-terracotta-400 hover:text-terracotta-600">
                        <x-icon name="replay" size="16" /> Rejouer
                    </button>
                </div>
                <div class="overflow-hidden rounded-xl border border-sable-200 bg-nuit-900" style="aspect-ratio: 10 / 16; max-height: 70vh; margin-inline: auto;">
                    <iframe :src="previewUrl" title="Aperçu de l’écran d’accueil"
                            class="h-full w-full border-0" style="display:block;"></iframe>
                </div>
                <p class="mt-2 text-center text-xs text-nuit-400">Format portrait (mobile). Sur le site, l’intro occupe tout l’écran.</p>
            </div>
        </div>
    </form>
</div>
@endsection
