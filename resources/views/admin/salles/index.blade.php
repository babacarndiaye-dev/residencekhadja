@extends('admin.layout')
@section('title', 'Salles & séminaires')

@section('content')
@php
    $ctl = 'block w-full min-w-0 max-w-full rounded-lg border border-sable-300 bg-white px-3 py-2 text-sm text-nuit-900 focus:border-terracotta-500 focus:outline-none';
    $lab = 'mb-1 block text-xs font-semibold uppercase tracking-wider text-nuit-400';
@endphp
<div class="flex flex-col gap-4" x-data="{ quoteForm: {{ $errors->any() ? 'true' : 'false' }} }">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.salles.index', ['from' => $from->copy()->subWeek()->toDateString()]) }}" class="inline-flex items-center gap-1 rounded-md border border-sable-300 px-3 py-1.5 text-sm hover:border-terracotta-400"><x-icon name="chevron_left" size="16" /> semaine</a>
            <a href="{{ route('admin.salles.index', ['from' => now()->toDateString()]) }}" class="rounded-md border border-sable-300 px-3 py-1.5 text-sm hover:border-terracotta-400">Aujourd’hui</a>
            <a href="{{ route('admin.salles.index', ['from' => $from->copy()->addWeek()->toDateString()]) }}" class="inline-flex items-center gap-1 rounded-md border border-sable-300 px-3 py-1.5 text-sm hover:border-terracotta-400">semaine <x-icon name="chevron_right" size="16" /></a>
            <span class="ml-1 text-sm font-medium text-nuit-700">{{ $from->translatedFormat('d M') }} – {{ $from->copy()->addDays(6)->translatedFormat('d M Y') }}</span>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($canQuote)
                <button type="button" x-on:click="quoteForm = !quoteForm"
                        class="inline-flex items-center gap-1.5 rounded-md bg-terracotta-500 px-4 py-1.5 text-sm font-semibold text-white hover:bg-terracotta-600">
                    <x-icon name="request_quote" size="18" />
                    <span x-text="quoteForm ? 'Fermer' : 'Demande de devis'"></span>
                </button>
            @endif
            <a href="{{ route('admin.events.spaces') }}" class="inline-flex items-center gap-1.5 rounded-md border border-sable-300 px-3 py-1.5 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Configurer les salles <x-icon name="arrow_forward" size="16" /></a>
        </div>
    </div>

    @if ($canQuote)
        {{-- ─────────── Formulaire : demande de devis salle ─────────── --}}
        <form method="POST" action="{{ route('admin.salles.quote_request') }}" x-show="quoteForm" x-cloak x-transition
              class="rounded-lg border border-sable-200 bg-white p-4 sm:p-5">
            @csrf
            <p class="font-display text-base font-semibold text-nuit-900">Demande de devis — salle / séminaire</p>
            <p class="mt-0.5 text-xs text-nuit-400">Crée une affaire dans le pipeline commercial et un devis brouillon pré-rempli (location, restauration, technique).</p>

            <div class="mt-4 grid gap-4 lg:grid-cols-3">
                {{-- Client --}}
                <fieldset class="flex flex-col gap-3">
                    <legend class="text-xs font-semibold uppercase tracking-wider text-terracotta-600">Client</legend>
                    <div>
                        <label for="q_company" class="{{ $lab }}">Société</label>
                        <input type="text" id="q_company" name="company" value="{{ old('company') }}" maxlength="150" class="{{ $ctl }}">
                    </div>
                    <div>
                        <label for="q_contact" class="{{ $lab }}">Contact <span class="text-terracotta-600">*</span></label>
                        <input type="text" id="q_contact" name="contact_name" value="{{ old('contact_name') }}" required maxlength="120" class="{{ $ctl }}">
                    </div>
                    <div>
                        <label for="q_email" class="{{ $lab }}">E-mail</label>
                        <input type="email" id="q_email" name="contact_email" value="{{ old('contact_email') }}" maxlength="180" class="{{ $ctl }}">
                    </div>
                    <div>
                        <label for="q_phone" class="{{ $lab }}">Téléphone</label>
                        <input type="tel" id="q_phone" name="contact_phone" value="{{ old('contact_phone') }}" maxlength="40" class="{{ $ctl }}">
                    </div>
                </fieldset>

                {{-- Événement --}}
                <fieldset class="flex flex-col gap-3">
                    <legend class="text-xs font-semibold uppercase tracking-wider text-terracotta-600">Événement</legend>
                    <div>
                        <label for="q_type" class="{{ $lab }}">Type <span class="text-terracotta-600">*</span></label>
                        <select id="q_type" name="event_type" class="{{ $ctl }}">
                            @foreach ($eventTypes as $k => $label)
                                <option value="{{ $k }}" @selected(old('event_type') === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="q_date" class="{{ $lab }}">Date <span class="text-terracotta-600">*</span></label>
                            <input type="date" id="q_date" name="date" value="{{ old('date', now()->addWeek()->toDateString()) }}"
                                   min="{{ now()->toDateString() }}" required class="{{ $ctl }} appearance-none">
                        </div>
                        <div>
                            <label for="q_pax" class="{{ $lab }}">Participants <span class="text-terracotta-600">*</span></label>
                            <input type="number" id="q_pax" name="pax" value="{{ old('pax', 30) }}" min="1" max="5000" required class="{{ $ctl }}">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="q_start" class="{{ $lab }}">Début</label>
                            <input type="time" id="q_start" name="start_time" value="{{ old('start_time', '09:00') }}" class="{{ $ctl }} appearance-none">
                        </div>
                        <div>
                            <label for="q_end" class="{{ $lab }}">Fin</label>
                            <input type="time" id="q_end" name="end_time" value="{{ old('end_time', '17:00') }}" class="{{ $ctl }} appearance-none">
                        </div>
                    </div>
                    <div>
                        <span class="{{ $lab }}">Durée <span class="text-terracotta-600">*</span></span>
                        <div class="flex gap-2">
                            @foreach (['full_day' => 'Journée', 'half_day' => 'Demi-journée'] as $k => $label)
                                <label class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg border border-sable-300 px-3 py-2 text-sm has-[:checked]:border-terracotta-500 has-[:checked]:bg-terracotta-50 has-[:checked]:font-semibold has-[:checked]:text-terracotta-700">
                                    <input type="radio" name="duration" value="{{ $k }}" @checked(old('duration', 'full_day') === $k) class="accent-terracotta-500">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </fieldset>

                {{-- Salle --}}
                <fieldset class="flex flex-col gap-3">
                    <legend class="text-xs font-semibold uppercase tracking-wider text-terracotta-600">Salle</legend>
                    <div>
                        <label for="q_space" class="{{ $lab }}">Salle souhaitée</label>
                        <select id="q_space" name="event_space_id" class="{{ $ctl }}">
                            <option value="">Salle à définir</option>
                            @foreach ($spaces as $space)
                                <option value="{{ $space->id }}" @selected(old('event_space_id') == $space->id)>{{ $space->name }} · {{ $space->maxCapacity() }} pers. · {{ money($space->full_day_price) }}/j</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="q_layout" class="{{ $lab }}">Disposition</label>
                        <select id="q_layout" name="layout" class="{{ $ctl }}">
                            <option value="">—</option>
                            @foreach ($layouts as $layout)
                                <option value="{{ $layout }}" @selected(old('layout') === $layout)>{{ $layout }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="q_message" class="{{ $lab }}">Besoins particuliers</label>
                        <textarea id="q_message" name="message" rows="4" maxlength="2000" class="{{ $ctl }}">{{ old('message') }}</textarea>
                    </div>
                </fieldset>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                {{-- Restauration --}}
                <div>
                    <span class="{{ $lab }}">Restauration &amp; pauses <span class="font-normal normal-case tracking-normal text-nuit-400">(× participants)</span></span>
                    <div class="flex flex-col gap-1.5">
                        @foreach ($catering as $pkg)
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-sable-200 px-3 py-2 text-sm has-[:checked]:border-terracotta-400 has-[:checked]:bg-terracotta-50">
                                <input type="checkbox" name="catering[]" value="{{ $pkg['code'] }}" @checked(in_array($pkg['code'], old('catering', []), true)) class="accent-terracotta-500">
                                <span class="flex-1">{{ $pkg['name'] }}</span>
                                <span class="text-xs font-semibold text-nuit-500">{{ money($pkg['per_pax_price']) }}/pers.</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Technique --}}
                <div>
                    <span class="{{ $lab }}">Technique &amp; régie <span class="font-normal normal-case tracking-normal text-nuit-400">(forfait / jour)</span></span>
                    <div class="flex flex-col gap-1.5">
                        @foreach ($equipment as $eq)
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-sable-200 px-3 py-2 text-sm has-[:checked]:border-terracotta-400 has-[:checked]:bg-terracotta-50">
                                <input type="checkbox" name="equipment[]" value="{{ $eq['code'] }}" @checked(in_array($eq['code'], old('equipment', []), true)) class="accent-terracotta-500">
                                <span class="flex-1">{{ $eq['name'] }}</span>
                                <span class="text-xs font-semibold text-nuit-500">{{ money($eq['price']) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-end gap-2">
                <button type="button" x-on:click="quoteForm = false" class="rounded-md border border-sable-300 px-4 py-2 text-sm font-semibold text-nuit-600 hover:bg-sable-50">Annuler</button>
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-terracotta-500 px-5 py-2 text-sm font-semibold text-white hover:bg-terracotta-600">
                    <x-icon name="request_quote" size="18" /> Générer le devis brouillon
                </button>
            </div>
        </form>
    @endif

    @if ($canQuote && $quoteRequests->isNotEmpty())
        {{-- ─────────── Demandes de devis en cours ─────────── --}}
        <div class="overflow-x-auto rounded-lg border border-sable-200 bg-white">
            <table class="w-full min-w-[46rem] text-sm">
                <caption class="border-b border-sable-100 px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-nuit-400">Demandes de devis en cours</caption>
                <thead class="bg-sable-50 text-left text-xs uppercase tracking-wider text-nuit-400">
                    <tr>
                        <th class="px-3 py-2.5">Client</th>
                        <th class="px-3 py-2.5">Événement</th>
                        <th class="px-3 py-2.5">Date</th>
                        <th class="px-3 py-2.5 text-right">Estimé</th>
                        <th class="px-3 py-2.5">Devis</th>
                        <th class="px-3 py-2.5">Suivi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quoteRequests as $lead)
                        @php $quote = $lead->quotes->first(); @endphp
                        <tr class="border-t border-sable-100">
                            <td class="px-3 py-2.5">
                                <p class="font-medium text-nuit-900">{{ $lead->company ?: $lead->contact_name }}</p>
                                @if ($lead->company)<p class="text-xs text-nuit-400">{{ $lead->contact_name }}</p>@endif
                            </td>
                            <td class="px-3 py-2.5 text-nuit-600">{{ $lead->typeLabel() }} · {{ $lead->pax }} pers.</td>
                            <td class="px-3 py-2.5 text-nuit-600">{{ $lead->expected_start?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-nuit-700">{{ money($lead->estimated_value) }}</td>
                            <td class="px-3 py-2.5">
                                @if ($quote)
                                    <a href="{{ route('admin.events.quotes.show', $quote) }}" class="font-semibold text-terracotta-600 hover:underline">{{ $quote->reference }}</a>
                                    <span class="ml-1 rounded bg-sable-100 px-1.5 py-0.5 text-[11px] text-nuit-500">{{ $quote->statusLabel() }}</span>
                                @else
                                    <span class="text-nuit-300">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5">
                                <a href="{{ route('admin.events.leads.show', $lead) }}" class="text-nuit-500 hover:text-terracotta-600">{{ $lead->reference }} ↗</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Planning d'occupation --}}
    <div class="overflow-x-auto rounded-lg border border-sable-200 bg-white">
        <table class="w-full min-w-[52rem] text-sm">
            <thead class="bg-sable-50 text-left text-xs uppercase tracking-wider text-nuit-400">
                <tr>
                    <th class="w-40 px-3 py-3">Salle</th>
                    @foreach ($days as $d)
                        <th class="px-2 py-3 text-center {{ $d->isToday() ? 'text-terracotta-600' : '' }}">{{ $d->translatedFormat('D d/m') }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($spaces as $space)
                    <tr class="border-t border-sable-100 align-top">
                        <td class="px-3 py-3">
                            <p class="font-medium text-nuit-900">{{ $space->name }}</p>
                            <p class="text-xs text-nuit-400">{{ $space->area ? $space->area.' m² · ' : '' }}{{ $space->maxCapacity() }} pers.</p>
                        </td>
                        @foreach ($days as $d)
                            @php
                                $dayBookings = ($bookings[$space->id] ?? collect())->filter(fn ($b) => $b->starts_at->isSameDay($d));
                            @endphp
                            <td class="px-2 py-2 {{ $d->isToday() ? 'bg-terracotta-50/40' : '' }}">
                                @forelse ($dayBookings as $b)
                                    <a href="{{ route('admin.events.show', $b->event) }}"
                                       class="mb-1 block rounded border border-nuit-200 bg-nuit-50 px-2 py-1 text-[11px] leading-tight last:mb-0 hover:border-terracotta-400">
                                        <span class="font-semibold text-nuit-800">{{ $b->starts_at->format('H:i') }}–{{ $b->ends_at->format('H:i') }}</span>
                                        <span class="block truncate text-nuit-500">{{ $b->event->name }}</span>
                                        @if ($b->layout)<span class="block text-nuit-400">{{ $b->layout }}</span>@endif
                                    </a>
                                @empty
                                    <span class="block text-center text-[11px] text-nuit-300">libre</span>
                                @endforelse
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-nuit-400">Aucune salle configurée. <a href="{{ route('admin.events.spaces') }}" class="font-semibold text-terracotta-600">En créer une</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Parc --}}
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($spaces as $space)
            <div class="rounded-lg border border-sable-200 bg-white p-4">
                <p class="font-display text-base font-semibold text-nuit-900">{{ $space->name }}</p>
                <p class="mt-0.5 text-xs text-nuit-400">{{ $space->area ? $space->area.' m²' : '—' }}</p>
                <ul class="mt-2 flex flex-wrap gap-1 text-xs text-nuit-500">
                    @foreach (($space->layouts ?? []) as $name => $cap)
                        <li class="rounded bg-sable-100 px-1.5 py-0.5">{{ $name }} {{ $cap }}</li>
                    @endforeach
                </ul>
                @if ($space->features)
                    <p class="mt-2 text-xs text-nuit-400">{{ implode(' · ', $space->features) }}</p>
                @endif
                <p class="mt-2 text-xs text-nuit-500">{{ money($space->half_day_price) }} ½ j · {{ money($space->full_day_price) }} j</p>
            </div>
        @endforeach
    </div>
</div>
@endsection
