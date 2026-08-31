@extends('admin.layout')
@section('title', 'Paramètres')

@section('content')
<div class="flex flex-col gap-8">

    <a href="{{ route('admin.site_settings.edit') }}" class="flex items-center justify-between rounded-2xl bg-nuit-900 px-5 py-4 text-white hover:bg-nuit-800">
        <span>
            <span class="block font-display text-base font-semibold">Réglages du site</span>
            <span class="block text-xs text-nuit-200">Identité, contact, horaires, réseaux, réservation, fidélité, Wi-Fi, SEO, e-mails — tout au même endroit.</span>
        </span>
        <span class="text-lg">→</span>
    </a>

    <a href="{{ route('admin.splash.edit') }}" class="flex items-center justify-between rounded-2xl border border-nuit-200 bg-white px-5 py-4 hover:border-terracotta-400">
        <span>
            <span class="block font-display text-base font-semibold text-nuit-900">Écran d’accueil</span>
            <span class="block text-xs text-nuit-500">Animation d’introduction premium de la vitrine : logo, « Bienvenue », signature, durée, arrière-plan — avec aperçu.</span>
        </span>
        <span class="text-lg text-nuit-400">→</span>
    </a>

    <a href="{{ route('admin.branding.edit') }}" class="flex items-center justify-between rounded-2xl border border-nuit-200 bg-white px-5 py-4 hover:border-terracotta-400">
        <span>
            <span class="block font-display text-base font-semibold text-nuit-900">Identité visuelle</span>
            <span class="block text-xs text-nuit-500">Changer le logo (couleur + monochrome) — repris sur la vitrine, le back-office, les factures, les bulletins et l’app invité.</span>
        </span>
        <span class="text-lg text-nuit-400">→</span>
    </a>

    <section>
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-display text-lg font-semibold text-nuit-900">Catégories de chambre</h2>
            <a href="{{ route('admin.room_categories.index') }}" class="rounded-full border border-nuit-200 px-4 py-1.5 text-xs font-semibold text-nuit-700 hover:border-terracotta-400 hover:text-terracotta-600">
                Gérer les catégories (contenu, photos, création) →
            </a>
        </div>
        <p class="mb-3 text-xs text-nuit-400">Réglage rapide du tarif et de la capacité ci-dessous ; contenu vitrine complet via « Gérer les catégories ».</p>
        <div class="flex flex-col gap-3">
            @foreach ($categories as $c)
                <form method="POST" action="{{ route('admin.settings.category', $c) }}"
                      class="grid items-end gap-3 rounded-2xl bg-white p-4 shadow-card sm:grid-cols-[1.4fr_repeat(4,1fr)]">
                    @csrf @method('PUT')
                    <div>
                        <p class="font-medium text-nuit-900">{{ $c->name }}</p>
                        <p class="text-xs text-nuit-400">{{ $c->rooms_count }} chambres · {{ $c->slug }}</p>
                    </div>
                    <label class="text-xs font-semibold text-nuit-500">Tarif / nuit
                        <input type="number" name="price" value="{{ $c->price }}" min="0" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    </label>
                    <label class="text-xs font-semibold text-nuit-500">Capacité
                        <input type="number" name="capacity" value="{{ $c->capacity }}" min="1" max="10" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    </label>
                    <label class="flex items-center gap-2 text-xs text-nuit-600"><input type="checkbox" name="featured" value="1" @checked($c->featured) class="rounded border-nuit-300 text-terracotta-500"> Mise en avant</label>
                    <div class="flex items-center gap-2">
                        <label class="flex items-center gap-2 text-xs text-nuit-600"><input type="checkbox" name="is_active" value="1" @checked($c->is_active) class="rounded border-nuit-300 text-terracotta-500"> Active</label>
                        <button class="rounded-full bg-nuit-900 px-4 py-2 text-xs font-semibold text-white hover:bg-nuit-800">Enregistrer</button>
                    </div>
                </form>
            @endforeach
        </div>
    </section>

    <section>
        <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Plans tarifaires</h2>
        <div class="flex flex-col gap-3">
            @foreach ($ratePlans as $p)
                <form method="POST" action="{{ route('admin.settings.rate_plan', $p) }}"
                      class="grid items-end gap-3 rounded-2xl bg-white p-4 shadow-card sm:grid-cols-[1fr_1fr_2fr_auto]">
                    @csrf @method('PUT')
                    <div>
                        <p class="font-medium text-nuit-900">{{ $p->name }}</p>
                        <p class="text-xs text-nuit-400">{{ $p->key }}</p>
                    </div>
                    <label class="text-xs font-semibold text-nuit-500">Coefficient
                        <input type="number" step="0.01" name="multiplier" value="{{ $p->multiplier }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    </label>
                    <label class="text-xs font-semibold text-nuit-500">Note
                        <input type="text" name="note" value="{{ $p->note }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    </label>
                    <div class="flex items-center gap-2">
                        <label class="flex items-center gap-2 text-xs text-nuit-600"><input type="checkbox" name="is_active" value="1" @checked($p->is_active) class="rounded border-nuit-300 text-terracotta-500"> Actif</label>
                        <button class="rounded-full bg-nuit-900 px-4 py-2 text-xs font-semibold text-white hover:bg-nuit-800">Enregistrer</button>
                    </div>
                </form>
            @endforeach
        </div>
    </section>
</div>
@endsection
