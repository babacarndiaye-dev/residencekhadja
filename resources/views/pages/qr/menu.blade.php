<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#de6443">
    <title>{{ $venue?->name ?? 'Carte' }} · {{ config('hotel.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @include('partials.fonts')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    // Menu sérialisé pour le calcul du panier côté client.
    $menuJs = $categories->flatMap->items->mapWithKeys(fn ($it) => [$it->id => [
        'name' => $it->name,
        'price' => $it->price,
        'groups' => $it->optionGroups->map(fn ($g) => [
            'id' => $g->id, 'name' => $g->name, 'type' => $g->type, 'required' => $g->required,
            'options' => $g->options->map(fn ($o) => ['id' => $o->id, 'name' => $o->name, 'delta' => (int) $o->price_delta])->values(),
        ])->values(),
    ]])->toArray();
@endphp
<body class="bg-sable-50 text-ink"
      x-data="qrMenu({ code: @js($location->code), menu: @js($menuJs), orderable: @js($orderable) })">

    {{-- Confirmation d'ajout (le menu reste affiché, on continue à choisir) --}}
    <div x-show="toast" x-cloak x-transition
         class="pointer-events-none fixed inset-x-0 top-3 z-50 mx-auto flex max-w-lg justify-center px-4">
        <div class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-medium text-white shadow-luxe" x-text="toast"></div>
    </div>

    {{-- En-tête --}}
    <header class="sticky top-0 z-30 border-b border-sable-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-lg items-center justify-between gap-3 px-4 py-3">
            <div class="min-w-0">
                <p class="truncate font-display text-lg font-semibold text-nuit-900">{{ $venue?->name ?? 'Carte' }}</p>
                <p class="truncate text-xs text-nuit-400">{{ $location->label }}</p>
            </div>
            @if ($orderable)
                <button x-on:click="callOpen = true" class="shrink-0 rounded-full border border-nuit-200 px-3 py-1.5 text-xs font-semibold text-nuit-700">
                    Appeler
                </button>
            @endif
        </div>

        {{-- Nav catégories --}}
        <nav class="mx-auto flex max-w-lg gap-2 overflow-x-auto px-4 pb-2 text-sm [scrollbar-width:none]">
            @foreach ($categories as $cat)
                <a href="#cat-{{ $cat->id }}" class="shrink-0 rounded-full bg-sable-100 px-3 py-1 font-medium text-nuit-700">{{ $cat->name }}</a>
            @endforeach
        </nav>
    </header>

    <main class="mx-auto max-w-lg px-4 pb-32 pt-4">

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-terracotta-300 bg-terracotta-50 p-3 text-sm text-terracotta-800">
                <ul class="list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        @if (session('status'))
            <div class="mb-4 rounded-xl border border-laiton-200 bg-laiton-50 p-3 text-sm text-nuit-800">{{ session('status') }}</div>
        @endif

        @unless ($orderable)
            <p class="mb-4 rounded-xl bg-nuit-900 p-3 text-sm text-white/80">
                Consultation de la carte uniquement — la commande à table n’est pas activée sur ce point de vente.
            </p>
        @endunless

        {{-- Mes commandes en cours --}}
        @if ($myOrders->isNotEmpty())
            <section class="mb-6 rounded-2xl border border-nuit-200 bg-white p-4">
                <h2 class="text-sm font-semibold text-nuit-900">Vos commandes</h2>
                <ul class="mt-2 flex flex-col gap-2">
                    @foreach ($myOrders as $o)
                        <li class="flex items-center justify-between text-sm">
                            <span>{{ $o->reference }} · {{ money($o->total) }}</span>
                            <a href="{{ route('qr.track', [$location->code, $o->reference]) }}" class="font-semibold text-terracotta-600">
                                {{ $o->statusLabel() }} →
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Catégories & articles --}}
        @foreach ($categories as $cat)
            <section id="cat-{{ $cat->id }}" class="mb-8 scroll-mt-32">
                <h2 class="font-display text-xl font-semibold text-nuit-900">{{ $cat->name }}</h2>
                @if ($cat->description)<p class="mt-0.5 text-xs text-nuit-400">{{ $cat->description }}</p>@endif

                <div class="mt-3 flex flex-col gap-3">
                    @foreach ($cat->items as $item)
                        <article class="flex gap-3 rounded-2xl bg-white p-3 shadow-card">
                            @if ($item->image)
                                <img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}" width="96" height="96"
                                     loading="lazy" class="h-20 w-20 shrink-0 rounded-xl object-cover">
                            @endif
                            <div class="flex min-w-0 flex-1 flex-col">
                                <div class="flex items-start justify-between gap-2">
                                    <h3 class="font-semibold text-nuit-900">{{ $item->name }}</h3>
                                    @if ($item->is_signature)<span class="shrink-0 rounded-full bg-terracotta-100 px-2 py-0.5 text-[0.65rem] font-bold uppercase text-terracotta-700">Signature</span>@endif
                                </div>
                                @if ($item->description)<p class="mt-0.5 text-xs leading-relaxed text-ink-soft">{{ $item->description }}</p>@endif
                                @if ($item->story)<p class="mt-1 text-xs italic text-nuit-400">{{ $item->story }}</p>@endif
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @foreach (($item->tags ?? []) as $t)<span class="rounded bg-sable-100 px-1.5 py-0.5 text-[0.65rem] text-nuit-500">{{ $t }}</span>@endforeach
                                    @if ($item->allergens)<span class="text-[0.65rem] text-nuit-400">Allergènes : {{ implode(', ', $item->allergens) }}</span>@endif
                                </div>
                                <div class="mt-2 flex items-center justify-between">
                                    <span class="font-semibold text-nuit-900">{{ money($item->price) }}</span>
                                    @if ($orderable)
                                        <div class="shrink-0" x-data="{ opt: {{ $item->optionGroups->isNotEmpty() ? 'true' : 'false' }}, id: {{ $item->id }} }">
                                            <button type="button" x-show="opt || qtyInCart(id) === 0" x-on:click="pick(id)"
                                                    class="rounded-full bg-terracotta-500 px-4 py-1.5 text-sm font-semibold text-white">
                                                Ajouter
                                            </button>
                                            <div x-show="!opt && qtyInCart(id) > 0" x-cloak
                                                 class="flex items-center gap-2 rounded-full bg-terracotta-500 px-2 py-1 text-white">
                                                <button type="button" x-on:click="bumpItem(id, -1)" class="px-1.5 text-lg leading-none">−</button>
                                                <span class="min-w-[1rem] text-center text-sm font-semibold" x-text="qtyInCart(id)"></span>
                                                <button type="button" x-on:click="bumpItem(id, 1)" class="px-1.5 text-lg leading-none">+</button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
    </main>

    @if ($orderable)
        {{-- Barre panier --}}
        <div x-show="count > 0" x-cloak
             class="fixed inset-x-0 bottom-0 z-30 mx-auto max-w-lg p-3">
            <button x-on:click="cartOpen = true" :class="pulse && 'scale-[1.03]'"
                    class="flex w-full items-center justify-between rounded-2xl bg-nuit-900 px-5 py-3.5 text-white shadow-luxe transition-transform">
                <span class="flex items-center gap-2 text-sm font-semibold">
                    <span class="rounded-full bg-white/20 px-2 py-0.5" x-text="count"></span>
                    Voir le panier · commander
                </span>
                <span class="font-semibold" x-text="fmt(total)"></span>
            </button>
        </div>

        {{-- Feuille options --}}
        <div x-show="sheet" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-nuit-900/40" x-on:click.self="sheet = null">
            <div class="w-full max-w-lg rounded-t-3xl bg-white p-5" x-show="sheet" x-transition>
                <template x-if="sheet">
                    <div>
                        <div class="flex items-start justify-between">
                            <h3 class="font-display text-lg font-semibold text-nuit-900" x-text="sheet.name"></h3>
                            <button x-on:click="sheet = null" class="text-nuit-400">✕</button>
                        </div>

                        <div class="mt-3 flex max-h-[50vh] flex-col gap-4 overflow-y-auto">
                            <template x-for="g in sheet.groups" :key="g.id">
                                <fieldset>
                                    <legend class="text-sm font-semibold text-nuit-800">
                                        <span x-text="g.name"></span>
                                        <span class="text-xs font-normal text-nuit-400" x-text="g.required ? '(requis)' : '(facultatif)'"></span>
                                    </legend>
                                    <div class="mt-1.5 flex flex-col gap-1.5">
                                        <template x-for="o in g.options" :key="o.id">
                                            <label class="flex items-center justify-between rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                                                <span class="flex items-center gap-2">
                                                    <input :type="g.type === 'multi' ? 'checkbox' : 'radio'" :name="'g'+g.id"
                                                           :value="o.id" x-model="sel[g.id]"
                                                           class="text-terracotta-500 focus:ring-terracotta-500">
                                                    <span x-text="o.name"></span>
                                                </span>
                                                <span class="text-xs text-nuit-500" x-show="o.delta" x-text="(o.delta>0?'+':'')+fmt(o.delta)"></span>
                                            </label>
                                        </template>
                                    </div>
                                </fieldset>
                            </template>

                            <label class="text-sm font-semibold text-nuit-800">Remarque
                                <input type="text" x-model="sheetNote" maxlength="180" placeholder="Sans oignon, bien cuit…"
                                       class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                            </label>
                        </div>

                        <div class="mt-4 flex items-center gap-3">
                            <div class="flex items-center gap-3 rounded-full border border-nuit-200 px-3 py-1.5">
                                <button x-on:click="sheetQty = Math.max(1, sheetQty-1)" class="text-lg">−</button>
                                <span class="w-5 text-center text-sm font-semibold" x-text="sheetQty"></span>
                                <button x-on:click="sheetQty = Math.min(20, sheetQty+1)" class="text-lg">+</button>
                            </div>
                            <button x-on:click="addFromSheet()"
                                    class="flex-1 rounded-full bg-terracotta-500 px-4 py-2.5 text-sm font-semibold text-white">
                                Ajouter · <span x-text="fmt(sheetUnit * sheetQty)"></span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Drawer panier + formulaire de commande --}}
        <div x-show="cartOpen" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-nuit-900/40" x-on:click.self="cartOpen = false">
            <form method="POST" action="{{ route('qr.order', $location->code) }}"
                  x-on:submit="serialize($el)"
                  class="flex max-h-[88vh] w-full max-w-lg flex-col rounded-t-3xl bg-white">
                @csrf
                <input type="hidden" name="idempotency_key" :value="idem">
                <div x-ref="lines"></div>

                <div class="flex items-center justify-between border-b border-sable-200 p-4">
                    <h3 class="font-display text-lg font-semibold text-nuit-900">Votre panier</h3>
                    <button type="button" x-on:click="cartOpen = false" class="text-nuit-400">✕</button>
                </div>

                <div class="flex-1 overflow-y-auto p-4">
                    <template x-if="count === 0"><p class="py-8 text-center text-sm text-nuit-400">Panier vide.</p></template>
                    <ul class="flex flex-col gap-3">
                        <template x-for="(l, i) in cart" :key="l.key">
                            <li class="flex gap-3 border-b border-sable-100 pb-3">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-nuit-900" x-text="l.name"></p>
                                    <p class="text-xs text-nuit-400" x-text="l.options.map(o => o.name).join(', ')"></p>
                                    <p class="text-xs italic text-nuit-400" x-show="l.note" x-text="l.note"></p>
                                    <div class="mt-1 flex items-center gap-3">
                                        <div class="flex items-center gap-2 rounded-full border border-nuit-200 px-2 py-0.5 text-sm">
                                            <button type="button" x-on:click="setQty(i, l.quantity-1)">−</button>
                                            <span x-text="l.quantity"></span>
                                            <button type="button" x-on:click="setQty(i, l.quantity+1)">+</button>
                                        </div>
                                        <button type="button" x-on:click="removeLine(i)" class="text-xs text-terracotta-600">Retirer</button>
                                    </div>
                                </div>
                                <span class="text-sm font-semibold text-nuit-900" x-text="fmt(l.unit_price * l.quantity)"></span>
                            </li>
                        </template>
                    </ul>

                    <div class="mt-4 flex flex-col gap-3" x-show="count > 0">
                        <label class="text-sm font-semibold text-nuit-800">Votre nom
                            <input type="text" name="guest_name" maxlength="80"
                                   value="{{ $reservation?->guest?->fullName() }}"
                                   class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                        </label>
                        <label class="text-sm font-semibold text-nuit-800">Remarque générale
                            <input type="text" name="note" maxlength="300"
                                   class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                        </label>

                        @if ($reservation)
                            <label class="flex items-center gap-2 rounded-lg bg-sable-100 p-3 text-sm text-nuit-800">
                                <input type="checkbox" name="charge_to_room" value="1" class="rounded border-nuit-300 text-terracotta-500">
                                Imputer sur la chambre {{ $reservation->room->number }} ({{ $reservation->guest->last_name }})
                            </label>
                        @elseif (! $location->isRoomService())
                            <div x-data="{ room: false }" class="rounded-lg bg-sable-100 p-3 text-sm">
                                <label class="flex items-center gap-2 text-nuit-800">
                                    <input type="checkbox" name="charge_to_room" value="1" x-model="room" class="rounded border-nuit-300 text-terracotta-500">
                                    Imputer sur ma chambre
                                </label>
                                <div x-show="room" x-cloak class="mt-2 grid grid-cols-2 gap-2">
                                    <input type="text" name="room_number" placeholder="N° chambre" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                                    <input type="text" name="room_last_name" placeholder="Nom de famille" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="border-t border-sable-200 p-4" x-show="count > 0">
                    <div class="mb-3 flex items-center justify-between text-sm">
                        <span class="text-nuit-500">Total (taxes comprises)</span>
                        <span class="font-display text-lg font-semibold text-nuit-900" x-text="fmt(total)"></span>
                    </div>
                    <button type="submit" class="w-full rounded-full bg-terracotta-500 px-4 py-3.5 text-sm font-semibold text-white">
                        Commander
                    </button>
                    <p class="mt-2 text-center text-[0.7rem] text-nuit-400">Envoi en cuisine immédiat. Règlement au service ou à la chambre.</p>
                </div>
            </form>
        </div>

        {{-- Appeler le serveur --}}
        <div x-show="callOpen" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-nuit-900/40" x-on:click.self="callOpen = false">
            <div class="w-full max-w-lg rounded-t-3xl bg-white p-5">
                <div class="flex items-center justify-between">
                    <h3 class="font-display text-lg font-semibold text-nuit-900">Appeler le serveur</h3>
                    <button x-on:click="callOpen = false" class="text-nuit-400">✕</button>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    @foreach (['assistance' => 'Assistance', 'water' => 'De l’eau', 'cutlery' => 'Des couverts', 'info' => 'Une information', 'other' => 'Autre'] as $k => $label)
                        <form method="POST" action="{{ route('qr.call', $location->code) }}">
                            @csrf
                            <input type="hidden" name="type" value="{{ $k }}">
                            <button class="w-full rounded-xl border border-nuit-200 px-3 py-3 text-sm font-medium text-nuit-800">{{ $label }}</button>
                        </form>
                    @endforeach
                    <form method="POST" action="{{ route('qr.bill', $location->code) }}">
                        @csrf
                        <button class="w-full rounded-xl bg-nuit-900 px-3 py-3 text-sm font-semibold text-white">Demander l’addition</button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <script>
        function qrMenu({ code, menu, orderable }) {
            const KEY = 'qr_cart_' + code;
            return {
                menu, orderable,
                cart: [],
                idem: crypto.randomUUID(),
                cartOpen: false, callOpen: false,
                sheet: null, sel: {}, sheetNote: '', sheetQty: 1,
                toast: '', pulse: false, _t: null, _p: null,

                init() {
                    try { this.cart = JSON.parse(localStorage.getItem(KEY)) || []; } catch (e) { this.cart = []; }
                    this.$watch('cart', v => { try { localStorage.setItem(KEY, JSON.stringify(v)); } catch (e) {} }, { deep: true });
                },

                // Retour visuel léger : on reste sur la carte pour continuer à choisir.
                flash(msg) {
                    this.toast = msg;
                    this.pulse = true;
                    clearTimeout(this._t); clearTimeout(this._p);
                    this._t = setTimeout(() => { this.toast = ''; }, 1600);
                    this._p = setTimeout(() => { this.pulse = false; }, 260);
                },
                // Ligne "simple" (sans option ni remarque) d'un article donné.
                lineFor(id) { return this.cart.find(l => l.item_id === id && l.options.length === 0 && !l.note); },
                qtyInCart(id) { const l = this.lineFor(id); return l ? l.quantity : 0; },
                bumpItem(id, delta) {
                    const l = this.lineFor(id);
                    if (l) {
                        const q = l.quantity + delta;
                        const i = this.cart.indexOf(l);
                        if (q <= 0) this.cart.splice(i, 1); else this.cart[i].quantity = Math.min(20, q);
                    } else if (delta > 0) {
                        this.addLine(id, [], '', 1);
                    }
                },

                get count() { return this.cart.reduce((n, l) => n + l.quantity, 0); },
                get subtotal() { return this.cart.reduce((n, l) => n + l.unit_price * l.quantity, 0); },
                get total() { return Math.round(this.subtotal * (1 + {{ config('menu.tax_rate', 0) }})); },
                fmt(n) { return new Intl.NumberFormat('fr-FR').format(Math.round(n)) + ' FCFA'; },

                pick(id) {
                    const m = this.menu[id];
                    if (!m) return;
                    if (!m.groups.length) { this.addLine(id, [], '', 1); return; }
                    this.sel = {}; this.sheetNote = ''; this.sheetQty = 1;
                    m.groups.forEach(g => { this.sel[g.id] = g.type === 'multi' ? [] : ''; });
                    this.sheet = { id, ...m };
                },
                get sheetUnit() {
                    if (!this.sheet) return 0;
                    let p = this.sheet.price;
                    this.sheet.groups.forEach(g => {
                        const picked = g.type === 'multi' ? (this.sel[g.id] || []) : (this.sel[g.id] ? [this.sel[g.id]] : []);
                        picked.forEach(oid => { const o = g.options.find(x => x == oid || x.id == oid); if (o) p += o.delta; });
                    });
                    return p;
                },
                addFromSheet() {
                    const opts = [];
                    for (const g of this.sheet.groups) {
                        const picked = g.type === 'multi' ? (this.sel[g.id] || []) : (this.sel[g.id] ? [this.sel[g.id]] : []);
                        if (g.required && picked.length === 0) { alert('Choix requis : ' + g.name); return; }
                        picked.forEach(oid => { const o = g.options.find(x => x.id == oid); if (o) opts.push({ option_id: o.id, name: o.name, price_delta: o.delta }); });
                    }
                    this.addLine(this.sheet.id, opts, this.sheetNote, this.sheetQty, this.sheetUnit);
                    this.sheet = null;
                },
                addLine(id, options, note, qty, unit) {
                    const m = this.menu[id];
                    const u = unit ?? m.price;
                    const sig = id + '|' + options.map(o => o.option_id).sort().join(',') + '|' + (note || '');
                    const found = this.cart.find(l => l.key === sig);
                    if (found) { found.quantity = Math.min(20, found.quantity + qty); }
                    else { this.cart.push({ key: sig, item_id: id, name: m.name, unit_price: u, quantity: qty, options, note: note || '' }); }
                    this.flash(qty > 1 ? (qty + ' × ' + m.name + ' ajoutés') : (m.name + ' ajouté'));
                },
                setQty(i, q) { if (q <= 0) this.cart.splice(i, 1); else this.cart[i].quantity = Math.min(20, q); },
                removeLine(i) { this.cart.splice(i, 1); },

                serialize(form) {
                    this.$refs.lines.innerHTML = '';
                    this.cart.forEach((l, i) => {
                        const add = (name, val) => {
                            const el = document.createElement('input');
                            el.type = 'hidden'; el.name = `items[${i}][${name}]`; el.value = val;
                            this.$refs.lines.appendChild(el);
                        };
                        add('item_id', l.item_id);
                        add('quantity', l.quantity);
                        if (l.note) add('note', l.note);
                        l.options.forEach((o, j) => {
                            const el = document.createElement('input');
                            el.type = 'hidden'; el.name = `items[${i}][options][${j}]`; el.value = o.option_id;
                            this.$refs.lines.appendChild(el);
                        });
                    });
                },
            };
        }
    </script>
</body>
</html>
