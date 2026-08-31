@extends('admin.layout')
@section('title', $event->name)

@section('content')
<div class="flex flex-col gap-6">
    <a href="{{ route('admin.events.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Calendrier</a>

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="font-display text-2xl font-semibold text-nuit-900">{{ $event->name }}</h2>
            <p class="text-sm text-nuit-500">{{ $event->reference }} · {{ $event->typeLabel() }} · {{ $event->client_name }} · {{ $event->pax }} pax
                <span class="ml-2 rounded-full bg-sable-200 px-2 py-0.5 text-xs font-semibold">{{ $event->statusLabel() }}</span>
                @if ($event->isOptionExpired())<span class="ml-1 rounded-full bg-terracotta-100 px-2 py-0.5 text-xs font-semibold text-terracotta-800">option expirée</span>@endif
            </p>
            <p class="text-sm text-nuit-600">{{ $event->starts_at->format('d/m/Y H\hi') }} → {{ $event->ends_at->format('d/m/Y H\hi') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.events.contract', $event) }}" target="_blank" class="rounded-full border border-nuit-200 px-4 py-1.5 text-xs font-semibold text-nuit-700 hover:border-terracotta-400">Contrat ↗</a>
            @if ($event->status === 'option')
                <form method="POST" action="{{ route('admin.events.confirm', $event) }}">@csrf<button class="rounded-full bg-emerald-600 px-4 py-1.5 text-xs font-semibold text-white">Confirmer</button></form>
            @endif
            @if ($event->status === 'confirme')
                <form method="POST" action="{{ route('admin.events.complete', $event) }}">@csrf<button class="rounded-full bg-nuit-900 px-4 py-1.5 text-xs font-semibold text-white">Marquer réalisé</button></form>
            @endif
            @if (in_array($event->status, ['option', 'confirme']))
                <form method="POST" action="{{ route('admin.events.cancel', $event) }}" onsubmit="return confirm('Annuler cet événement ?')">@csrf<button class="rounded-full border border-nuit-200 px-4 py-1.5 text-xs font-semibold text-nuit-500">Annuler</button></form>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.3fr_1fr]">
        <div class="flex flex-col gap-6">

            {{-- Salles --}}
            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Salles réservées</h3>
                @forelse ($event->spaceBookings as $b)
                    <div class="flex items-center justify-between border-b border-sable-100 py-2 text-sm">
                        <div>
                            <p class="font-medium text-nuit-900">{{ $b->space->name }}</p>
                            <p class="text-xs text-nuit-500">{{ $b->starts_at->format('d/m H\hi') }} → {{ $b->ends_at->format('d/m H\hi') }}@if($b->layout) · {{ $b->layout }}@endif</p>
                        </div>
                        <form method="POST" action="{{ route('admin.events.space.remove', [$event, $b]) }}">@csrf @method('DELETE')<button class="text-xs text-terracotta-600">Retirer</button></form>
                    </div>
                @empty
                    <p class="text-sm text-nuit-400">Aucune salle affectée.</p>
                @endforelse

                <form method="POST" action="{{ route('admin.events.space.add', $event) }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                    @csrf
                    <select name="event_space_id" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                        <option value="">Salle…</option>
                        @foreach ($spaces as $s)<option value="{{ $s->id }}">{{ $s->name }} ({{ $s->maxCapacity() }} pax max)</option>@endforeach
                    </select>
                    <select name="layout" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                        <option value="">Disposition…</option>
                        @foreach ($layouts as $l)<option value="{{ $l }}">{{ $l }}</option>@endforeach
                    </select>
                    <label class="text-xs text-nuit-500">Début<input type="datetime-local" name="starts_at" value="{{ $event->starts_at->format('Y-m-d\TH:i') }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
                    <label class="text-xs text-nuit-500">Fin<input type="datetime-local" name="ends_at" value="{{ $event->ends_at->format('Y-m-d\TH:i') }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
                    <div class="sm:col-span-2"><button class="rounded-full bg-nuit-900 px-4 py-1.5 text-xs font-semibold text-white">Affecter la salle</button></div>
                </form>
            </section>

            {{-- Feuille de fonction --}}
            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Feuille de fonction (BEO)</h3>
                <ol class="flex flex-col gap-2 text-sm">
                    @forelse ($event->agenda as $a)
                        <li class="flex items-start justify-between border-b border-sable-100 pb-2">
                            <div>
                                <p class="font-medium text-nuit-900">{{ $a->scheduled_at->format('H\hi') }}@if($a->duration_min) ({{ $a->duration_min }} min)@endif — {{ $a->title }}</p>
                                <p class="text-xs text-nuit-500">{{ $a->categoryLabel() }}@if($a->area) · {{ $a->area }}@endif @if($a->responsible)· {{ $a->responsible }}@endif</p>
                                @if ($a->detail)<p class="text-xs text-nuit-600">{{ $a->detail }}</p>@endif
                            </div>
                            <form method="POST" action="{{ route('admin.events.agenda.destroy', [$event, $a]) }}">@csrf @method('DELETE')<button class="text-xs text-terracotta-600">✕</button></form>
                        </li>
                    @empty
                        <li class="text-nuit-400">Aucune ligne.</li>
                    @endforelse
                </ol>

                <form method="POST" action="{{ route('admin.events.agenda.store', $event) }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                    @csrf
                    <label class="text-xs text-nuit-500">Heure<input type="datetime-local" name="scheduled_at" value="{{ $event->starts_at->format('Y-m-d\TH:i') }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
                    <label class="text-xs text-nuit-500">Durée (min)<input type="number" name="duration_min" min="5" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
                    <input name="title" placeholder="Intitulé" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    <select name="category" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                        @foreach ($agendaCategories as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                    </select>
                    <input name="area" placeholder="Lieu / salle" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    <input name="responsible" placeholder="Responsable" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    <input name="detail" placeholder="Détail" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm sm:col-span-2">
                    <div class="sm:col-span-2"><button class="rounded-full bg-nuit-900 px-4 py-1.5 text-xs font-semibold text-white">Ajouter la ligne</button></div>
                </form>
            </section>
        </div>

        <div class="flex flex-col gap-6">
            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Finance</h3>
                @if ($event->quote)
                    <dl class="flex flex-col gap-1.5 text-sm">
                        <div class="flex justify-between"><dt class="text-nuit-500">Devis</dt><dd><a href="{{ route('admin.events.quotes.show', $event->quote) }}" class="font-mono text-xs text-terracotta-600 hover:underline">{{ $event->quote->reference }}</a></dd></div>
                        <div class="flex justify-between"><dt class="text-nuit-500">Contrat</dt><dd class="font-semibold text-nuit-900">{{ money($event->contractValue()) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-nuit-500">Acompte</dt><dd>{{ money($event->quote->deposit_amount) }} <span class="text-xs {{ $event->deposit_paid ? 'text-emerald-700' : 'text-nuit-400' }}">{{ $event->deposit_paid ? 'encaissé' : 'en attente' }}</span></dd></div>
                        <div class="flex justify-between border-t border-sable-200 pt-1"><dt class="text-nuit-500">Solde dû</dt><dd class="font-semibold">{{ money($event->balanceDue()) }}</dd></div>
                    </dl>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @unless ($event->deposit_paid)
                            <form method="POST" action="{{ route('admin.events.deposit', $event) }}">@csrf<button class="rounded-full bg-terracotta-500 px-4 py-1.5 text-xs font-semibold text-white">Encaisser l'acompte</button></form>
                            <form method="POST" action="{{ route('admin.payments.link.event', $event) }}">@csrf<input type="hidden" name="purpose" value="deposit"><button class="rounded-full border border-nuit-200 px-4 py-1.5 text-xs font-semibold text-nuit-700 hover:border-terracotta-400">Lien de paiement (acompte)</button></form>
                        @endunless
                        @if ($event->deposit_paid && ! $event->settled && $event->balanceDue() > 0)
                            <form method="POST" action="{{ route('admin.events.settlement', $event) }}">@csrf<button class="rounded-full bg-nuit-900 px-4 py-1.5 text-xs font-semibold text-white">Encaisser le solde</button></form>
                            <form method="POST" action="{{ route('admin.payments.link.event', $event) }}">@csrf<input type="hidden" name="purpose" value="balance"><button class="rounded-full border border-nuit-200 px-4 py-1.5 text-xs font-semibold text-nuit-700 hover:border-terracotta-400">Lien de paiement (solde)</button></form>
                        @endif
                        @if ($event->settled)<span class="text-xs font-semibold text-emerald-700">Soldé ✓</span>@endif
                    </div>
                @else
                    <p class="text-sm text-nuit-400">Aucun devis rattaché.</p>
                @endif
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-card text-sm">
                <h3 class="mb-2 font-display text-lg font-semibold text-nuit-900">Logistique</h3>
                <p class="text-nuit-600">Chambres à bloquer : <b>{{ $event->rooms_to_block }}</b></p>
                @if ($event->option_expires_on && $event->status === 'option')
                    <p class="text-nuit-600">Option valable jusqu'au <b>{{ $event->option_expires_on->format('d/m/Y') }}</b></p>
                @endif
                @if ($event->notes)<p class="mt-2 rounded-lg bg-sable-100 p-3 text-xs text-nuit-600">{{ $event->notes }}</p>@endif
            </section>
        </div>
    </div>
</div>
@endsection
