<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <title>Vente · {{ $venue->name }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @include('partials.material-symbols')
    @include('partials.fonts')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak]{display:none!important}
        .pos-scroll::-webkit-scrollbar{width:8px}
        .pos-scroll::-webkit-scrollbar-thumb{background:#d9d9d9;border-radius:8px}
        .prod{transition:border-color .1s ease, transform .1s ease}
        .prod:active{transform:scale(.97)}
        .flash{animation:flash .45s ease}
        @keyframes flash{0%{box-shadow:inset 0 0 0 2px #de6443}100%{box-shadow:inset 0 0 0 2px transparent}}
    </style>
</head>
<body class="h-screen overflow-hidden bg-sable-100 text-nuit-900"
      x-data="pos({
          menu: @js($menu), rooms: @js($rooms), held: @js($held),
          saleTypes: @js($saleTypes), venueId: {{ $venue->id }},
          servers: @js($servers), discountLimits: @js($discountLimits),
          tables: @js($tables), preTableId: @js($preTableId), resumeOrder: @js($resume),
          preServerId: @js($preServerId), preSaleType: @js($preSaleType),
          guestsUrl: '{{ route('admin.pos.guests') }}',
      })"
      x-init="init()">

<div class="flex h-screen flex-col">

    {{-- ─────────────── Barre supérieure ─────────────── --}}
    <header class="flex items-center justify-between gap-4 border-b border-sable-300 bg-white px-4 py-2.5">
        <div class="flex items-center gap-3">
            <button x-on:click="newSale()" class="flex items-center gap-1.5 rounded-md border border-sable-300 px-3 py-1.5 text-sm font-semibold text-nuit-700 hover:bg-sable-50">
                <x-icon name="add" size="18" /> Nouveau
            </button>
            <div class="hidden rounded-md border border-sable-300 p-0.5 text-xs font-semibold sm:flex">
                @foreach ($saleTypes as $k => $label)
                    <button x-on:click="saleType = '{{ $k }}'"
                            class="rounded px-3 py-1.5 transition-colors"
                            :class="saleType === '{{ $k }}' ? 'bg-nuit-900 text-white' : 'text-nuit-500 hover:text-nuit-800'">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-4 text-xs text-nuit-500">
            @if ($venues->count() > 1)
                <select onchange="location.href='{{ route('admin.pos.register') }}?venue='+this.value"
                        class="rounded-md border border-sable-300 bg-white px-2.5 py-1.5">
                    @foreach ($venues as $v)<option value="{{ $v->id }}" @selected($v->id === $venue->id)>{{ $v->name }}</option>@endforeach
                </select>
            @endif
            @unless ($session)
                <a href="{{ route('admin.pos.index') }}" class="flex items-center gap-1 rounded-md bg-amber-50 px-2.5 py-1.5 font-semibold text-amber-700">
                    <x-icon name="warning" size="16" /> Caisse fermée
                </a>
            @endunless
            <span class="font-mono" x-text="clock"></span>
            <div class="flex items-center gap-2">
                <div class="text-right leading-tight">
                    <p class="font-semibold text-nuit-900">{{ auth()->user()->name }}</p>
                    <p class="text-[0.7rem] text-nuit-400">{{ $venue->name }}</p>
                </div>
                <span class="grid h-8 w-8 place-items-center rounded-full bg-terracotta-100 text-sm font-bold text-terracotta-700">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
            </div>
            <a href="{{ route('admin.pos.index') }}" class="rounded-md border border-sable-300 px-3 py-1.5 hover:border-terracotta-400 hover:text-terracotta-600">Quitter</a>
        </div>
    </header>

    <div class="flex min-h-0 flex-1">

        {{-- ─────────────── Zone 1 · catégories ─────────────── --}}
        <nav class="pos-scroll flex w-48 shrink-0 flex-col gap-1 overflow-y-auto border-r border-sable-300 bg-white p-2">
            <div class="relative mb-1">
                <x-icon name="search" size="18" class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-nuit-300" />
                <input type="search" x-model="search" placeholder="Rechercher"
                       class="w-full rounded-md border border-sable-300 bg-sable-50 py-2 pl-8 pr-2 text-sm focus:border-terracotta-400 focus:outline-none">
            </div>
            <template x-for="cat in menu" :key="cat.id">
                <button x-on:click="activeCat = cat.id; search = ''"
                        class="flex items-center gap-2 rounded-md px-2.5 py-2.5 text-left text-sm font-semibold transition-colors"
                        :class="activeCat === cat.id && !search ? 'bg-white text-terracotta-700 ring-1 ring-terracotta-500' : 'text-nuit-600 hover:bg-sable-100'">
                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded bg-sable-100 text-base" x-text="cat.icon"></span>
                    <span class="flex-1 leading-tight" x-text="cat.name"></span>
                    <x-icon name="chevron_right" size="18" x-show="activeCat === cat.id && !search" class="text-terracotta-500" />
                </button>
            </template>
        </nav>

        {{-- ─────────────── Zone 2 · articles ─────────────── --}}
        <main class="pos-scroll flex-1 overflow-y-auto bg-sable-50 p-3">
            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
                <template x-for="item in visibleItems" :key="item.id">
                    <button x-on:click="addItem(item); pulse($event)"
                            class="prod group flex flex-col overflow-hidden rounded-lg border border-sable-200 bg-white text-left hover:border-nuit-300">
                        <div class="relative aspect-[4/3] w-full overflow-hidden bg-sable-100">
                            <template x-if="item.image">
                                <img :src="item.image" alt="" loading="lazy" class="h-full w-full object-cover">
                            </template>
                            <template x-if="!item.image">
                                <div class="grid h-full w-full place-items-center text-nuit-300">
                                    <x-icon name="restaurant" size="30" />
                                </div>
                            </template>
                        </div>
                        <div class="flex flex-1 flex-col items-center gap-0.5 px-1.5 py-2 text-center">
                            <p class="line-clamp-2 text-[0.78rem] font-semibold leading-tight text-nuit-900" x-text="item.name"></p>
                            <p class="text-xs font-semibold text-nuit-500" x-text="money(item.price)"></p>
                        </div>
                    </button>
                </template>
            </div>
            <p x-show="!visibleItems.length" class="mt-16 text-center text-sm text-nuit-400">Aucun article dans cette sélection.</p>
        </main>

        {{-- ─────────────── Zone 3 · ticket ─────────────── --}}
        <aside class="flex w-[24rem] shrink-0 flex-col border-l border-sable-300 bg-white">
            <div class="flex flex-col gap-2 border-b border-sable-200 px-4 py-3 text-sm">
                <div class="flex items-center justify-between gap-2">
                    <button type="button" x-on:click="guestPanel = true" class="flex items-center gap-1.5 text-nuit-500 hover:text-terracotta-600">
                        <x-icon name="person" size="18" />
                        <span x-text="customerName || 'Client hôtel'" :class="!customerName && 'text-nuit-400'"></span>
                    </button>
                    <button type="button" x-on:click="tablePicker = true" class="flex items-center gap-1.5 rounded border border-transparent px-1.5 py-1 text-sm text-nuit-500 hover:border-sable-300 hover:text-terracotta-600">
                        <x-icon name="restaurant" size="18" />
                        <span x-text="tableLabel || 'Table'" :class="!tableLabel && 'text-nuit-400'"></span>
                    </button>
                </div>
                <label class="flex items-center gap-1.5 text-nuit-500">
                    <x-icon name="room_service" size="18" />
                    <select x-model="serverId" class="flex-1 rounded border border-transparent px-1 py-1 text-sm hover:border-sable-300 focus:border-terracotta-400 focus:outline-none">
                        <option value="">Serveur —</option>
                        <template x-for="s in servers" :key="s.id"><option :value="s.id" x-text="s.name"></option></template>
                    </select>
                </label>
            </div>

            <div class="pos-scroll min-h-0 flex-1 overflow-y-auto">
                <template x-for="line in cart" :key="line.key">
                    <div class="flex items-start gap-2.5 border-b border-sable-100 px-4 py-3">
                        <template x-if="line.image">
                            <img :src="line.image" alt="" class="h-10 w-10 shrink-0 rounded object-cover">
                        </template>
                        <template x-if="!line.image">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded bg-sable-100"><x-icon name="restaurant" size="18" class="text-nuit-400" /></span>
                        </template>

                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold leading-tight text-nuit-900" x-text="line.name"></p>
                            <p x-show="line.options.length" class="truncate text-xs text-nuit-400" x-text="line.options.map(o => o.name).join(' · ')"></p>
                            <div class="mt-1.5 flex items-center gap-2">
                                <div class="flex items-center rounded border border-sable-300 text-nuit-600">
                                    <button x-on:click="dec(line)" class="grid h-6 w-6 place-items-center hover:bg-sable-100"><x-icon name="remove" size="16" /></button>
                                    <span class="w-6 text-center text-xs font-bold" x-text="line.quantity"></span>
                                    <button x-on:click="inc(line)" class="grid h-6 w-6 place-items-center hover:bg-sable-100"><x-icon name="add" size="16" /></button>
                                </div>
                                <button x-on:click="removeLine(line)" class="text-xs text-nuit-400 hover:text-terracotta-600">Retirer</button>
                            </div>
                        </div>

                        <div class="shrink-0 text-right">
                            <p class="font-semibold text-nuit-900" x-text="money(line.quantity * line.unit_price)"></p>
                            <p class="text-xs text-nuit-400" x-text="line.quantity + ' × ' + money(line.unit_price)"></p>
                        </div>
                    </div>
                </template>
                <div x-show="!cart.length" class="mt-20 flex flex-col items-center gap-2 text-nuit-300">
                    <x-icon name="receipt_long" size="40" />
                    <p class="text-sm">Touchez un article pour commencer</p>
                </div>
            </div>

            <div class="border-t border-sable-200 px-4 py-3">
                <div class="flex items-center justify-between py-1 text-sm">
                    <span class="flex items-center gap-1.5 text-nuit-600"><x-icon name="percent" size="16" /> Remise</span>
                    <div class="flex items-center gap-1">
                        <div class="flex rounded border border-sable-300 text-[0.7rem] font-semibold">
                            <button x-on:click="discountMode = 'amount'" :class="discountMode === 'amount' ? 'bg-nuit-900 text-white' : 'text-nuit-500'" class="rounded-l px-2 py-1">F</button>
                            <button x-on:click="discountMode = 'percent'" :class="discountMode === 'percent' ? 'bg-nuit-900 text-white' : 'text-nuit-500'" class="rounded-r px-2 py-1">%</button>
                        </div>
                        <input type="number" min="0" x-model.number="discountValue"
                               class="w-20 rounded border border-sable-300 px-2 py-1 text-right text-sm focus:border-terracotta-400 focus:outline-none">
                    </div>
                </div>
                <div x-show="discountOverLimit" class="flex items-center gap-1 py-0.5 text-[0.7rem] font-semibold text-terracotta-600">
                    <x-icon name="lock" size="14" /> Remise au-delà du seuil — PIN manager requis
                </div>
                <div class="flex items-center justify-between border-t border-sable-100 pt-2 text-sm">
                    <span class="text-nuit-500">Sous-total</span>
                    <span class="tabular-nums text-nuit-700" x-text="money(subtotal)"></span>
                </div>
                <div x-show="discountAmount > 0" class="flex items-center justify-between text-sm text-terracotta-600">
                    <span>Remise</span><span class="tabular-nums" x-text="'−' + money(discountAmount)"></span>
                </div>

                <div class="mt-2 grid grid-cols-3 gap-1.5">
                    <button x-on:click="hold()" :disabled="!cart.length" class="rounded-md border border-sable-300 py-2 text-xs font-semibold text-nuit-600 hover:bg-sable-50 disabled:opacity-30">En attente</button>
                    <button x-on:click="showHeld = true" class="col-span-2 rounded-md border border-sable-300 py-2 text-xs font-semibold text-nuit-600 hover:bg-sable-50">
                        Reprendre<span x-show="held.length" class="ml-1 rounded-full bg-terracotta-500 px-1.5 text-[0.65rem] text-white" x-text="held.length"></span>
                    </button>
                </div>

                <button x-on:click="openPay()" :disabled="!cart.length"
                        class="mt-2 flex w-full items-center justify-between rounded-lg bg-terracotta-500 px-4 py-3.5 text-white transition-colors hover:bg-terracotta-600 active:scale-[.99] disabled:opacity-30">
                    <span class="text-sm font-semibold" x-text="'Payer ' + cart.length + ' produit' + (cart.length > 1 ? 's' : '')"></span>
                    <span class="font-display text-xl font-bold tabular-nums" x-text="money(total)"></span>
                </button>
            </div>
        </aside>
    </div>
</div>

{{-- ═══════════════ Modale options ═══════════════ --}}
<div x-show="optItem" x-cloak x-transition.opacity class="fixed inset-0 z-30 grid place-items-center bg-nuit-900/50 p-4">
    <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-luxe">
        <p class="font-display text-xl font-semibold text-nuit-900" x-text="optItem?.name"></p>
        <template x-for="g in (optItem?.option_groups || [])" :key="g.id">
            <div class="mt-4">
                <p class="text-[0.7rem] font-semibold uppercase tracking-[0.15em] text-nuit-400" x-text="g.name + (g.required ? ' — requis' : '')"></p>
                <div class="mt-1.5 flex flex-col gap-1.5">
                    <template x-for="o in g.options" :key="o.id">
                        <label class="flex cursor-pointer items-center justify-between rounded-md border px-3.5 py-2.5 text-sm transition-colors"
                               :class="(Array.isArray(optSel[g.id]) ? optSel[g.id].includes(String(o.id)) || optSel[g.id].includes(o.id) : String(optSel[g.id]) === String(o.id)) ? 'border-terracotta-500 bg-terracotta-50' : 'border-sable-300'">
                            <span class="flex items-center gap-2.5">
                                <input :type="g.type === 'single' ? 'radio' : 'checkbox'" :name="'g'+g.id" :value="o.id" x-model="optSel[g.id]" class="accent-terracotta-500">
                                <span x-text="o.name"></span>
                            </span>
                            <span x-show="o.price_delta" class="text-xs font-semibold text-nuit-500" x-text="(o.price_delta > 0 ? '+' : '') + money(o.price_delta)"></span>
                        </label>
                    </template>
                </div>
            </div>
        </template>
        <div class="mt-6 flex gap-2">
            <button x-on:click="optItem = null" class="flex-1 rounded-md border border-sable-300 py-3 text-sm font-semibold text-nuit-600">Annuler</button>
            <button x-on:click="confirmOptions()" class="flex-1 rounded-md bg-nuit-900 py-3 text-sm font-semibold text-white hover:bg-terracotta-500">Ajouter</button>
        </div>
    </div>
</div>

{{-- ═══════════════ Modale reprendre ═══════════════ --}}
<div x-show="showHeld" x-cloak x-transition.opacity class="fixed inset-0 z-30 grid place-items-center bg-nuit-900/50 p-4">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-luxe">
        <div class="mb-3 flex items-center justify-between">
            <p class="font-display text-xl font-semibold text-nuit-900">Commandes en attente</p>
            <button x-on:click="showHeld = false" class="grid h-8 w-8 place-items-center rounded-full text-nuit-400 hover:bg-sable-100"><x-icon name="close" size="18" /></button>
        </div>
        <template x-for="h in held" :key="h.id">
            <div class="mb-1.5 flex items-center gap-2">
                <button x-on:click="resume(h)" class="flex flex-1 items-center justify-between rounded-md border border-sable-200 px-4 py-3 text-left text-sm hover:border-terracotta-300 hover:bg-terracotta-50">
                    <span><b class="text-nuit-900" x-text="h.label || h.reference"></b><span class="text-nuit-400"> · </span><span x-text="h.count"></span> art.</span>
                    <span class="font-semibold text-nuit-700" x-text="money(h.total)"></span>
                </button>
                <button x-on:click="askVoid(h)" title="Annuler" class="grid h-9 w-9 shrink-0 place-items-center rounded-md border border-sable-200 text-nuit-400 hover:border-red-300 hover:text-red-600"><x-icon name="delete" size="16" /></button>
            </div>
        </template>
        <p x-show="!held.length" class="py-6 text-center text-sm text-nuit-400">Aucune commande en attente.</p>
    </div>
</div>

{{-- ═══════════════ Modale paiement ═══════════════ --}}
<div x-show="payOpen" x-cloak x-transition.opacity class="fixed inset-0 z-30 grid place-items-center bg-nuit-900/60 p-4">
    <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-luxe">
        <template x-if="!result">
            <div>
                <p class="text-center text-[0.7rem] uppercase tracking-[0.2em] text-nuit-400">Montant à régler</p>
                <p class="text-center font-display text-4xl font-bold text-nuit-900" x-text="money(total)"></p>

                <label class="mt-4 block text-[0.7rem] font-semibold uppercase tracking-[0.15em] text-nuit-400">Nom du client (facultatif)</label>
                <input x-model="customerName" placeholder="Ex. M. Diop"
                       class="mt-1 w-full rounded-md border border-sable-300 px-4 py-2.5 text-sm focus:border-terracotta-400 focus:outline-none">

                {{-- Règlements --}}
                <div class="mt-4 flex flex-col gap-2">
                    <template x-for="(p, i) in payments" :key="i">
                        <div class="rounded-md border border-sable-300 p-2.5">
                            <div class="flex items-center gap-2">
                                <select x-model="p.method" class="flex-1 rounded border border-sable-300 px-2 py-1.5 text-sm">
                                    <template x-for="m in methods" :key="m.k"><option :value="m.k" x-text="m.label"></option></template>
                                </select>
                                <input type="number" min="0" x-model.number="p.amount" class="w-28 rounded border border-sable-300 px-2 py-1.5 text-right text-sm">
                                <button x-show="payments.length > 1" x-on:click="removePayment(i)" class="text-nuit-400 hover:text-red-600"><x-icon name="close" size="16" /></button>
                            </div>
                            <div x-show="p.method === 'especes'" class="mt-2 flex items-center gap-2">
                                <span class="text-[0.7rem] uppercase tracking-wider text-nuit-400">Reçu</span>
                                <input type="number" min="0" x-model.number="p.tendered" class="w-28 rounded border border-sable-300 px-2 py-1.5 text-right text-sm">
                                <span class="text-xs text-nuit-500">Rendu <b x-text="money(Math.max(0, (p.tendered || 0) - (p.amount || 0)))"></b></span>
                            </div>
                            <div x-show="p.method === 'chambre'" class="mt-2 flex flex-col gap-1.5">
                                <input x-model="guestQuery" x-on:input.debounce.300ms="searchGuests()" placeholder="Rechercher : chambre, nom, réf."
                                       class="w-full rounded border border-sable-300 px-2 py-1.5 text-sm">
                                <select x-model.number="p.reservation_id" class="w-full rounded border border-sable-300 px-2 py-1.5 text-sm">
                                    <option value="">— Choisir la chambre —</option>
                                    <template x-for="g in chambreOptions" :key="g.id">
                                        <option :value="g.id" x-text="'Ch. ' + g.room + ' · ' + g.guest"></option>
                                    </template>
                                </select>
                                <template x-if="folioFor(p.reservation_id)">
                                    <div class="grid grid-cols-4 gap-1 text-center text-[0.65rem] text-nuit-500">
                                        <div>Resto<br><b class="text-nuit-800" x-text="money(folioFor(p.reservation_id).restaurant)"></b></div>
                                        <div>Bar<br><b class="text-nuit-800" x-text="money(folioFor(p.reservation_id).bar)"></b></div>
                                        <div>R. service<br><b class="text-nuit-800" x-text="money(folioFor(p.reservation_id).room_service)"></b></div>
                                        <div>Folio<br><b class="text-nuit-800" x-text="money(folioFor(p.reservation_id).total)"></b></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                    <span class="text-[0.7rem] font-semibold uppercase tracking-wider text-nuit-400">+ Règlement :</span>
                    <template x-for="m in methods" :key="m.k">
                        <button x-on:click="addPayment(m.k)" class="rounded-full bg-sable-100 px-2.5 py-1 text-xs font-semibold hover:bg-sable-200" x-text="m.label"></button>
                    </template>
                </div>

                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                    <span class="text-[0.7rem] font-semibold uppercase tracking-wider text-nuit-400">Diviser :</span>
                    <template x-for="n in [2, 3, 4, 5]" :key="n">
                        <button x-on:click="splitEqually(n)" class="rounded-full bg-sable-100 px-2.5 py-1 text-xs font-semibold hover:bg-sable-200" x-text="'en ' + n"></button>
                    </template>
                </div>

                <div class="mt-3 flex items-center justify-between rounded-md bg-sable-50 px-3 py-2 text-sm">
                    <span class="text-nuit-500">Reste à payer</span>
                    <b class="font-display tabular-nums" :class="remaining > 0 ? 'text-terracotta-600' : 'text-emerald-700'" x-text="money(remaining)"></b>
                </div>

                <p x-show="error" class="mt-3 rounded-md bg-terracotta-50 px-3 py-2 text-sm text-terracotta-700" x-text="error"></p>

                <div class="mt-5 flex gap-2">
                    <button x-on:click="payOpen = false" class="flex-1 rounded-md border border-sable-300 py-3 text-sm font-semibold text-nuit-600">Annuler</button>
                    <button x-on:click="pay()" :disabled="busy || remaining > 0"
                            class="flex-[1.4] rounded-md bg-nuit-900 py-3 text-sm font-semibold text-white hover:bg-terracotta-500 disabled:opacity-50"
                            x-text="busy ? 'Traitement…' : 'Valider le paiement'"></button>
                </div>
            </div>
        </template>

        <template x-if="result">
            <div class="text-center">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-emerald-100 text-emerald-700"><x-icon name="check" size="34" /></span>
                <p class="mt-3 font-display text-xl font-semibold text-nuit-900">Paiement effectué</p>
                <p class="text-sm text-nuit-500" x-text="result.reference"></p>
                <p x-show="result.change > 0" class="mt-1 text-sm">Monnaie à rendre : <b class="font-display text-base text-nuit-900" x-text="money(result.change)"></b></p>
                <div class="mt-5 flex gap-2">
                    <a :href="result.receipt_url" target="_blank" class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-md border border-sable-300 py-3 text-sm font-semibold text-nuit-600"><x-icon name="print" size="16" /> Ticket</a>
                    <button x-on:click="finish()" class="flex-1 rounded-md bg-terracotta-500 py-3 text-sm font-semibold text-white">Nouvelle vente</button>
                </div>
            </div>
        </template>
    </div>
</div>

{{-- ═══════════════ Modale sélecteur de table ═══════════════ --}}
<div x-show="tablePicker" x-cloak x-transition.opacity class="fixed inset-0 z-30 grid place-items-center bg-nuit-900/50 p-4">
    <div class="flex max-h-[80vh] w-full max-w-lg flex-col rounded-xl bg-white p-6 shadow-luxe">
        <div class="mb-3 flex items-center justify-between">
            <p class="font-display text-xl font-semibold text-nuit-900">Choisir une table</p>
            <button x-on:click="tablePicker = false" class="grid h-8 w-8 place-items-center rounded-full text-nuit-400 hover:bg-sable-100"><x-icon name="close" size="18" /></button>
        </div>
        <div class="mb-3 flex flex-wrap gap-2">
            <button x-on:click="pickTable(null)" class="rounded-full border border-sable-300 px-3 py-1.5 text-xs font-semibold text-nuit-600">Sans table / à emporter</button>
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto">
            <template x-for="(group, zone) in tablesByZone" :key="zone">
                <div class="mb-3">
                    <p class="mb-1 text-[0.7rem] font-semibold uppercase tracking-[0.15em] text-nuit-400" x-text="zone"></p>
                    <div class="grid grid-cols-3 gap-1.5 sm:grid-cols-4">
                        <template x-for="t in group" :key="t.id">
                            <button x-on:click="pickTable(t)"
                                    class="rounded-md border px-2 py-2 text-sm font-semibold"
                                    :class="t.status === 'libre' ? 'border-emerald-200 text-nuit-700 hover:border-emerald-400' : 'border-terracotta-300 bg-terracotta-50 text-terracotta-700'"
                                    x-text="t.label"></button>
                        </template>
                    </div>
                </div>
            </template>
            <p x-show="!tables.length" class="py-6 text-center text-sm text-nuit-400">Aucune table configurée.</p>
        </div>
    </div>
</div>

{{-- ═══════════════ Panneau client hôtel ═══════════════ --}}
<div x-show="guestPanel" x-cloak x-transition.opacity class="fixed inset-0 z-30 grid place-items-center bg-nuit-900/50 p-4">
    <div class="flex max-h-[80vh] w-full max-w-md flex-col rounded-xl bg-white p-6 shadow-luxe">
        <div class="mb-3 flex items-center justify-between">
            <p class="font-display text-xl font-semibold text-nuit-900">Client hôtel</p>
            <button x-on:click="guestPanel = false" class="grid h-8 w-8 place-items-center rounded-full text-nuit-400 hover:bg-sable-100"><x-icon name="close" size="18" /></button>
        </div>
        <input x-model="guestQuery" x-on:input.debounce.300ms="searchGuests()" placeholder="Chambre, nom, réf. ou lien carte d'accueil"
               class="w-full rounded-md border border-sable-300 px-3 py-2.5 text-sm focus:border-terracotta-400 focus:outline-none">
        <div class="mt-2 min-h-0 flex-1 overflow-y-auto">
            <template x-for="g in guestResults" :key="g.id">
                <button x-on:click="pickGuest(g)" class="mb-1 flex w-full flex-col rounded-md border border-sable-200 px-3 py-2 text-left hover:border-terracotta-300 hover:bg-terracotta-50">
                    <span class="text-sm font-semibold text-nuit-900">Ch. <span x-text="g.room"></span> · <span x-text="g.guest"></span></span>
                    <span class="mt-0.5 text-xs text-nuit-500">
                        Resto <b x-text="money(g.folio.restaurant)"></b> ·
                        Bar <b x-text="money(g.folio.bar)"></b> ·
                        R.S. <b x-text="money(g.folio.room_service)"></b> ·
                        Folio <b x-text="money(g.folio.total)"></b>
                    </span>
                </button>
            </template>
            <p x-show="!guestResults.length" class="py-6 text-center text-sm text-nuit-400">Saisissez au moins 2 caractères.</p>
        </div>
    </div>
</div>

{{-- ═══════════════ Modale PIN manager ═══════════════ --}}
<div x-show="pinModal" x-cloak x-transition.opacity class="fixed inset-0 z-40 grid place-items-center bg-nuit-900/70 p-4">
    <div class="w-full max-w-xs rounded-xl bg-white p-6 text-center shadow-luxe">
        <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-terracotta-100 text-terracotta-700"><x-icon name="lock" size="24" /></span>
        <p class="mt-3 font-display text-lg font-semibold text-nuit-900">Autorisation manager</p>
        <p class="text-xs text-nuit-500" x-text="pinFor === 'void' ? 'Annulation de la commande ' + (voidTarget?.label || voidTarget?.reference) : 'Remise au-delà du seuil autorisé'"></p>

        <div x-show="pinFor === 'void'" class="mt-3">
            <input x-model="voidReason" placeholder="Motif de l'annulation" class="w-full rounded-md border border-sable-300 px-3 py-2 text-sm">
        </div>
        <input x-model="managerPin" type="password" inputmode="numeric" maxlength="6" placeholder="PIN"
               class="mt-3 w-full rounded-md border border-sable-300 px-3 py-3 text-center font-display text-2xl tracking-[0.3em] focus:border-terracotta-400 focus:outline-none">
        <p x-show="pinError" class="mt-2 text-sm text-terracotta-700" x-text="pinError"></p>

        <div class="mt-4 flex gap-2">
            <button x-on:click="pinModal = false; managerPin = ''; pinError = ''" class="flex-1 rounded-md border border-sable-300 py-2.5 text-sm font-semibold text-nuit-600">Annuler</button>
            <button x-on:click="submitPin()" class="flex-1 rounded-md bg-nuit-900 py-2.5 text-sm font-semibold text-white hover:bg-terracotta-500">Valider</button>
        </div>
    </div>
</div>

<script>
    function pos(cfg) {
        const CHECKOUT = '{{ route('admin.pos.checkout') }}';
        const HOLD = '{{ route('admin.pos.hold') }}';
        const AUTHORIZE = '{{ route('admin.pos.authorize') }}';
        const VOID_BASE = '{{ url('admin/pos/vente') }}';

        return {
            menu: cfg.menu, rooms: cfg.rooms, held: cfg.held, servers: cfg.servers,
            venueId: cfg.venueId, limits: cfg.discountLimits,
            tables: cfg.tables || [], guestsUrl: cfg.guestsUrl,
            activeCat: cfg.menu[0]?.id ?? null,
            search: '',
            cart: [],
            saleType: 'restaurant', tableId: null, tableLabel: '', customerName: '', serverId: '',
            discountMode: 'amount', discountValue: 0,
            optItem: null, optSel: {},
            showHeld: false, tablePicker: false,
            guestPanel: false, guestQuery: '', guestResults: [], guestPick: null,
            payOpen: false, payments: [], error: '', busy: false,
            result: null,
            resumeId: null,
            clock: '',
            pinModal: false, pinFor: 'pay', managerPin: '', pinError: '', voidTarget: null, voidReason: '',
            methods: [
                { k: 'especes', label: 'Espèces' }, { k: 'wave', label: 'Wave' },
                { k: 'orange_money', label: 'Orange M.' }, { k: 'free_money', label: 'Free M.' },
                { k: 'carte', label: 'Carte' }, { k: 'chambre', label: 'Chambre' },
            ],

            init() {
                this.tick(); setInterval(() => this.tick(), 30000);
                if (cfg.preServerId) this.serverId = String(cfg.preServerId);
                if (cfg.preSaleType && this.saleTypesKeys.includes(cfg.preSaleType)) this.saleType = cfg.preSaleType;
                if (cfg.resumeOrder) this.resume(cfg.resumeOrder);
                if (cfg.preTableId) { const t = this.tables.find(x => x.id === cfg.preTableId); if (t) this.pickTable(t); }
            },
            get saleTypesKeys() { return Object.keys(@js($saleTypes)); },
            tick() { this.clock = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }); },
            money(n) { return new Intl.NumberFormat('fr-FR').format(Math.round(n || 0)) + ' F'; },
            pulse(e) { const el = e.currentTarget; el.classList.remove('flash'); void el.offsetWidth; el.classList.add('flash'); },

            get tablesByZone() {
                return this.tables.reduce((acc, t) => { (acc[t.zone || 'Sans zone'] ||= []).push(t); return acc; }, {});
            },
            pickTable(t) { this.tableId = t?.id || null; this.tableLabel = t?.label || ''; this.tablePicker = false; },

            // Chambres proposées à l'imputation : séjours en cours + résultats de recherche, dédupliqués.
            get chambreOptions() {
                const m = new Map();
                [...this.rooms, ...this.guestResults].forEach(x => m.set(x.id, { id: x.id, room: x.room, guest: x.guest }));
                return [...m.values()];
            },
            folioFor(id) {
                return this.guestResults.find(g => g.id === id)?.folio || null;
            },
            async searchGuests() {
                const q = this.guestQuery.trim();
                const ref = (q.match(/HRK-[A-Z0-9-]+/i) || [])[0];
                const term = ref || q;
                if (term.length < 2) { this.guestResults = []; return; }
                try {
                    const r = await fetch(this.guestsUrl + '?q=' + encodeURIComponent(term), { headers: { 'Accept': 'application/json' } });
                    this.guestResults = r.ok ? ((await r.json()).results || []) : [];
                } catch (e) { this.guestResults = []; }
            },
            pickGuest(g) {
                this.customerName = g.guest;
                this.guestPick = g;
                for (const p of this.payments) if (p.method === 'chambre') p.reservation_id = g.id;
                this.guestPanel = false;
            },
            splitEqually(n) {
                const base = Math.floor(this.total / n);
                this.payments = Array.from({ length: n }, (_, i) => ({
                    method: 'especes',
                    amount: i === n - 1 ? this.total - base * (n - 1) : base,
                    tendered: null, reservation_id: '',
                }));
            },

            get visibleItems() {
                if (this.search) {
                    const s = this.search.toLowerCase();
                    return this.menu.flatMap(c => c.items).filter(i => i.name.toLowerCase().includes(s));
                }
                return (this.menu.find(c => c.id === this.activeCat)?.items) || [];
            },
            get subtotal() { return this.cart.reduce((t, l) => t + l.quantity * l.unit_price, 0); },
            get discountAmount() {
                if (this.discountMode === 'percent') return Math.min(this.subtotal, Math.round(this.subtotal * (this.discountValue || 0) / 100));
                return Math.min(this.subtotal, Math.max(0, this.discountValue || 0));
            },
            get total() { return Math.max(0, this.subtotal - this.discountAmount); },
            get discountOverLimit() {
                if (this.discountAmount <= 0) return false;
                const pct = this.subtotal > 0 ? this.discountAmount / this.subtotal * 100 : 0;
                return pct > this.limits.max_percent || this.discountAmount > this.limits.max_amount;
            },
            get paidSum() { return this.payments.reduce((s, p) => s + (Math.round(+p.amount) || 0), 0); },
            get remaining() { return Math.max(0, this.total - this.paidSum); },

            addItem(item) {
                if (item.has_options) {
                    this.optItem = item;
                    this.optSel = {};
                    item.option_groups.forEach(g => { this.optSel[g.id] = g.type === 'single' ? '' : []; });
                    return;
                }
                this.pushLine(item, [], []);
            },
            confirmOptions() {
                const it = this.optItem;
                const ids = [], snap = [];
                for (const g of it.option_groups) {
                    const sel = this.optSel[g.id];
                    const picked = Array.isArray(sel) ? sel : (sel ? [sel] : []);
                    if (g.required && !picked.length) return;
                    for (const oid of picked) {
                        const o = g.options.find(x => String(x.id) === String(oid));
                        if (o) { ids.push(o.id); snap.push({ id: o.id, name: o.name, price_delta: o.price_delta }); }
                    }
                }
                this.pushLine(it, ids, snap);
                this.optItem = null;
            },
            pushLine(item, optionIds, snap) {
                const unit = item.price + snap.reduce((s, o) => s + (o.price_delta || 0), 0);
                const key = item.id + ':' + optionIds.slice().sort().join(',');
                const found = this.cart.find(l => l.key === key);
                if (found) { found.quantity++; return; }
                this.cart.push({ key, item_id: item.id, name: item.name, image: item.image || null, unit_price: unit, quantity: 1, options: snap, option_ids: optionIds, note: '' });
            },
            inc(l) { l.quantity++; },
            dec(l) { l.quantity--; if (l.quantity <= 0) this.removeLine(l); },
            removeLine(l) { this.cart = this.cart.filter(x => x !== l); },
            newSale() { this.cart = []; this.discountValue = 0; this.discountMode = 'amount'; this.tableId = null; this.tableLabel = ''; this.customerName = ''; this.serverId = cfg.preServerId ? String(cfg.preServerId) : ''; this.resumeId = null; this.payments = []; this.guestPick = null; this.guestResults = []; this.guestQuery = ''; },
            finish() { this.payOpen = false; this.result = null; this.newSale(); },

            payload(extra = {}) {
                return {
                    venue_id: this.venueId, sale_type: this.saleType,
                    table_id: this.tableId || null, table_label: this.tableLabel || null,
                    customer_name: this.customerName || null,
                    server_id: this.serverId || null,
                    discount: { type: this.discountMode, value: Math.max(0, this.discountValue || 0) },
                    lines: this.cart.map(l => ({ item_id: l.item_id, quantity: l.quantity, options: l.option_ids, note: l.note || null })),
                    ...extra,
                };
            },
            hold() {
                if (!this.cart.length) return;
                fetch(HOLD, this.opts(this.payload()))
                    .then(r => r.json()).then(() => { this.newSale(); location.reload(); });
            },
            resume(h) {
                this.newSale();
                this.resumeId = h.id;
                this.tableId = h.table_id || null;
                this.tableLabel = h.label || '';
                this.cart = h.lines.map(l => {
                    const opts = (l.options || []);
                    const ids = opts.map(o => o.id).filter(Boolean);
                    return { key: l.item_id + ':' + ids.slice().sort().join(','), item_id: l.item_id, name: l.name, image: l.image || null,
                             unit_price: l.unit_price, quantity: l.quantity, options: opts, option_ids: ids, note: '' };
                });
                this.showHeld = false;
            },
            openPay() {
                if (!this.cart.length) return;
                this.payOpen = true; this.error = ''; this.result = null;
                this.payments = [{ method: 'especes', amount: this.total, tendered: this.total, reservation_id: '' }];
            },
            addPayment(method) {
                this.payments.push({ method, amount: this.remaining || 0, tendered: method === 'especes' ? (this.remaining || 0) : null, reservation_id: '' });
            },
            removePayment(i) { this.payments.splice(i, 1); },

            pay() {
                if (this.paidSum < this.total) { this.error = 'Le règlement ne couvre pas le total.'; return; }
                for (const p of this.payments) {
                    if (p.method === 'chambre' && !p.reservation_id) { this.error = 'Choisissez la chambre à imputer.'; return; }
                }
                if (this.discountOverLimit && !this.managerPin) { this.pinFor = 'pay'; this.pinError = ''; this.pinModal = true; return; }

                this.busy = true; this.error = '';
                const extra = {
                    payments: this.payments.map(p => ({
                        method: p.method,
                        amount: Math.round(+p.amount || 0),
                        tendered: p.method === 'especes' ? Math.round(+p.tendered || 0) : null,
                        reservation_id: p.method === 'chambre' ? p.reservation_id : null,
                    })),
                };
                if (this.discountOverLimit) extra.manager_pin = this.managerPin;
                if (this.resumeId) extra.resume_order_id = this.resumeId;

                fetch(CHECKOUT, this.opts(this.payload(extra)))
                    .then(async r => { const d = await r.json(); if (!r.ok) throw d; return d; })
                    .then(d => { this.result = d; this.held = this.held.filter(h => h.id !== this.resumeId); this.managerPin = ''; })
                    .catch(e => { this.error = (e && (e.message || (e.errors && Object.values(e.errors)[0][0]))) || 'Paiement refusé.'; })
                    .finally(() => this.busy = false);
            },

            askVoid(h) {
                this.voidTarget = h; this.pinFor = 'void'; this.voidReason = ''; this.managerPin = ''; this.pinError = '';
                this.showHeld = false; this.pinModal = true;
            },
            submitPin() {
                if (!this.managerPin) { this.pinError = 'Saisissez le PIN.'; return; }
                if (this.pinFor === 'void' && !this.voidReason.trim()) { this.pinError = 'Motif requis.'; return; }
                fetch(AUTHORIZE, this.opts({ pin: this.managerPin }))
                    .then(r => r.json())
                    .then(d => {
                        if (!d.ok) { this.pinError = 'PIN invalide.'; return; }
                        this.pinModal = false;
                        if (this.pinFor === 'void') this.doVoid();
                        else this.pay();
                    });
            },
            doVoid() {
                fetch(VOID_BASE + '/' + this.voidTarget.id + '/annuler', this.opts({ reason: this.voidReason, manager_pin: this.managerPin }))
                    .then(async r => { if (!r.ok) throw await r.json(); return r.json(); })
                    .then(() => { this.held = this.held.filter(h => h.id !== this.voidTarget.id); this.managerPin = ''; })
                    .catch(() => { this.pinError = 'Annulation refusée.'; this.pinModal = true; });
            },

            opts(body) {
                return {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                               'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(body),
                };
            },
        };
    }
</script>
</body>
</html>
