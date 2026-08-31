@extends('admin.layout')
@section('title', 'Caisse restaurant')

@section('content')
<div class="flex flex-col gap-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-display text-lg font-semibold text-nuit-900">Caisse restaurant</h1>
            <p class="text-sm text-nuit-500">{{ auth()->user()->name }} · {{ now()->translatedFormat('l d F Y') }}</p>
        </div>
        @if ($session)
            <a href="{{ route('admin.pos.register') }}" class="rounded-full bg-terracotta-500 px-6 py-2.5 text-sm font-semibold text-white hover:bg-terracotta-600">Nouvelle vente</a>
        @endif
    </div>

    @if ($needsPin)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            Vous autorisez les opérations sensibles au comptoir mais n'avez pas encore de PIN caisse.
            <a href="{{ route('admin.pos.pin.edit') }}" class="font-semibold underline">Définir mon PIN →</a>
        </div>
    @endif

    {{-- ─────────── Prise de poste (identification légère) ─────────── --}}
    @if ($operator)
        <div class="flex flex-wrap items-center justify-between gap-2 rounded-2xl bg-nuit-900 px-5 py-3 text-sm text-white">
            <span><span class="text-white/50">Poste :</span> <b>{{ $stations[$station] ?? $station }}</b> <span class="text-white/50">· Opérateur :</span> <b>{{ $operator }}</b></span>
            <form method="POST" action="{{ route('admin.pos.station.exit') }}">@csrf
                <button class="rounded-full border border-white/20 px-3 py-1 text-xs font-semibold text-white/80 hover:bg-white/10">Libérer le poste</button>
            </form>
        </div>
    @else
        <form method="POST" action="{{ route('admin.pos.station') }}"
              x-data="{ user: '', pin: '', station: '{{ array_key_first($stations) }}' }"
              class="rounded-2xl bg-white p-5 shadow-card">
            @csrf
            <h2 class="font-display text-lg font-semibold text-nuit-900">Qui êtes-vous ?</h2>
            <p class="mt-1 text-sm text-nuit-500">Choisissez votre profil, saisissez votre PIN et votre poste pour démarrer le service.</p>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($posUsers as $u)
                    <button type="button" x-on:click="user = '{{ $u->id }}'"
                            class="rounded-lg border px-3 py-2 text-sm font-semibold"
                            :class="user === '{{ $u->id }}' ? 'border-terracotta-500 bg-terracotta-50 text-terracotta-700' : 'border-sable-300 text-nuit-600'">{{ $u->name }}</button>
                @endforeach
            </div>
            <input type="hidden" name="user_id" :value="user">

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <label class="text-xs font-semibold text-nuit-500">PIN
                    <input type="password" name="pin" x-model="pin" inputmode="numeric" maxlength="6" required
                           class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2.5 text-center font-display text-xl tracking-[0.3em]">
                </label>
                <label class="text-xs font-semibold text-nuit-500">Poste
                    <select name="station" x-model="station" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2.5 text-sm">
                        @foreach ($stations as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                    </select>
                </label>
            </div>

            <button class="mt-4 rounded-full bg-nuit-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-terracotta-500" :disabled="!user || !pin">Prendre le poste</button>
        </form>
    @endif

    @unless ($session)
        {{-- ─────────── Ouverture de caisse ─────────── --}}
        <form method="POST" action="{{ route('admin.pos.session.open') }}"
              x-data="{ denoms: {}, float: 0, get denomTotal() { return Object.entries(this.denoms).reduce((s,[v,n]) => s + v * (parseInt(n)||0), 0); } }"
              x-effect="if (denomTotal) float = denomTotal"
              class="rounded-2xl bg-white p-6 shadow-card">
            @csrf
            <h2 class="font-display text-lg font-semibold text-nuit-900">Ouverture de caisse</h2>
            <p class="mt-1 text-sm text-nuit-500">Comptez le fond de caisse pour démarrer le service.</p>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Caisse
                    <select name="finance_account_id" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                        @foreach ($cashAccounts as $a)
                            <option value="{{ $a->id }}" @selected($account && $a->id === $account->id)>{{ $a->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Fond de caisse (FCFA)
                    <input type="number" name="opening_float" x-model.number="float" min="0" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                </label>
            </div>

            <p class="mt-5 text-xs font-semibold uppercase tracking-wider text-nuit-400">Détail des coupures (facultatif)</p>
            <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-5">
                @foreach ($denominations as $d)
                    <label class="flex items-center gap-2 rounded-lg border border-sable-200 px-2 py-1.5 text-sm">
                        <span class="w-14 shrink-0 text-right text-xs font-semibold text-nuit-500">{{ number_format($d, 0, ',', ' ') }}</span>
                        <span class="text-nuit-300">×</span>
                        <input type="number" min="0" name="denominations[{{ $d }}]" x-model="denoms[{{ $d }}]" class="w-full rounded border border-sable-200 px-1.5 py-1 text-right">
                    </label>
                @endforeach
            </div>

            <button class="mt-5 rounded-full bg-nuit-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-terracotta-500">Ouvrir la caisse</button>
        </form>
    @else
        {{-- ─────────── KPIs du service ─────────── --}}
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $tiles = [
                    ['CA F&B du jour', money($fnb['revenue']), 'payments'],
                    ['Tickets', $fnb['orders'], 'receipt_long'],
                    ['Panier moyen', money($fnb['avg_check']), 'trending_up'],
                    ['En préparation', $openOrders, 'skillet'],
                    ['Room service en cours', $roomServicePending, 'room_service'],
                    ['En attente', $heldOrders, 'pause_circle'],
                ];
            @endphp
            @foreach ($tiles as [$label, $value, $icon])
                <div class="rounded-2xl bg-white p-4 shadow-card">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wider text-nuit-400">{{ $label }}</p>
                        <x-icon :name="$icon" size="18" class="text-nuit-300" />
                    </div>
                    <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        {{-- ─────────── Session en cours ─────────── --}}
        <div class="grid gap-6 lg:grid-cols-[1fr_1.1fr]">
            <section class="rounded-2xl bg-white p-5 shadow-card">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-lg font-semibold text-nuit-900">Session ouverte</h2>
                    <a href="{{ route('admin.pos.session.report') }}" target="_blank" class="text-xs font-semibold text-terracotta-600 hover:underline">Rapport X ↗</a>
                </div>
                <dl class="mt-3 flex flex-col gap-1.5 text-sm">
                    <div class="flex justify-between"><dt class="text-nuit-500">Caisse</dt><dd class="font-medium text-nuit-900">{{ $session->account->name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-nuit-500">Ouverte</dt><dd>{{ $session->opened_at->format('d/m à H:i') }} · {{ $session->openedBy->name ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-nuit-500">Fond de caisse</dt><dd>{{ money($session->opening_float) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-nuit-500">Tickets de la session</dt><dd>{{ $session->orders_count }}</dd></div>
                    <div class="flex justify-between border-t border-sable-200 pt-2 font-semibold"><dt class="text-nuit-500">Espèces théoriques</dt><dd class="text-nuit-900">{{ money($session->expected()) }}</dd></div>
                </dl>
            </section>

            <form method="POST" action="{{ route('admin.pos.session.close', $session) }}"
                  x-data="{ denoms: {}, counted: 0, get denomTotal() { return Object.entries(this.denoms).reduce((s,[v,n]) => s + v * (parseInt(n)||0), 0); } }"
                  x-effect="if (denomTotal) counted = denomTotal"
                  onsubmit="return confirm('Clôturer la caisse ? Cette action est définitive.')"
                  class="rounded-2xl bg-white p-5 shadow-card">
                @csrf
                <h2 class="font-display text-lg font-semibold text-nuit-900">Clôture &amp; comptage</h2>
                <p class="mt-1 text-sm text-nuit-500">Comptez les espèces en caisse coupure par coupure.</p>

                <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach ($denominations as $d)
                        <label class="flex items-center gap-2 rounded-lg border border-sable-200 px-2 py-1.5 text-sm">
                            <span class="w-14 shrink-0 text-right text-xs font-semibold text-nuit-500">{{ number_format($d, 0, ',', ' ') }}</span>
                            <span class="text-nuit-300">×</span>
                            <input type="number" min="0" name="denominations[{{ $d }}]" x-model="denoms[{{ $d }}]" class="w-full rounded border border-sable-200 px-1.5 py-1 text-right">
                        </label>
                    @endforeach
                </div>

                <div class="mt-3 flex items-center gap-3">
                    <label class="flex-1 text-xs font-semibold text-nuit-500">Montant compté (FCFA)
                        <input type="number" name="counted_amount" min="0" required x-model.number="counted"
                               class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                    </label>
                    <p class="pt-4 text-xs text-nuit-400">Théorique<br><b class="text-nuit-900">{{ money($session->expected()) }}</b></p>
                </div>
                <input type="text" name="note" placeholder="Justification d'un éventuel écart" class="mt-2 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">

                <button class="mt-4 rounded-full bg-nuit-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-terracotta-500">Clôturer la caisse</button>
            </form>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.pos.floor') }}" class="rounded-full border border-sable-300 px-4 py-2 text-sm font-semibold text-nuit-600 hover:border-terracotta-400">Plan de salle</a>
            <a href="{{ route('admin.pos.room_service') }}" class="rounded-full border border-sable-300 px-4 py-2 text-sm font-semibold text-nuit-600 hover:border-terracotta-400">Room service</a>
            <a href="{{ route('admin.pos.breakfast') }}" class="rounded-full border border-sable-300 px-4 py-2 text-sm font-semibold text-nuit-600 hover:border-terracotta-400">Petit-déjeuner</a>
            <a href="{{ route('admin.pos.reports') }}" class="rounded-full border border-sable-300 px-4 py-2 text-sm font-semibold text-nuit-600 hover:border-terracotta-400">Rapports caisse</a>
            <a href="{{ route('admin.orders.index') }}" class="rounded-full border border-sable-300 px-4 py-2 text-sm font-semibold text-nuit-600 hover:border-terracotta-400">Toutes les commandes</a>
            <a href="{{ route('admin.kds.index') }}" class="rounded-full border border-sable-300 px-4 py-2 text-sm font-semibold text-nuit-600 hover:border-terracotta-400">Cuisine (KDS)</a>
        </div>
    @endunless
</div>
@endsection
