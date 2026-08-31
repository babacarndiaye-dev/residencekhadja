@extends('admin.layout')
@section('title', 'Commande '.$order->reference)

@section('content')
<div class="flex flex-col gap-6">
    <a href="{{ route('admin.orders.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Toutes les commandes</a>

    <div class="flex flex-wrap items-center gap-3">
        <h2 class="font-display text-2xl font-semibold text-nuit-900">{{ $order->reference }}</h2>
        <x-admin.badge :status="$order->status" />
        <x-admin.badge :status="$order->payment_status" :label="['unpaid'=>'À régler','paid'=>'Réglé','charged_to_room'=>'Imputé chambre'][$order->payment_status] ?? $order->payment_status" />
        <span class="text-sm text-nuit-500">{{ $order->created_at->format('d/m/Y H:i') }}</span>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr] lg:items-start">
        <div class="flex flex-col gap-6">
            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Articles</h3>
                <ul class="flex flex-col gap-3 text-sm">
                    @foreach ($order->items as $line)
                        <li class="flex justify-between gap-3 border-b border-sable-100 pb-2">
                            <div>
                                <span class="font-semibold text-nuit-900">{{ $line->quantity }}× {{ $line->name }}</span>
                                @if ($line->options)<span class="block text-xs text-nuit-500">{{ collect($line->options)->map(fn($o) => $o['name'].($o['price_delta'] ? ' ('.money($o['price_delta']).')' : ''))->join(', ') }}</span>@endif
                                @if ($line->note)<span class="block text-xs italic text-terracotta-700">{{ $line->note }}</span>@endif
                            </div>
                            <span>{{ money($line->line_total) }}</span>
                        </li>
                    @endforeach
                </ul>
                <dl class="mt-3 flex flex-col gap-1 text-sm">
                    <div class="flex justify-between"><dt class="text-nuit-500">Sous-total</dt><dd>{{ money($order->subtotal) }}</dd></div>
                    @if ($order->service_charge)<div class="flex justify-between"><dt class="text-nuit-500">Service</dt><dd>{{ money($order->service_charge) }}</dd></div>@endif
                    <div class="flex justify-between"><dt class="text-nuit-500">Taxes</dt><dd>{{ money($order->tax) }}</dd></div>
                    <div class="flex justify-between border-t border-sable-200 pt-1 font-semibold text-nuit-900"><dt>Total</dt><dd>{{ money($order->total) }}</dd></div>
                </dl>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Contexte</h3>
                <dl class="grid gap-2 text-sm sm:grid-cols-2">
                    <div><dt class="text-nuit-400">Lieu</dt><dd class="text-nuit-800">{{ $order->venue?->name }} — {{ $order->location?->label }}</dd></div>
                    <div><dt class="text-nuit-400">Type</dt><dd class="text-nuit-800">{{ $order->type === 'room_service' ? 'Room service' : 'Sur place' }}</dd></div>
                    <div><dt class="text-nuit-400">Client</dt><dd class="text-nuit-800">{{ $order->guest_name ?? '—' }}</dd></div>
                    @if ($order->reservation)
                        <div><dt class="text-nuit-400">Réservation</dt><dd><a href="{{ route('admin.reservations.show', $order->reservation) }}" class="text-terracotta-600 hover:underline">{{ $order->reservation->reference }} — Ch. {{ $order->room?->number }}</a></dd></div>
                    @endif
                </dl>
                @if ($order->serviceRequests->isNotEmpty())
                    <p class="mt-3 text-xs text-nuit-400">Demandes liées : {{ $order->serviceRequests->map->typeLabel()->join(', ') }}</p>
                @endif
            </section>
        </div>

        <div class="flex flex-col gap-6 lg:sticky lg:top-6">
            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Actions</h3>

                @unless ($order->isPaid())
                    <form method="POST" action="{{ route('admin.orders.pay', $order) }}" class="mb-3 flex gap-2">
                        @csrf
                        <select name="method" class="flex-1 rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                            <option value="especes">Espèces</option>
                            <option value="carte">Carte</option>
                            <option value="mobile">Mobile</option>
                        </select>
                        <button class="rounded-full bg-terracotta-500 px-4 py-2 text-sm font-semibold text-white hover:bg-terracotta-600">Encaisser</button>
                    </form>

                    <form method="POST" action="{{ route('admin.orders.charge', $order) }}" class="mb-3 flex flex-col gap-2">
                        @csrf
                        <label class="text-xs font-semibold text-nuit-500">Imputer sur une chambre</label>
                        <select name="reservation_id" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                            <option value="">Choisir un séjour en cours…</option>
                            @foreach (\App\Models\Reservation::with('guest','room')->where('status','checked_in')->get() as $r)
                                <option value="{{ $r->id }}">Ch. {{ $r->room?->number }} — {{ $r->guest->fullName() }} ({{ $r->reference }})</option>
                            @endforeach
                        </select>
                        <button class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white hover:bg-nuit-800">Imputer</button>
                    </form>
                @else
                    <p class="mb-3 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                        Réglé — {{ \App\Models\Payment::METHODS[$order->payment_method] ?? $order->payment_method }}
                    </p>
                @endunless

                @if ($order->source === 'pos')
                    <div class="mb-3 flex flex-col gap-2 rounded-lg border border-sable-200 p-3"
                         x-data="{ send: false }">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-semibold text-nuit-700">Facture A4</span>
                            @if ($order->invoice_number)<span class="font-mono text-xs text-nuit-500">{{ $order->invoice_number }}</span>@endif
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('admin.pos.invoice', $order) }}" target="_blank" class="rounded-full bg-nuit-900 px-4 py-1.5 text-xs font-semibold text-white hover:bg-nuit-800">Éditer / imprimer</a>
                            <button type="button" x-on:click="send = !send" class="rounded-full border border-sable-300 px-4 py-1.5 text-xs font-semibold text-nuit-600 hover:border-terracotta-400">Envoyer</button>
                        </div>
                        <form x-show="send" x-cloak method="POST" action="{{ route('admin.pos.invoice.send', $order) }}" class="flex flex-col gap-2">
                            @csrf
                            <input type="email" name="email" placeholder="E-mail (défaut : client de la chambre)" value="{{ optional($order->reservation?->guest)->email }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                            <input type="text" name="phone" placeholder="Téléphone (SMS, facultatif)" value="{{ optional($order->reservation?->guest)->phone }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                            <button class="rounded-full bg-terracotta-500 px-4 py-2 text-xs font-semibold text-white hover:bg-terracotta-600">Envoyer la facture</button>
                        </form>
                    </div>
                @endif

                @unless (in_array($order->status, ['completed', 'cancelled'], true))
                    <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" onsubmit="return confirm('Annuler cette commande ?')">
                        @csrf
                        <button class="w-full rounded-full border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Annuler la commande</button>
                    </form>
                @endunless
            </section>
        </div>
    </div>
</div>
@endsection
