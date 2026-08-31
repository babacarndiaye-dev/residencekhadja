@extends('layouts.app')

@section('title', 'Réserver — vos chambres')
@push('head')<meta name="robots" content="noindex,follow">@endpush

@section('content')
@php
    $maxRooms = (int) config('booking.max_rooms', 5);
    $seed = [
        'nights' => $quote['nights'],
        'room_count' => (int) collect($selected)->sum(),
        'room_lines' => $quote['room_lines'],
        'room_total' => $quote['room_total'],
        'extras_total' => $quote['extras_total'],
        'tourist_tax' => $quote['tourist_tax'],
        'discount' => $quote['discount'],
        'tax' => $quote['tax'],
        'total' => $quote['total'],
        'deposit' => $quote['deposit'],
    ];
@endphp
<section class="bg-sable-100 py-10 sm:py-14"
    x-data="{
        plan: @js(old('rate_plan', $ratePlan)),
        qty: @js((object) collect($rooms)->mapWithKeys(fn ($r) => [$r->slug => (int) ($selected[$r->slug] ?? 0)])->all()),
        q: @js($seed),
        issues: [],
        loading: false,
        _t: null,
        max: {{ $maxRooms }},
        get count() { return Object.values(this.qty).reduce((a, b) => a + (+b || 0), 0) },
        bump(slug, d, cap) {
            const n = Math.max(0, Math.min(cap, (+this.qty[slug] || 0) + d));
            if (this.count - (+this.qty[slug] || 0) + n > this.max) return;
            this.qty[slug] = n; this.refresh();
        },
        refresh() {
            clearTimeout(this._t);
            this._t = setTimeout(() => this.fetchQuote(), 250);
        },
        async fetchQuote() {
            this.loading = true;
            const fd = new FormData();
            fd.append('_token', this.$refs.form.querySelector('[name=_token]').value);
            fd.append('rate_plan', this.plan);
            Object.entries(this.qty).forEach(([s, n]) => { if (+n > 0) fd.append('lines[' + s + ']', n); });
            try {
                const r = await fetch('{{ route('booking.quote') }}', { method: 'POST', headers: { 'Accept': 'application/json' }, body: fd });
                const j = await r.json();
                if (j.quote) this.q = j.quote;
                this.issues = j.issues || [];
            } catch (e) { /* on garde le dernier devis */ }
            this.loading = false;
        },
        money(n) { return new Intl.NumberFormat('fr-FR').format(Math.round(n || 0)) + ' FCFA' },
    }"
