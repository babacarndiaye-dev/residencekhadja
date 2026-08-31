@extends('admin.layout')
@section('title', $guest->fullName())

@php $acc = $guest->loyaltyAccount; @endphp

@section('content')
<div class="flex flex-col gap-6">
    <a href="{{ route('admin.guests.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Tous les clients</a>

    <div class="grid gap-6 lg:grid-cols-[1fr_1.4fr]">

        {{-- ============ Colonne gauche : identité + fidélité ============ --}}
        <div class="flex flex-col gap-6">

            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h2 class="font-display text-lg font-semibold text-nuit-900">{{ $guest->civility }} {{ $guest->fullName() }}</h2>
                <div class="mt-2 flex flex-wrap gap-x-6 gap-y-1 text-sm text-nuit-600">
                    <span>Valeur cumulée <b class="text-nuit-900">{{ money($guest->lifetimeValue()) }}</b></span>
                    <span>{{ $guest->honouredStays() }} séjour(s)</span>
                </div>

                <form method="POST" action="{{ route('admin.guests.update', $guest) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                    @csrf @method('PUT')
                    <label class="text-xs font-semibold text-nuit-500">Civilité
                        <input name="civility" value="{{ old('civility', $guest->civility) }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    </label>
                    <label class="text-xs font-semibold text-nuit-500">Naissance
                        <input type="date" name="birthdate" value="{{ old('birthdate', optional($guest->birthdate)->toDateString()) }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    </label>
                    <label class="text-xs font-semibold text-nuit-500">Prénom
                        <input name="first_name" value="{{ old('first_name', $guest->first_name) }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    </label>
                    <label class="text-xs font-semibold text-nuit-500">Nom
                        <input name="last_name" value="{{ old('last_name', $guest->last_name) }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    </label>
                    <label class="text-xs font-semibold text-nuit-500">E-mail
                        <input type="email" name="email" value="{{ old('email', $guest->email) }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    </label>
                    <label class="text-xs font-semibold text-nuit-500">Téléphone
                        <input name="phone" value="{{ old('phone', $guest->phone) }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    </label>
                    <label class="text-xs font-semibold text-nuit-500">Pays
                        <input name="country" value="{{ old('country', $guest->country) }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    </label>
                    <label class="text-xs font-semibold text-nuit-500">Société
                        <input name="company" value="{{ old('company', $guest->company) }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    </label>
                    <label class="text-xs font-semibold text-nuit-500 sm:col-span-2">Origine
                        <select name="acquisition_source" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                            <option value="">—</option>
                            @foreach ($sources as $k => $label)
                                <option value="{{ $k }}" @selected(old('acquisition_source', $guest->acquisition_source) === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <fieldset class="sm:col-span-2">
                        <legend class="text-xs font-semibold text-nuit-500">Étiquettes</legend>
                        <div class="mt-1 flex flex-wrap gap-2">
                            @foreach ($tagSuggestions as $tag)
                                <label class="flex items-center gap-1 rounded-full border border-nuit-200 px-2.5 py-1 text-xs">
                                    <input type="checkbox" name="tags[]" value="{{ $tag }}" @checked(in_array($tag, $guest->tags ?? []))>
                                    {{ $tag }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <label class="text-xs font-semibold text-nuit-500 sm:col-span-2">Notes
                        <textarea name="notes" rows="2" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">{{ old('notes', $guest->notes) }}</textarea>
                    </label>
                    <div class="sm:col-span-2">
                        <button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white hover:bg-nuit-800">Enregistrer la fiche</button>
                    </div>
                </form>

                {{-- Consentement --}}
                <div class="mt-4 flex items-center justify-between rounded-xl bg-sable-100 p-3">
                    <div class="text-sm">
                        <p class="font-semibold text-nuit-800">Consentement marketing</p>
                        <p class="text-xs text-nuit-500">
                            {{ $guest->marketing_opt_in ? 'Accordé' : 'Non accordé' }}
                            @if ($guest->consent_updated_at) · {{ $guest->consent_updated_at->format('d/m/Y') }} @endif
                        </p>
                    </div>
                    <form method="POST" action="{{ route('admin.guests.consent', $guest) }}">
                        @csrf
                        <input type="hidden" name="marketing_opt_in" value="{{ $guest->marketing_opt_in ? 0 : 1 }}">
                        <button class="rounded-full px-4 py-1.5 text-xs font-semibold {{ $guest->marketing_opt_in ? 'border border-nuit-200 text-nuit-700' : 'bg-emerald-600 text-white' }}">
                            {{ $guest->marketing_opt_in ? 'Retirer' : 'Enregistrer l’opt-in' }}
                        </button>
                    </form>
                </div>
            </section>

            {{-- Fidélité --}}
            <section class="rounded-2xl bg-white p-5 shadow-card">
                <div class="flex items-center justify-between">
                    <h3 class="font-display text-lg font-semibold text-nuit-900">Fidélité — {{ config('loyalty.program_name') }}</h3>
                    @if ($acc)
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold text-white" style="background: {{ $acc->tier->color ?? '#596d7a' }}">{{ $acc->tier->name ?? 'Membre' }}</span>
                    @endif
                </div>

                @if (! $acc)
                    <p class="mt-2 text-sm text-nuit-500">Client non inscrit au programme.</p>
                    <form method="POST" action="{{ route('admin.guests.enroll', $guest) }}" class="mt-3">
                        @csrf
                        <button class="rounded-full bg-terracotta-500 px-5 py-2 text-sm font-semibold text-white hover:bg-terracotta-600">Inscrire au programme</button>
                    </form>
                @else
                    <div class="mt-3 grid grid-cols-3 gap-3 text-center">
                        <div class="rounded-xl bg-sable-100 p-3">
                            <p class="text-xs text-nuit-400">Solde</p>
                            <p class="font-display text-2xl font-semibold text-nuit-900">{{ number_format($acc->points_balance, 0, ',', ' ') }}</p>
                            <p class="text-xs text-nuit-400">≈ {{ money($acc->balanceValue()) }}</p>
                        </div>
                        <div class="rounded-xl bg-sable-100 p-3">
                            <p class="text-xs text-nuit-400">Cumul à vie</p>
                            <p class="font-display text-2xl font-semibold text-nuit-900">{{ number_format($acc->lifetime_points, 0, ',', ' ') }}</p>
                        </div>
                        <div class="rounded-xl bg-sable-100 p-3">
                            <p class="text-xs text-nuit-400">Carte</p>
                            <p class="font-mono text-sm font-semibold text-nuit-900">{{ $acc->member_no }}</p>
                        </div>
                    </div>

                    @php $next = $acc->pointsToNextTier(); @endphp
                    @if ($next)
                        <p class="mt-2 text-xs text-nuit-500">Plus que <b>{{ number_format($next['missing'], 0, ',', ' ') }}</b> points pour le palier {{ $next['tier']->name }}.</p>
                    @endif
                    @if ($acc->tier && $acc->tier->perks)
                        <ul class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($acc->tier->perks as $perk)
                                <li class="rounded-full bg-nuit-50 px-2 py-0.5 text-xs text-nuit-600">{{ $perk }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <form method="POST" action="{{ route('admin.guests.points.redeem', $guest) }}" class="rounded-xl border border-sable-300 p-3">
                            @csrf
                            <p class="text-xs font-semibold text-nuit-600">Utiliser des points (remise)</p>
                            <input type="number" name="points" min="1" step="1" placeholder="points" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">
                            <input name="reason" placeholder="Motif (facultatif)" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">
                            <button class="mt-2 rounded-full bg-nuit-900 px-4 py-1.5 text-xs font-semibold text-white">Convertir</button>
                        </form>
                        <form method="POST" action="{{ route('admin.guests.points.adjust', $guest) }}" class="rounded-xl border border-sable-300 p-3">
                            @csrf
                            <p class="text-xs font-semibold text-nuit-600">Ajustement manuel (± points)</p>
                            <input type="number" name="points" step="1" placeholder="ex. 250 ou -100" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">
                            <input name="reason" placeholder="Motif" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">
                            <button class="mt-2 rounded-full border border-nuit-300 px-4 py-1.5 text-xs font-semibold text-nuit-700">Appliquer</button>
                        </form>
                    </div>

                    @if ($acc->transactions->isNotEmpty())
                        <table class="mt-4 w-full text-xs">
                            <thead class="text-left uppercase tracking-wider text-nuit-400">
                                <tr><th class="py-1">Date</th><th>Type</th><th>Motif</th><th class="text-right">Points</th><th class="text-right">Solde</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($acc->transactions as $tx)
                                    <tr class="border-t border-sable-100">
                                        <td class="py-1 text-nuit-500">{{ $tx->created_at->format('d/m/y') }}</td>
                                        <td>{{ $tx->typeLabel() }}</td>
                                        <td class="text-nuit-500">{{ $tx->reason }}</td>
                                        <td class="text-right font-semibold {{ $tx->points < 0 ? 'text-terracotta-600' : 'text-emerald-700' }}">{{ $tx->points > 0 ? '+' : '' }}{{ number_format($tx->points, 0, ',', ' ') }}</td>
                                        <td class="text-right text-nuit-500">{{ number_format($tx->balance_after, 0, ',', ' ') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                @endif
            </section>
        </div>

        {{-- ============ Colonne droite : séjours + timeline ============ --}}
        <div class="flex flex-col gap-6">

            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Historique des réservations</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wider text-nuit-400">
                            <tr><th class="py-2">Réf.</th><th>Séjour</th><th>Catégorie</th><th>Total</th><th>Statut</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($guest->reservations as $r)
                                <tr class="border-t border-sable-200">
                                    <td class="py-2"><a href="{{ route('admin.reservations.show', $r) }}" class="font-mono text-xs text-terracotta-600 hover:underline">{{ $r->reference }}</a></td>
                                    <td class="py-2 text-xs text-nuit-600">{{ $r->check_in->format('d/m/Y') }} → {{ $r->check_out->format('d/m/Y') }}</td>
                                    <td class="py-2">{{ $r->roomCategory->name }}</td>
                                    <td class="py-2">{{ money($r->total) }}</td>
                                    <td class="py-2"><x-admin.badge :status="$r->status" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-6 text-center text-nuit-400">Aucun séjour.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Historique relation client</h3>

                <form method="POST" action="{{ route('admin.guests.interaction', $guest) }}" class="grid gap-2 sm:grid-cols-[auto_1fr] sm:items-start">
                    @csrf
                    <select name="type" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                        @foreach ($interactionTypes as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                    </select>
                    <div class="flex flex-col gap-2">
                        <input name="subject" placeholder="Objet" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                        <textarea name="body" rows="2" placeholder="Détail (facultatif)" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm"></textarea>
                        <button class="self-start rounded-full bg-nuit-900 px-4 py-1.5 text-xs font-semibold text-white">Ajouter</button>
                    </div>
                </form>

                <ol class="mt-4 flex flex-col gap-3 border-l border-sable-300 pl-4">
                    @forelse ($guest->interactions as $it)
                        <li class="relative">
                            <span class="absolute -left-[1.35rem] top-1 h-2.5 w-2.5 rounded-full bg-terracotta-400"></span>
                            <p class="text-sm font-semibold text-nuit-800">{{ $it->subject }}
                                <span class="ml-1 rounded-full bg-sable-100 px-2 py-0.5 text-xs font-medium text-nuit-500">{{ $it->typeLabel() }}</span>
                            </p>
                            @if ($it->body)<p class="text-xs text-nuit-600">{{ $it->body }}</p>@endif
                            <p class="text-xs text-nuit-400">{{ $it->occurred_at->format('d/m/Y H:i') }}@if($it->user) · {{ $it->user->name }}@endif</p>
                        </li>
                    @empty
                        <li class="text-sm text-nuit-400">Aucune interaction enregistrée.</li>
                    @endforelse
                </ol>
            </section>
        </div>
    </div>
</div>
@endsection
