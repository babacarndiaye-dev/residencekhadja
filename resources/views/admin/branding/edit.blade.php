@extends('admin.layout')
@section('title', 'Identité visuelle')

@section('content')
<div class="mx-auto flex max-w-3xl flex-col gap-5">

    <a href="{{ route('admin.settings.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Paramètres</a>

    <div>
        <h2 class="font-display text-2xl font-semibold text-nuit-900">Identité visuelle</h2>
        <p class="text-sm text-nuit-500">
            Le logo est repris partout : en-tête et pied de la vitrine, back-office, factures, bulletins de paie,
            badges, écrans de connexion et app invité. SVG, PNG ou WebP — 1 Mo maximum.
        </p>
    </div>

    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.branding.update') }}" enctype="multipart/form-data" class="flex flex-col gap-5">
        @csrf @method('PUT')

        {{-- Logo principal (couleur) --}}
        <section class="flex flex-col gap-3 rounded-2xl bg-white p-5 shadow-card">
            <div>
                <h3 class="font-display text-lg font-semibold text-nuit-900">Logo principal</h3>
                <p class="text-xs text-nuit-400">Version couleur, posée sur fond clair (en-tête vitrine, sidebar, documents).</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="grid h-20 w-20 shrink-0 place-items-center rounded-xl border border-sable-200 bg-white p-2">
                    <img src="{{ $urls['logo'] }}" alt="Logo principal actuel" class="max-h-full max-w-full object-contain">
                </div>
                <div class="flex-1">
                    <input type="file" name="logo" accept="image/svg+xml,image/png,image/webp,image/jpeg" class="w-full text-xs">
                    <p class="mt-1 text-xs text-nuit-400">Vide = logo livré par défaut.</p>
                    @if ($paths['logo'])
                        <label class="mt-1 flex items-center gap-2 text-xs text-nuit-600">
                            <input type="checkbox" name="remove_logo" value="1" class="rounded border-nuit-300 text-terracotta-500"> Revenir au logo par défaut
                        </label>
                    @endif
                    @error('logo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        {{-- Logo monochrome (fonds sombres) --}}
        <section class="flex flex-col gap-3 rounded-2xl bg-white p-5 shadow-card">
            <div>
                <h3 class="font-display text-lg font-semibold text-nuit-900">Logo monochrome</h3>
                <p class="text-xs text-nuit-400">Version claire/monochrome, posée sur fond sombre (pied de page, badges RH, écrans de connexion).</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="grid h-20 w-20 shrink-0 place-items-center rounded-xl bg-nuit-900 p-2">
                    <img src="{{ $urls['logo_mono'] }}" alt="Logo monochrome actuel" class="max-h-full max-w-full object-contain">
                </div>
                <div class="flex-1">
                    <input type="file" name="logo_mono" accept="image/svg+xml,image/png,image/webp,image/jpeg" class="w-full text-xs">
                    <p class="mt-1 text-xs text-nuit-400">Vide = logo monochrome livré par défaut.</p>
                    @if ($paths['logo_mono'])
                        <label class="mt-1 flex items-center gap-2 text-xs text-nuit-600">
                            <input type="checkbox" name="remove_logo_mono" value="1" class="rounded border-nuit-300 text-terracotta-500"> Revenir au logo par défaut
                        </label>
                    @endif
                    @error('logo_mono')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="sticky bottom-0 flex items-center gap-3 rounded-2xl bg-white/95 p-3 shadow-card backdrop-blur">
            <button class="rounded-xl bg-nuit-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-nuit-800">Enregistrer</button>
            <span class="text-xs text-nuit-400">Le changement est visible immédiatement sur tout le site.</span>
        </div>
    </form>
</div>
@endsection
