@extends('admin.layout')
@section('title', 'Réservation '.$reservation->groupKey())

@php
    $canAct = auth()->user()->hasRole('reception');
    $members = $members ?? collect([$reservation]);
    $grouped = $grouped ?? $reservation->isGrouped();

    $checkable = $members->filter->canCheckIn()->values();
    $inHouse = $members->filter->canCheckOut()->values();
    $hasPending = $members->contains(fn ($m) => $m->status === 'pending');
    $cancellable = $members->contains(fn ($m) => in_array($m->status, ['pending', 'confirmed'], true));
    $anyCheckedOut = $members->contains(fn ($m) => $m->status === 'checked_out');

    $roomTotal = (int) $members->sum('room_total');
    $extrasTotal = (int) $members->sum('extras_total');
    $discountTotal = (int) $members->sum('discount_amount');
    $taxTotal = (int) $members->sum('tax_amount');
    $touristTotal = (int) $members->sum('tourist_tax');
    $staysTotal = (int) $members->sum('total');
    $chargesTotal = (int) $members->sum(fn ($m) => $m->chargesTotal());
    $grandTotal = $staysTotal + $chargesTotal;
    $paidTotal = (int) $members->sum(fn ($m) => $m->paidAmount());
    $balance = $grandTotal - $paidTotal;
@endphp

