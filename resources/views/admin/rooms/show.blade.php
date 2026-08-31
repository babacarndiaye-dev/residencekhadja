@extends('admin.layout')
@section('title', 'Chambre '.$room->number)

@php $cat = $room->category; @endphp

@section('content')
<div class="flex flex-col gap-5">
    <a href="{{ route('admin.rooms.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Toutes les chambres</a>

    {{-- En-tête --}}
    <div class="flex flex-wrap items-center gap-3">
        <h2 class="font-display text-2xl font-semibold text-nuit-900">Chambre {{ $room->number }}</h2>
        <x-admin.badge :status="$room->status" />
        @if ($segment)
            <span class="inline-flex items-center gap-1 rounded-full bg-nuit-900 px-3 py-1 text-xs font-semibold text-white">
                <x-icon :name="$segment['icon']" size="14" /> {{ $segment['label'] }}
            </span>
        @endif
        <span class="text-sm text-nuit-500">{{ $cat?->name ?? '—' }}@if ($room->floor) · étage {{ $room->floor }}@endif</span>
    </div>

    <div class="grid gap-5 lg:grid-cols-[1.6fr_1fr]">

        {{-- ========================= COLONNE PRINCIPALE ========================= --}}
        <div class="flex flex-col gap-5">

            <section class="rounded-2xl bg-white p-5 shadow-card">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div><p class="text-xs text-nuit-400">Type</p><p class="font-medium text-nuit-900">{{ $cat?->name ?? '—' }}</p></div>
                    <div><p class="text-xs text-nuit-400">Lit</p><p class="font-medium text-nuit-900">{{ $cat?->bed ?: '—' }}</p></div>
                    <div><p class="text-xs text-nuit-400">Vue</p><p class="font-medium text-nuit-900">{{ $cat?->view ?: '—' }}</p></div>
                    <div><p class="text-xs text-nuit-400">Capacité</p><p class="font-medium text-nuit-900">{{ $cat?->capacity ?? '—' }} pers.</p></div>
                    @if ($canFolio)
                        <div><p class="text-xs text-nuit-400">Tarif / nuit</p><p class="font-medium text-nuit-900">{{ $cat ? money($cat->price) : '—' }}</p></div>
                    @endif
                    <div><p class="text-xs text-nuit-400">Étage / bâtiment</p><p class="font-medium text-nuit-900">{{ $room->floor ?: '—' }}{{ $room->building ? ' · '.$room->building : '' }}</p></div>
                </div>

                @if ($canOperate || auth()->user()->hasRole('housekeeping'))
                    <form method="POST" action="{{ route('admin.rooms.status', $room) }}" class="mt-4 flex flex-wrap items-center gap-2 border-t border-sable-100 pt-3">
                        @csrf
                        <label class="text-xs font-semibold text-nuit-500">Statut de la chambre</label>
                        <select name="status" class="rounded-lg border border-nuit-200 px-2 py-1.5 text-xs">
                            @foreach ($statuses as $k => $l)<option value="{{ $k }}" @selected($room->status === $k)>{{ $l }}</option>@endforeach
                        </select>
                        <button class="rounded-lg bg-nuit-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-nuit-800">Mettre à jour</button>
                    </form>
                @endif
            </section>

            {{-- Client + séjour : le tableau s'adapte à l'occupant --}}
            @if ($stay)
                <div class="grid gap-5 sm:grid-cols-2">
                    <section class="rounded-2xl bg-white p-5 shadow-card">
                        <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Client actuel</h3>
                        <p class="text-base font-semibold text-nuit-900">{{ $stay->guest->civility }} {{ $stay->guest->fullName() }}</p>
                        @if ($stay->guest->loyaltyAccount?->tier)
                            <p class="text-xs font-semibold text-terracotta-600">Fidélité · {{ $stay->guest->loyaltyAccount->tier->name }}</p>
                        @endif
                        @if ($canOperate)
                            <dl class="mt-3 space-y-1 text-sm text-nuit-600">
                                @if ($stay->guest->email)<div class="flex items-center gap-2"><x-icon name="mail" size="15" /> {{ $stay->guest->email }}</div>@endif
                                @if ($stay->guest->phone)<div class="flex items-center gap-2"><x-icon name="call" size="15" /> {{ $stay->guest->phone }}</div>@endif
                                @if ($stay->guest->country)<div class="flex items-center gap-2"><x-icon name="public" size="15" /> {{ $stay->guest->country }}</div>@endif
                                @if ($stay->guest->company)<div class="flex items-center gap-2"><x-icon name="business_center" size="15" /> {{ $stay->guest->company }}</div>@endif
                            </dl>
                            <a href="{{ route('admin.guests.show', $stay->guest) }}" class="mt-3 inline-block rounded-full border border-nuit-200 px-3 py-1.5 text-xs font-semibold text-nuit-700 hover:border-terracotta-400 hover:text-terracotta-600">Profil complet →</a>
                        @endif
                    </section>

                    <section class="rounded-2xl bg-white p-5 shadow-card">
                        <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Détails du séjour</h3>
                        <dl class="space-y-1.5 text-sm">
                            <div class="flex justify-between"><dt class="text-nuit-400">Réservation</dt><dd class="font-mono text-xs">{{ $stay->reference }}</dd></div>
                            <div class="flex justify-between"><dt class="text-nuit-400">Arrivée</dt><dd>{{ $stay->check_in->format('d/m/Y') }} · {{ optional($stay->checked_in_at)->format('H:i') ?? $checkinTime }}</dd></div>
                            <div class="flex justify-between"><dt class="text-nuit-400">Départ</dt><dd>{{ $stay->check_out->format('d/m/Y') }} · {{ $checkoutTime }}</dd></div>
                            <div class="flex justify-between"><dt class="text-nuit-400">Nuits</dt><dd>{{ $stay->nights() }}</dd></div>
                            <div class="flex justify-between"><dt class="text-nuit-400">Personnes</dt><dd>{{ $stay->adults }} ad.@if ($stay->children) · {{ $stay->children }} enf.@endif</dd></div>
                            <div class="flex justify-between"><dt class="text-nuit-400">Formule</dt><dd>{{ $stay->ratePlan?->name ?? '—' }}</dd></div>
                            <div class="flex justify-between"><dt class="text-nuit-400">Canal</dt><dd class="capitalize">{{ $stay->channel }}</dd></div>
                            <div class="flex justify-between"><dt class="text-nuit-400">Statut</dt><dd><x-admin.badge :status="$stay->status" :label="$stay->statusLabel()" /></dd></div>
                        </dl>
                    </section>
                </div>
            @else
                <section class="rounded-2xl bg-white p-6 text-center shadow-card">
                    <p class="font-display text-lg font-semibold text-nuit-900">Chambre inoccupée</p>
                    <p class="mt-1 text-sm text-nuit-500">Aucun séjour en cours. Le tableau de bord s’active automatiquement au check-in.</p>
                </section>
            @endif

            {{-- État opérationnel --}}
            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">État de la chambre</h3>
                <div class="grid gap-3 text-sm sm:grid-cols-2">
                    <div class="flex items-center justify-between"><span class="text-nuit-500">Propreté</span><span class="font-medium">{{ $room->statusLabel() }}</span></div>
                    <div class="flex items-center justify-between"><span class="text-nuit-500">Dernière tâche ménage</span><span class="font-medium">{{ $housekeeping?->statusLabel() ?? '—' }}</span></div>
                    <div class="flex items-center justify-between"><span class="text-nuit-500">Maintenance</span>
                        <span class="font-medium {{ $maintenance->isNotEmpty() ? 'text-terracotta-700' : 'text-emerald-700' }}">
                            {{ $maintenance->isNotEmpty() ? $maintenance->count().' ticket(s) ouvert(s)' : 'Aucun problème' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between"><span class="text-nuit-500">Active à la vente</span><span class="font-medium">{{ $room->is_active ? 'Oui' : 'Non' }}</span></div>
                </div>
                @if ($maintenance->isNotEmpty())
                    <ul class="mt-3 space-y-1 border-t border-sable-100 pt-3 text-xs text-nuit-500">
                        @foreach ($maintenance as $t)
                            <li><span class="font-mono">{{ $t->reference }}</span> · {{ $t->title }} — {{ $t->statusLabel() }} ({{ $t->priorityLabel() }})</li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Demandes en cours --}}
            @if ($stay)
                <section class="rounded-2xl bg-white p-5 shadow-card">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="font-display text-lg font-semibold text-nuit-900">Demandes en cours</h3>
                        <a href="{{ route('admin.guest_requests.index') }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Toutes les demandes →</a>
                    </div>
                    @forelse ($openRequests as $r)
                        <div class="flex items-center justify-between border-t border-sable-100 py-2 text-sm first:border-0">
                            <span>{{ $r->typeLabel() }}@if ($r->note)<span class="block text-xs text-nuit-400">{{ $r->note }}</span>@endif</span>
                            <span class="flex items-center gap-2 whitespace-nowrap text-xs text-nuit-400">
                                {{ $r->created_at->format('H:i') }}
                                <x-admin.badge :status="$r->status" :label="$r->statusLabel()" />
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-nuit-400">Aucune demande active.</p>
                    @endforelse
                </section>
            @endif

            {{-- Historique des séjours --}}
            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Historique des séjours</h3>
                @if ($history->isEmpty())
                    <p class="text-sm text-nuit-400">Aucun séjour passé enregistré pour cette chambre.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-left text-xs uppercase tracking-wider text-nuit-400">
                                <tr>
                                    <th class="py-2">Client</th><th class="py-2">Arrivée</th><th class="py-2">Départ</th><th class="py-2">Nuits</th>
                                    @if ($canFolio)<th class="py-2 text-right">Montant</th>@endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($history as $h)
                                    <tr class="border-t border-sable-100">
                                        <td class="py-2">{{ $h->guest?->fullName() ?? '—' }}</td>
                                        <td class="py-2 text-nuit-500">{{ $h->check_in->format('d/m/y') }}</td>
                                        <td class="py-2 text-nuit-500">{{ $h->check_out->format('d/m/y') }}</td>
                                        <td class="py-2">{{ $h->nights() }}</td>
                                        @if ($canFolio)<td class="py-2 text-right">{{ money($h->grandTotal()) }}</td>@endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        {{-- ============================== ASIDE ============================== --}}
        <div class="flex flex-col gap-5">

            @if ($stay && $canFolio)
                <section class="rounded-2xl bg-white p-5 shadow-card">
                    <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Folio du séjour</h3>
                    <dl class="space-y-1.5 text-sm">
                        <div class="flex justify-between"><dt class="text-nuit-500">Hébergement</dt><dd>{{ money($stay->total) }}</dd></div>
                        @foreach ($stay->charges as $c)
                            <div class="flex justify-between"><dt class="text-nuit-500">{{ $c->label }}</dt><dd>{{ money($c->amount) }}</dd></div>
                        @endforeach
                        <div class="mt-2 flex justify-between border-t border-sable-200 pt-2 font-semibold text-nuit-900"><dt>Total</dt><dd>{{ money($stay->grandTotal()) }}</dd></div>
                        <div class="flex justify-between text-emerald-700"><dt>Réglé</dt><dd>{{ money($stay->paidAmount()) }}</dd></div>
                        <div class="flex justify-between text-lg font-semibold {{ $stay->balance() > 0 ? 'text-terracotta-700' : 'text-emerald-700' }}">
                            <dt>Solde à payer</dt><dd>{{ money($stay->balance()) }}</dd>
                        </div>
                    </dl>
                    <a href="{{ route('admin.reservations.show', $stay) }}" class="mt-4 block rounded-xl bg-nuit-900 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-nuit-800">
                        Ouvrir la réservation
                    </a>
                </section>
            @endif

            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Services de l’hôtel</h3>
                <div class="grid grid-cols-3 gap-2">
                    @foreach ($services as $svc)
                        <div class="flex flex-col items-center gap-1 rounded-xl border border-sable-200 p-3 text-center">
                            <x-icon :name="$svc['icon']" size="22" class="text-terracotta-500" />
                            <span class="text-[11px] leading-tight text-nuit-600">{{ $svc['label'] }}</span>
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 text-xs text-nuit-400">Catalogue présenté au client dans son espace « {{ config('guestapp.name') }} ».</p>
            </section>
        </div>
    </div>
</div>
@endsection