>
    <x-container>
        <x-booking-steps current="room" class="mb-8" />

        <div class="grid gap-8 lg:grid-cols-[1.6fr_1fr] lg:items-start">
            <form method="POST" action="{{ route('booking.rooms.store') }}" x-ref="form" class="flex flex-col gap-6">
                @csrf

                <div>
                    <h1 class="font-display text-3xl font-semibold text-nuit-900 sm:text-4xl">Composez votre séjour</h1>
                    <p class="mt-2 text-ink-soft">
                        {{ pretty_date($booking['check_in']) }} → {{ pretty_date($booking['check_out']) }} ·
                        {{ $quote['nights'] }} nuit{{ $quote['nights'] > 1 ? 's' : '' }}.
                        Ajoutez une ou plusieurs chambres, de catégories différentes si besoin
                        (jusqu'à {{ $maxRooms }}).
                    </p>
                </div>

                {{-- Choix du tarif --}}
                <fieldset class="rounded-2xl bg-white p-5 shadow-card">
                    <legend class="px-1 text-sm font-semibold text-nuit-900">Conditions tarifaires</legend>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        @foreach ($ratePlans as $key => $rp)
                            <label class="flex cursor-pointer flex-col gap-1 rounded-xl border p-4 text-sm transition"
                                   :class="plan === '{{ $key }}' ? 'border-terracotta-500 bg-terracotta-50' : 'border-nuit-200'">
                                <span class="flex items-center gap-2 font-semibold text-nuit-900">
                                    <input type="radio" name="rate_plan" value="{{ $key }}" x-model="plan" x-on:change="refresh()"
                                           class="text-terracotta-500 focus:ring-terracotta-500">
                                    {{ $rp['label'] }}
                                </span>
                                <span class="text-xs text-nuit-500">{{ $rp['note'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                @error('lines')<p class="rounded-xl border border-terracotta-300 bg-terracotta-50 p-3 text-sm font-medium text-terracotta-800">{{ $message }}</p>@enderror
                <template x-if="issues.length">
                    <div class="rounded-xl border border-terracotta-300 bg-terracotta-50 p-3 text-sm text-terracotta-800">
                        <template x-for="msg in issues" :key="msg"><p x-text="msg"></p></template>
                    </div>
                </template>

                {{-- Catalogue de chambres avec sélecteur de quantité --}}
                <div class="flex flex-col gap-4">
                    @foreach ($rooms as $room)
                        @php $left = (int) ($availability[$room->id] ?? 0); @endphp
                        <div @class([
                            'flex flex-col gap-4 overflow-hidden rounded-2xl bg-white shadow-card ring-1 transition sm:flex-row',
                        ])
                             :class="(qty['{{ $room->slug }}'] || 0) > 0 ? 'ring-terracotta-500' : 'ring-sable-200'">
                            <div class="relative h-44 w-full shrink-0 sm:h-auto sm:w-52">
                                <img src="{{ $room['images'][0] }}" alt="{{ $room['name'] }}" width="360" height="270"
                                     loading="lazy" decoding="async" class="h-full w-full object-cover">
                                @if ($left <= 0)
                                    <span class="absolute left-2 top-2 rounded-full bg-nuit-900/90 px-2.5 py-1 text-xs font-semibold text-white">Complet</span>
                                @elseif ($left <= 3)
                                    <span class="absolute left-2 top-2 rounded-full bg-terracotta-500 px-2.5 py-1 text-xs font-semibold text-white">Plus que {{ $left }}</span>
                                @endif
                            </div>
                            <div class="flex flex-1 flex-col gap-2 p-5">
                                <div>
                                    <h3 class="text-xl text-nuit-900">{{ $room['name'] }}</h3>
                                    <p class="text-xs text-nuit-400">{{ $room['size'] }} m² · {{ $room['capacity'] }} pers. · {{ $room['bed'] }}</p>
                                </div>
                                <p class="text-sm leading-relaxed text-ink-soft">{{ $room['short'] }}</p>

                                <div class="mt-auto flex flex-wrap items-end justify-between gap-3 pt-2">
                                    <div class="text-sm">
                                        <span class="font-display text-lg font-semibold text-nuit-900">{{ money($room['price']) }}</span>
                                        <span class="text-nuit-400">/ nuit</span>
                                    </div>

                                    @if ($left <= 0)
                                        <span class="text-xs font-semibold text-nuit-400">Indisponible à ces dates</span>
                                    @else
                                        <div class="flex items-center gap-2">
                                            <button type="button" x-on:click="bump('{{ $room->slug }}', -1, {{ min($left, $maxRooms) }})"
                                                    class="grid h-9 w-9 place-items-center rounded-full border border-sable-300 text-nuit-700 hover:border-terracotta-400 disabled:opacity-30"
                                                    :disabled="(qty['{{ $room->slug }}'] || 0) < 1" aria-label="Retirer">
                                                <x-icon name="remove" :size="16" />
                                            </button>
                                            <input type="number" name="lines[{{ $room->slug }}]" min="0" max="{{ min($left, $maxRooms) }}"
                                                   x-model.number="qty['{{ $room->slug }}']" x-on:input="refresh()"
                                                   class="w-12 rounded-lg border border-sable-300 py-1.5 text-center text-sm font-semibold">
                                            <button type="button" x-on:click="bump('{{ $room->slug }}', 1, {{ min($left, $maxRooms) }})"
                                                    class="grid h-9 w-9 place-items-center rounded-full border border-sable-300 text-nuit-700 hover:border-terracotta-400 disabled:opacity-30"
                                                    :disabled="count >= max || (qty['{{ $room->slug }}'] || 0) >= {{ min($left, $maxRooms) }}" aria-label="Ajouter">
                                                <x-icon name="add" :size="16" />
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-between gap-4">
                    <a href="{{ route('booking.start') }}" class="text-sm font-semibold text-nuit-600 hover:text-terracotta-600">&larr; Retour aux dates</a>
                    <button type="submit" :disabled="count < 1"
                            class="inline-flex items-center gap-2 rounded-full bg-terracotta-500 px-7 py-3 text-sm font-semibold text-white transition hover:bg-terracotta-600 disabled:opacity-50">
                        <span x-text="count > 1 ? ('Continuer · ' + count + ' chambres') : 'Continuer vers les options'"></span>
                    </button>
                </div>
            </form>

            {{-- Devis en direct --}}
            <aside class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-luxe lg:sticky lg:top-28">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-xl font-semibold text-nuit-900">Votre devis</h2>
                    <span x-show="loading" x-cloak class="flex items-center gap-1 text-xs text-nuit-400">
                        <x-icon name="progress_activity" :size="14" class="animate-spin" /> calcul…
                    </span>
                </div>

                <dl class="flex flex-col gap-2 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-nuit-500">Séjour</dt>
                        <dd class="font-medium text-nuit-900">{{ pretty_date($booking['check_in']) }} → {{ pretty_date($booking['check_out']) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-nuit-500">Durée</dt>
                        <dd class="font-medium text-nuit-900"><span x-text="q.nights"></span> nuit(s)</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-nuit-500">Chambres</dt>
                        <dd class="font-medium text-nuit-900" x-text="q.room_count || count"></dd></div>
                    <a href="{{ route('booking.start') }}" class="mt-1 text-xs font-semibold text-terracotta-600 hover:underline">Modifier les dates</a>
                </dl>

                <div class="border-t border-sable-200 pt-4">
                    <template x-if="!q.room_lines || !q.room_lines.length">
                        <p class="text-sm text-nuit-500">Ajoutez une chambre pour voir le tarif.</p>
                    </template>
                    <dl class="flex flex-col gap-2 text-sm">
                        <template x-for="l in (q.room_lines || [])" :key="l.slug">
                            <div class="flex justify-between gap-4">
                                <dt class="text-nuit-600"><span x-text="l.name"></span> <span class="text-nuit-400" x-text="'×' + l.qty"></span>
                                    <span class="block text-xs text-nuit-400" x-text="money(l.nightly) + ' × ' + l.nights + ' nuit(s)'"></span></dt>
                                <dd class="font-medium text-nuit-900" x-text="money(l.line_total)"></dd>
                            </div>
                        </template>

                        <template x-if="q.discount">
                            <div class="flex justify-between gap-4 text-terracotta-700">
                                <dt x-text="q.discount?.label"></dt><dd class="font-medium" x-text="'−' + money(q.discount?.amount)"></dd>
                            </div>
                        </template>
                        <div class="flex justify-between gap-4"><dt class="text-nuit-500">Taxes & services</dt>
                            <dd class="text-nuit-700" x-text="money(q.tax)"></dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-nuit-500">Taxe de séjour</dt>
                            <dd class="text-nuit-700" x-text="money(q.tourist_tax)"></dd></div>

                        <div class="mt-2 flex items-baseline justify-between gap-4 border-t border-sable-200 pt-3">
                            <dt class="font-display text-base font-semibold text-nuit-900">Total</dt>
                            <dd class="font-display text-xl font-semibold text-nuit-900" x-text="money(q.total)"></dd>
                        </div>
                        <p class="text-xs text-nuit-400">Dépôt à la réservation : <span x-text="money(q.deposit)"></span> (30 %). Solde à l'hôtel.</p>
                    </dl>
                </div>
            </aside>
        </div>
    </x-container>
</section>
@endsection
