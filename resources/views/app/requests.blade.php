@extends('app.layout')
@section('title', 'Services & demandes')

@section('content')
<div class="flex flex-col gap-4"
     x-data="{ cat: @js($focusCat ?? 'all'), open: null }"
     x-init="setInterval(() => location.reload(), 60000)">

    {{-- ===================== CATALOGUE DE SERVICES ===================== --}}
    <section class="rounded-2xl bg-white p-5 shadow-card">
        <h1 class="font-display text-lg font-semibold text-nuit-900">Services en chambre</h1>
        <p class="mt-1 text-xs text-nuit-500">Un service payant est ajouté à votre folio (onglet « Séjour »).</p>

        {{-- Puces de filtre par catégorie --}}
        <div class="-mx-1 mt-4 flex gap-2 overflow-x-auto px-1 pb-1">
            <button type="button" x-on:click="cat = 'all'"
                    class="shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors"
                    :class="cat === 'all' ? 'bg-nuit-900 text-white' : 'bg-sable-100 text-nuit-600'">Tous</button>
            @foreach ($serviceCategories as $key => $label)
                @continue(($services[$key] ?? collect())->isEmpty())
                <button type="button" x-on:click="cat = '{{ $key }}'"
                        class="flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors"
                        :class="cat === '{{ $key }}' ? 'bg-nuit-900 text-white' : 'bg-sable-100 text-nuit-600'">
                    <x-icon name="{{ $categoryIcons[$key] ?? 'label' }}" :size="15" /> {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="mt-4 flex flex-col gap-5">
            @foreach ($serviceCategories as $catKey => $catLabel)
                @php $items = $services[$catKey] ?? collect(); @endphp
                @continue($items->isEmpty())
                <div x-show="cat === 'all' || cat === '{{ $catKey }}'" x-transition.opacity>
                    <h2 class="mb-2 flex items-center gap-1.5 text-[0.7rem] font-semibold uppercase tracking-[0.16em] text-nuit-400">
                        <x-icon name="{{ $categoryIcons[$catKey] ?? 'label' }}" :size="15" class="text-terracotta-500" /> {{ $catLabel }}
                    </h2>
                    <ul class="flex flex-col divide-y divide-sable-100 overflow-hidden rounded-2xl border border-sable-200 bg-white">
                        @foreach ($items as $s)
                            <li>
                                <button type="button" x-on:click="open = (open === '{{ $s['slug'] }}' ? null : '{{ $s['slug'] }}')"
                                        class="flex w-full items-center gap-3 px-3.5 py-3 text-left">
                                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-sable-50 text-terracotta-600">
                                        <x-icon name="{{ $categoryIcons[$s['category']] ?? 'label' }}" :size="19" />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold text-nuit-900">{{ $s['label'] }}</span>
                                        <span class="block truncate text-xs text-nuit-500">{{ $s['desc'] }}</span>
                                    </span>
                                    <span class="shrink-0 text-xs font-semibold {{ ($s['price'] ?? 0) > 0 ? 'text-terracotta-600' : 'text-emerald-700' }}">
                                        {{ ($s['price'] ?? 0) > 0 ? money($s['price']).(($s['qty'] ?? false) ? ' /u' : '') : 'Offert' }}
                                    </span>
                                    <span class="shrink-0 text-nuit-300 transition-transform" :class="open === '{{ $s['slug'] }}' && 'rotate-180'"><x-icon name="expand_more" :size="18" /></span>
                                </button>

                                <div x-show="open === '{{ $s['slug'] }}'" x-cloak class="px-3.5 pb-3.5">
                                    <form method="POST" action="{{ route('guest.requests.service') }}" class="flex flex-col gap-2">
                                        @csrf
                                        <input type="hidden" name="slug" value="{{ $s['slug'] }}">
                                        @if ($s['qty'] ?? false)
                                            <label class="flex items-center justify-between text-xs font-semibold text-nuit-700">
                                                Quantité
                                                <select name="quantity" class="border border-sable-300 px-2 py-1.5 text-sm">
                                                    @for ($i = 1; $i <= 5; $i++)<option value="{{ $i }}">{{ $i }}</option>@endfor
                                                </select>
                                            </label>
                                        @endif
                                        <textarea name="note" rows="2" placeholder="Précision (heure souhaitée, allergie, n° de vol…)"
                                                  class="border border-sable-300 px-3 py-2 text-sm focus:border-terracotta-500 focus:outline-none"></textarea>
                                        <button class="flex items-center justify-center gap-1.5 rounded-full bg-nuit-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-terracotta-500">
                                            <x-icon name="send" :size="16" /> Demander
                                            @if (($s['price'] ?? 0) > 0)<span class="text-white/70">· {{ money($s['price']) }}{{ ($s['qty'] ?? false) ? ' /u' : '' }}</span>@endif
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ===================== DEMANDE LIBRE ===================== --}}
    <section class="rounded-2xl bg-white p-5 shadow-card" x-data="{ show: false }">
        <button type="button" x-on:click="show = !show" class="flex w-full items-center justify-between text-sm font-semibold text-nuit-900">
            <span class="flex items-center gap-2"><x-icon name="edit_note" :size="18" class="text-terracotta-500" /> Autre demande</span>
            <span class="text-nuit-300 transition-transform" :class="show && 'rotate-180'"><x-icon name="expand_more" :size="18" /></span>
        </button>
        <form x-show="show" x-cloak method="POST" action="{{ route('guest.requests.store') }}" class="mt-3 flex flex-col gap-3">
            @csrf
            <select name="type" class="border border-sable-300 px-3 py-3 text-base focus:border-terracotta-500 focus:outline-none">
                @foreach ($types as $k => $label)
                    <option value="{{ $k }}" @selected($prefill === $k)>{{ $label }}</option>
                @endforeach
            </select>
            <textarea name="note" rows="2" placeholder="Décrivez votre demande"
                      class="border border-sable-300 px-3 py-2 text-sm focus:border-terracotta-500 focus:outline-none"></textarea>
            <button class="rounded-full bg-nuit-900 px-6 py-3 text-sm font-semibold text-white hover:bg-terracotta-500">Envoyer</button>
        </form>
    </section>

    {{-- ===================== HISTORIQUE ===================== --}}
    <section class="rounded-2xl bg-white p-5 shadow-card">
        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wider text-nuit-500">Mes demandes</h2>
        <ol class="flex flex-col gap-3">
            @forelse ($requests as $r)
                <li class="flex items-start justify-between gap-3 border-b border-sable-100 pb-3 last:border-0">
                    <div>
                        <p class="text-sm font-semibold text-nuit-900">{{ $r->typeLabel() }}</p>
                        @if ($r->charge_id)
                            <p class="flex items-center gap-1 text-xs font-semibold text-terracotta-600">
                                <x-icon name="receipt_long" :size="13" /> {{ money($r->chargeAmount()) }} · folio
                            </p>
                        @endif
                        @if ($r->note)<p class="text-xs text-nuit-500">{{ $r->note }}</p>@endif
                        <p class="mt-0.5 text-[0.7rem] text-nuit-400">{{ $r->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <span class="rounded-full px-2 py-0.5 text-[0.7rem] font-semibold
                            {{ ['open' => 'bg-laiton-100 text-laiton-800', 'acknowledged' => 'bg-nuit-100 text-nuit-700', 'done' => 'bg-emerald-100 text-emerald-800', 'cancelled' => 'bg-sable-200 text-nuit-400'][$r->status] }}">
                            {{ $r->statusLabel() }}
                        </span>
                        @if ($r->status === 'open')
                            <form method="POST" action="{{ route('guest.requests.cancel', $r) }}">
                                @csrf
                                <button class="text-[0.7rem] font-semibold text-terracotta-600">Annuler</button>
                            </form>
                        @endif
                    </div>
                </li>
            @empty
                <li class="py-4 text-center text-sm text-nuit-400">Aucune demande pour le moment.</li>
            @endforelse
        </ol>
    </section>
</div>
@endsection