@section('content')
<div class="flex flex-col gap-6">

    <a href="{{ route('admin.reservations.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Toutes les réservations</a>

    <div class="flex flex-wrap items-center gap-3">
        <h2 class="font-display text-2xl font-semibold text-nuit-900">{{ $reservation->groupKey() }}</h2>
        @if ($grouped)
            <span class="rounded-full bg-nuit-900 px-2.5 py-1 text-xs font-semibold text-white">{{ $members->count() }} chambres</span>
        @endif
        <x-admin.badge :status="$reservation->status" />
        <span class="text-sm text-nuit-500">créée le {{ $reservation->created_at->format('d/m/Y H:i') }} · canal {{ $reservation->channel }}</span>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.5fr_1fr] lg:items-start">

        {{-- Détails --}}
        <div class="flex flex-col gap-6">
            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Client</h3>
                <dl class="grid gap-2 text-sm sm:grid-cols-2">
                    <div><dt class="text-nuit-400">Nom</dt><dd class="font-medium text-nuit-900"><a href="{{ route('admin.guests.show', $reservation->guest) }}" class="hover:text-terracotta-600">{{ $reservation->guest->civility }} {{ $reservation->guest->fullName() }}</a></dd></div>
                    <div><dt class="text-nuit-400">E-mail</dt><dd class="text-nuit-800">{{ $reservation->guest->email }}</dd></div>
                    <div><dt class="text-nuit-400">Téléphone</dt><dd class="text-nuit-800">{{ $reservation->guest->phone }}</dd></div>
                    <div><dt class="text-nuit-400">Pays</dt><dd class="text-nuit-800">{{ $reservation->guest->country }}</dd></div>
                    @if ($reservation->guest->company)<div><dt class="text-nuit-400">Société</dt><dd class="text-nuit-800">{{ $reservation->guest->company }}</dd></div>@endif
                </dl>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Séjour</h3>
                <dl class="grid gap-2 text-sm sm:grid-cols-2">
                    <div><dt class="text-nuit-400">Arrivée</dt><dd class="font-medium text-nuit-900">{{ pretty_date($reservation->check_in) }}{{ $reservation->arrival_time ? ' · '.$reservation->arrival_time : '' }}</dd></div>
                    <div><dt class="text-nuit-400">Départ</dt><dd class="font-medium text-nuit-900">{{ pretty_date($reservation->check_out) }}</dd></div>
                    <div><dt class="text-nuit-400">Durée</dt><dd class="text-nuit-800">{{ $reservation->nights() }} nuit(s)</dd></div>
                    <div><dt class="text-nuit-400">Occupants</dt><dd class="text-nuit-800">{{ $reservation->adults }} adulte(s), {{ $reservation->children }} enfant(s)</dd></div>
                    <div><dt class="text-nuit-400">Tarif</dt><dd class="text-nuit-800">{{ optional($reservation->ratePlan)->name ?? '—' }}</dd></div>
                    <div><dt class="text-nuit-400">Code promo</dt><dd class="text-nuit-800">{{ $members->first(fn ($m) => filled($m->promo_code))?->promo_code ?? '—' }}</dd></div>
                </dl>

                {{-- Chambres du séjour --}}
                <div class="mt-4 overflow-x-auto rounded-xl border border-sable-200">
                    <table class="w-full text-sm">
                        <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                            <tr>
                                <th class="px-3 py-2">Réf.</th>
                                <th class="px-3 py-2">Catégorie</th>
                                <th class="px-3 py-2">Chambre</th>
                                <th class="px-3 py-2">Statut</th>
                                <th class="px-3 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($members as $m)
                                <tr class="border-t border-sable-200">
                                    <td class="px-3 py-2 font-mono text-xs">{{ $m->reference }}</td>
                                    <td class="px-3 py-2">{{ $m->roomCategory->name }}</td>
                                    <td class="px-3 py-2">{{ $m->room->number ?? '—' }}</td>
                                    <td class="px-3 py-2"><x-admin.badge :status="$m->status" /></td>
                                    <td class="px-3 py-2 text-right">{{ money($m->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($reservation->special_requests)
                    <p class="mt-3 text-xs text-nuit-400">Demandes spéciales</p>
                    <div class="mt-1 flex flex-wrap gap-2">
                        @foreach ($reservation->special_requests as $sr)<span class="rounded-full bg-sable-100 px-2.5 py-1 text-xs text-nuit-700">{{ $sr }}</span>@endforeach
                    </div>
                @endif
                @if ($reservation->notes)
                    <p class="mt-3 whitespace-pre-line rounded-lg bg-sable-100 p-3 text-xs text-nuit-600">{{ $reservation->notes }}</p>
                @endif
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Montants {{ $grouped ? '— séjour consolidé' : '' }}</h3>
                <dl class="flex flex-col gap-1.5 text-sm">
                    <div class="flex justify-between"><dt class="text-nuit-500">Hébergement{{ $grouped ? ' ('.$members->count().' ch.)' : '' }}</dt><dd>{{ money($roomTotal) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-nuit-500">Services</dt><dd>{{ money($extrasTotal) }}</dd></div>
                    @if ($discountTotal)<div class="flex justify-between text-terracotta-700"><dt>Remise</dt><dd>−{{ money($discountTotal) }}</dd></div>@endif
                    <div class="flex justify-between"><dt class="text-nuit-500">Taxes & services</dt><dd>{{ money($taxTotal) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-nuit-500">Taxe de séjour</dt><dd>{{ money($touristTotal) }}</dd></div>
                    <div class="mt-1 flex justify-between border-t border-sable-200 pt-2"><dt class="text-nuit-500">Total séjour</dt><dd>{{ money($staysTotal) }}</dd></div>

                    @if ($chargesTotal)
                        <div class="flex justify-between"><dt class="text-nuit-500">Consommations</dt><dd>{{ money($chargesTotal) }}</dd></div>
                    @endif

                    <div class="mt-1 flex justify-between border-t border-sable-200 pt-2 font-semibold text-nuit-900"><dt>Total à payer</dt><dd>{{ money($grandTotal) }}</dd></div>
                    <div class="flex justify-between text-nuit-500"><dt>Réglé</dt><dd>{{ money($paidTotal) }}</dd></div>
                    <div class="flex justify-between font-semibold {{ $balance > 0 ? 'text-terracotta-700' : 'text-emerald-700' }}"><dt>Solde</dt><dd>{{ money($balance) }}</dd></div>
                </dl>
            </section>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col gap-6 lg:sticky lg:top-6">

            @if ($canAct)
                <section class="rounded-2xl bg-white p-5 shadow-card">
                    <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Actions</h3>

                    @if ($hasPending)
                        <form method="POST" action="{{ route('admin.reservations.confirm', $reservation) }}" class="mb-2">
                            @csrf
                            <button class="w-full rounded-full bg-terracotta-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-terracotta-600">Confirmer {{ $grouped ? 'le séjour ('.$members->where('status', 'pending')->count().' ch.)' : 'la réservation' }}</button>
                        </form>
                    @endif

                    @if ($checkable->isNotEmpty())
                        <form method="POST" action="{{ route('admin.reservations.check_in', $reservation) }}" class="mb-2 flex flex-col gap-2">
                            @csrf
                            <label class="text-xs font-semibold text-nuit-500">Attribuer {{ $checkable->count() > 1 ? 'les chambres' : 'une chambre' }}</label>
                            @php $noRoom = false; @endphp
                            @foreach ($checkable as $m)
                                @php $pool = ($assignableRooms[$m->room_category_id] ?? collect()); $noRoom = $noRoom || $pool->isEmpty(); @endphp
                                <div class="flex items-center gap-2">
                                    @if ($grouped)<span class="w-28 shrink-0 truncate text-xs text-nuit-500">{{ $m->reference }} · {{ $m->roomCategory->name }}</span>@endif
                                    <select name="assign[{{ $m->id }}]" required class="flex-1 rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                                        <option value="">{{ $m->roomCategory->name }} — choisir…</option>
                                        @foreach ($pool as $room)
                                            <option value="{{ $room->id }}">Ch. {{ $room->number }} — étage {{ $room->floor }} ({{ $room->statusLabel() }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                            <button class="rounded-full bg-nuit-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-nuit-800" @disabled($noRoom)>Check-in</button>
                            @if ($noRoom)<p class="text-xs text-terracotta-700">Pas assez de chambres disponibles dans une des catégories.</p>@endif
                            @if ($grouped)<p class="text-xs text-nuit-400">Choisissez une chambre distincte par ligne.</p>@endif
                        </form>
                    @endif

                    @if ($inHouse->isNotEmpty())
                        <form method="POST" action="{{ route('admin.reservations.check_out', $reservation) }}" class="mb-2 flex flex-col gap-2">
                            @csrf
                            @if ($balance > 0)
                                <label class="flex items-center gap-2 text-xs text-nuit-600">
                                    <input type="checkbox" name="settle_balance" value="1" class="rounded border-nuit-300 text-terracotta-500">
                                    Encaisser le solde ({{ money($balance) }})
                                </label>
                                <select name="method" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                                    @foreach ($paymentMethods as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                                </select>
                            @endif
                            <button class="rounded-full bg-terracotta-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-terracotta-600">Check-out {{ $grouped ? '('.$inHouse->count().' ch.)' : '' }}</button>
                        </form>
                    @endif

                    @if ($anyCheckedOut)
                        <a href="{{ route('admin.reservations.invoice', $reservation) }}" class="block rounded-full bg-nuit-900 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-nuit-800">Voir la facture</a>
                    @endif

                    @if (in_array($reservation->status, ['confirmed', 'checked_in'], true))
                        <div class="mt-3 rounded-xl border border-sable-200 bg-sable-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wider text-nuit-500">Carte d’accueil — app invité</p>
                            <div class="mt-2 flex items-center gap-3">
                                <img src="{{ route('admin.reservations.app_qr', $reservation) }}" alt="QR app invité" width="88" height="88" class="h-22 w-22 shrink-0 rounded-lg border border-sable-200 bg-white">
                                <div class="min-w-0 text-xs">
                                    <p class="text-nuit-500">Le client scanne ce QR (ou reçoit le lien) pour accéder à son séjour, son folio, le room service et ses demandes — sans compte.</p>
                                    <p class="mt-1 truncate font-mono text-xs text-nuit-400">{{ \App\Services\GuestApp::magicUrl($reservation) }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($cancellable)
                        <div class="mt-3 flex gap-2 border-t border-sable-200 pt-3">
                            <form method="POST" action="{{ route('admin.reservations.no_show', $reservation) }}" class="flex-1">
                                @csrf
                                <button class="w-full rounded-full border border-nuit-200 px-3 py-2 text-xs font-semibold text-nuit-600 hover:border-terracotta-400 hover:text-terracotta-600">No-show</button>
                            </form>
                            <form method="POST" action="{{ route('admin.reservations.cancel', $reservation) }}" class="flex-1"
                                  onsubmit="return confirm('Annuler {{ $grouped ? 'tout le séjour' : 'cette réservation' }} ?')">
                                @csrf
                                <input type="hidden" name="reason" value="Annulé depuis le back-office">
                                <button class="w-full rounded-full border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Annuler</button>
                            </form>
                        </div>
                    @endif
                </section>
            @endif

            {{-- Paiements --}}
            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Paiements {{ $grouped ? '— folio du séjour' : '' }}</h3>
                @forelse ($reservation->payments as $p)
                    <div class="flex items-center justify-between border-t border-sable-200 py-2 text-sm">
                        <div>
                            <span class="font-medium text-nuit-900">{{ money($p->amount) }}</span>
                            <span class="block text-xs text-nuit-400">{{ \App\Models\Payment::TYPES[$p->type] ?? $p->type }} · {{ \App\Models\Payment::METHODS[$p->method] ?? $p->method }} · {{ $p->received_at->format('d/m H:i') }}</span>
                        </div>
                    </div>
                @empty
                    <p class="border-t border-sable-200 py-3 text-sm text-nuit-400">Aucun paiement enregistré.</p>
                @endforelse

                @if ($canAct && $reservation->status !== 'cancelled')
                    <form method="POST" action="{{ route('admin.reservations.payment', $reservation) }}" class="mt-3 flex flex-col gap-2 border-t border-sable-200 pt-3">
                        @csrf
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="amount" min="1" placeholder="Montant" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                            <select name="type" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                                @foreach (\App\Models\Payment::TYPES as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <select name="method" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                                @foreach ($paymentMethods as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                            </select>
                            <input type="text" name="reference" placeholder="Réf. (facultatif)" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                        </div>
                        <button class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white hover:bg-nuit-800">Enregistrer le paiement</button>
                    </form>

                    <div class="mt-3 flex flex-wrap gap-2 border-t border-sable-200 pt-3">
                        <form method="POST" action="{{ route('admin.payments.link.reservation', $reservation) }}">
                            @csrf<input type="hidden" name="purpose" value="deposit">
                            <button class="rounded-full border border-nuit-200 px-4 py-1.5 text-xs font-semibold text-nuit-700 hover:border-terracotta-400">Lien de paiement — acompte</button>
                        </form>
                        @if ($balance > 0)
                            <form method="POST" action="{{ route('admin.payments.link.reservation', $reservation) }}">
                                @csrf<input type="hidden" name="purpose" value="balance">
                                <button class="rounded-full border border-nuit-200 px-4 py-1.5 text-xs font-semibold text-nuit-700 hover:border-terracotta-400">Lien de paiement — solde</button>
                            </form>
                        @endif
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>
@endsection
