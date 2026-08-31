@extends('layouts.app')

@section('title', 'Séminaires & Événements')
@section('description', "Trois salles modulables jusqu'à 250 personnes, restauration sur mesure et régie technique : organisez votre séminaire ou votre événement à l'Hôtel Résidence Khadija, Thiès.")

@section('content')

    <x-page-hero eyebrow="Séminaires & Événements" title="Vos événements, orchestrés de A à Z"
                 :image="asset('img/hotel/salle-seminaire.jpg')">
        Journées d'étude, conférences, dîners de gala, cérémonies familiales : un interlocuteur unique,
        des espaces lumineux et une cuisine qui fait la différence.
    </x-page-hero>

    <section class="py-16 sm:py-24">
        <x-container>
            <x-section-heading eyebrow="Nos salles" title="Trois espaces, toutes les configurations" class="reveal" />

            <div class="mt-12 flex flex-col gap-8">
                @foreach ($rooms as $room)
                    <article class="reveal grid gap-6 overflow-hidden rounded-2xl bg-white shadow-card lg:grid-cols-[1fr_1.4fr]">
                        <img src="{{ $room['image'] }}" alt="{{ $room['name'] }}" width="700" height="500"
                             loading="lazy" decoding="async" class="h-full min-h-[220px] w-full object-cover">
                        <div class="flex flex-col gap-4 p-6">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <h3 class="text-2xl text-nuit-900">{{ $room['name'] }}</h3>
                                <span class="text-sm text-nuit-400">{{ $room['area'] }} m²</span>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($room['capacities'] as $layout => $pax)
                                    <span class="rounded-full bg-sable-100 px-3 py-1 text-xs font-medium text-nuit-700">{{ $layout }} · {{ $pax }}</span>
                                @endforeach
                            </div>
                            <ul class="flex flex-wrap gap-x-5 gap-y-1 text-sm text-ink-soft">
                                @foreach ($room['features'] as $f)
                                    <li class="flex items-center gap-1.5">
                                        <span class="h-1.5 w-1.5 rounded-full bg-terracotta-500"></span>{{ $f }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </article>
                @endforeach
            </div>
        </x-container>
    </section>

    <section id="devis" class="scroll-mt-24 bg-sable-100 py-16 sm:py-24">
        @php
            $ctl = 'block w-full min-w-0 max-w-full rounded-lg border border-nuit-200 bg-white px-3.5 py-3 text-base text-nuit-900 focus:border-terracotta-500 focus:outline-none sm:text-sm';
            $lab = 'mb-1 block text-xs font-semibold uppercase tracking-wider text-nuit-500';
        @endphp
        <x-container class="grid gap-10 lg:grid-cols-3">
            <div class="reveal lg:col-span-1">
                <x-section-heading eyebrow="Prestations" title="Ce que nous prenons en charge" />
                <ul class="mt-6 grid gap-3">
                    @foreach ($services as $service)
                        <li class="flex items-start gap-3 text-sm text-nuit-800">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-terracotta-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.9a1 1 0 1 1 1.4-1.4l3.1 3.1 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                            {{ $service }}
                        </li>
                    @endforeach
                </ul>
                <p class="mt-6 text-sm text-nuit-500">
                    Réponse chiffrée sous 24 h ouvrées. Un interlocuteur commercial dédié suit votre dossier de la première demande jusqu'au jour J.
                </p>
            </div>

            <form method="POST" action="{{ route('events.enquiry') }}"
                  class="reveal flex flex-col gap-5 rounded-2xl bg-white p-6 shadow-card sm:p-8 lg:col-span-2">
                @csrf
                <div>
                    <h3 class="text-xl text-nuit-900">Demander un devis</h3>
                    <p class="mt-1 text-sm text-nuit-500">Décrivez votre événement : nous préparons une proposition personnalisée (salle, restauration, technique).</p>
                </div>

                @if ($errors->any())
                    <div class="rounded-lg border border-terracotta-300 bg-terracotta-50 px-4 py-3 text-sm text-terracotta-800">
                        <ul class="list-inside list-disc space-y-0.5">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                {{-- Pot de miel anti-spam --}}
                <div class="hidden" aria-hidden="true">
                    <label>Ne pas remplir<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>

                {{-- Contact --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="e_name" class="{{ $lab }}">Nom <span class="text-terracotta-600">*</span></label>
                        <input type="text" id="e_name" name="contact_name" value="{{ old('contact_name') }}" required maxlength="120" class="{{ $ctl }}">
                    </div>
                    <div>
                        <label for="e_company" class="{{ $lab }}">Organisation</label>
                        <input type="text" id="e_company" name="company" value="{{ old('company') }}" maxlength="150" class="{{ $ctl }}">
                    </div>
                    <div>
                        <label for="e_email" class="{{ $lab }}">E-mail <span class="text-terracotta-600">*</span></label>
                        <input type="email" id="e_email" name="contact_email" value="{{ old('contact_email') }}" required maxlength="180" class="{{ $ctl }}">
                    </div>
                    <div>
                        <label for="e_phone" class="{{ $lab }}">Téléphone</label>
                        <input type="tel" id="e_phone" name="contact_phone" value="{{ old('contact_phone') }}" maxlength="40" class="{{ $ctl }}">
                    </div>
                </div>

                {{-- Événement --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="e_type" class="{{ $lab }}">Type d'événement <span class="text-terracotta-600">*</span></label>
                        <select id="e_type" name="event_type" class="{{ $ctl }}">
                            @foreach ($eventTypes as $k => $label)
                                <option value="{{ $k }}" @selected(old('event_type') === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="e_date" class="{{ $lab }}">Date souhaitée</label>
                        <input type="date" id="e_date" name="date" value="{{ old('date') }}" min="{{ now()->toDateString() }}" class="{{ $ctl }} appearance-none">
                    </div>
                    <div>
                        <label for="e_pax" class="{{ $lab }}">Participants <span class="text-terracotta-600">*</span></label>
                        <input type="number" id="e_pax" name="pax" value="{{ old('pax', 30) }}" min="1" max="5000" required class="{{ $ctl }}">
                    </div>
                    <div>
                        <span class="{{ $lab }}">Durée <span class="text-terracotta-600">*</span></span>
                        <div class="flex gap-2">
                            @foreach (['full_day' => 'Journée', 'half_day' => 'Demi-journée'] as $k => $label)
                                <label class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg border border-nuit-200 px-3 py-2.5 text-sm has-[:checked]:border-terracotta-500 has-[:checked]:bg-terracotta-50 has-[:checked]:font-semibold has-[:checked]:text-terracotta-700">
                                    <input type="radio" name="duration" value="{{ $k }}" @checked(old('duration', 'full_day') === $k) class="accent-terracotta-500">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label for="e_layout" class="{{ $lab }}">Disposition</label>
                        <select id="e_layout" name="layout" class="{{ $ctl }}">
                            <option value="">Sans préférence</option>
                            @foreach ($layouts as $layout)
                                <option value="{{ $layout }}" @selected(old('layout') === $layout)>{{ $layout }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($spaces->isNotEmpty())
                        <div class="sm:col-span-2">
                            <label for="e_space" class="{{ $lab }}">Salle souhaitée</label>
                            <select id="e_space" name="event_space_id" class="{{ $ctl }}">
                                <option value="">Sans préférence — à conseiller</option>
                                @foreach ($spaces as $space)
                                    <option value="{{ $space->id }}" @selected(old('event_space_id') == $space->id)>{{ $space->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                {{-- Options --}}
                <div class="grid gap-5 sm:grid-cols-2">
                    <fieldset>
                        <legend class="{{ $lab }}">Restauration &amp; pauses</legend>
                        <div class="flex flex-col gap-1.5">
                            @foreach ($catering as $pkg)
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-sable-200 px-3 py-2 text-sm has-[:checked]:border-terracotta-400 has-[:checked]:bg-terracotta-50">
                                    <input type="checkbox" name="catering[]" value="{{ $pkg['code'] }}" @checked(in_array($pkg['code'], old('catering', []), true)) class="accent-terracotta-500">
                                    <span>{{ $pkg['name'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend class="{{ $lab }}">Technique &amp; régie</legend>
                        <div class="flex flex-col gap-1.5">
                            @foreach ($equipment as $eq)
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-sable-200 px-3 py-2 text-sm has-[:checked]:border-terracotta-400 has-[:checked]:bg-terracotta-50">
                                    <input type="checkbox" name="equipment[]" value="{{ $eq['code'] }}" @checked(in_array($eq['code'], old('equipment', []), true)) class="accent-terracotta-500">
                                    <span>{{ $eq['name'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                </div>

                <div>
                    <label for="e_message" class="{{ $lab }}">Précisions</label>
                    <textarea id="e_message" name="message" rows="4" maxlength="2000" class="{{ $ctl }}"
                              placeholder="Hébergement des participants, horaires, contraintes particulières…">{{ old('message') }}</textarea>
                </div>

                <label class="flex items-start gap-2 text-xs text-nuit-500">
                    <input type="checkbox" name="consent" value="1" required class="mt-0.5 accent-terracotta-500">
                    J'accepte que mes informations soient utilisées pour traiter ma demande de devis.
                </label>

                <x-button type="submit" variant="primary">Envoyer la demande de devis</x-button>
            </form>
        </x-container>
    </section>

@endsection
