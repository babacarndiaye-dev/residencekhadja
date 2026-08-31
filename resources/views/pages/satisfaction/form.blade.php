@extends('layouts.app')

@section('title', 'Votre avis')
@section('description', 'Partagez votre expérience à l’Hôtel Résidence Khadija.')

@push('head')<meta name="robots" content="noindex,nofollow">@endpush

@section('content')
@php
    $ctl = 'block w-full min-w-0 max-w-full rounded-lg border border-nuit-200 bg-white px-3.5 py-3 text-base text-nuit-900 focus:border-terracotta-500 focus:outline-none sm:text-sm';
    $lab = 'mb-1 block text-xs font-semibold uppercase tracking-wider text-nuit-500';
@endphp

<section class="bg-sable-100 py-12 sm:py-20">
    <x-container size="narrow">
        <div class="reveal">
            <span class="eyebrow text-terracotta-600">Votre séjour</span>
            <h1 class="mt-3 text-3xl text-nuit-900 sm:text-4xl">Comment s'est passé votre séjour ?</h1>
            <p class="mt-3 text-sm text-nuit-500">
                Bonjour {{ $survey->guest?->first_name ?? $survey->reservation?->guest?->first_name }},
                merci de prendre une minute. Votre retour aide toute l'équipe à progresser.
                @if ($survey->reservation)
                    <br><span class="text-nuit-400">Séjour réf. {{ $survey->reservation->reference }}
                    @if ($survey->reservation->roomCategory) · {{ $survey->reservation->roomCategory->name }} @endif</span>
                @endif
            </p>
        </div>

        <form method="POST" action="{{ route('satisfaction.store', $survey->token) }}"
              x-data="{ overall: {{ (int) old('rating_overall') }}, hover: 0, consent: {{ old('consent_publish') ? 'true' : 'false' }} }"
              class="reveal mt-8 flex flex-col gap-7 rounded-2xl bg-white p-6 shadow-card sm:p-8">
            @csrf

            @if ($errors->any())
                <div class="rounded-lg border border-terracotta-300 bg-terracotta-50 px-4 py-3 text-sm text-terracotta-800">
                    <ul class="list-inside list-disc space-y-0.5">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            {{-- Note globale --}}
            <div>
                <span class="{{ $lab }}">Note globale <span class="text-terracotta-600">*</span></span>
                <div class="flex items-center gap-1.5" x-on:mouseleave="hover = 0">
                    <template x-for="n in 5" :key="n">
                        <button type="button" x-on:click="overall = n" x-on:mouseenter="hover = n"
                                class="p-1 text-3xl leading-none transition-colors"
                                :class="(hover || overall) >= n ? 'text-laiton-500' : 'text-nuit-200'"
                                :aria-label="n + ' étoile' + (n > 1 ? 's' : '')">★</button>
                    </template>
                    <span class="ml-2 text-sm text-nuit-500" x-text="overall ? overall + ' / 5' : ''"></span>
                </div>
                <input type="hidden" name="rating_overall" :value="overall || ''">
            </div>

            {{-- NPS --}}
            <div>
                <span class="{{ $lab }}">{{ $npsQuestion }}</span>
                <div class="flex flex-wrap gap-1.5">
                    @for ($i = 0; $i <= 10; $i++)
                        <label class="grid h-9 w-9 cursor-pointer place-items-center rounded-lg border border-nuit-200 text-sm has-[:checked]:border-terracotta-500 has-[:checked]:bg-terracotta-500 has-[:checked]:font-semibold has-[:checked]:text-white">
                            <input type="radio" name="nps_score" value="{{ $i }}" @checked((string) old('nps_score') === (string) $i) class="sr-only">
                            {{ $i }}
                        </label>
                    @endfor
                </div>
                <div class="mt-1 flex justify-between text-[11px] text-nuit-400">
                    <span>Pas du tout</span><span>Absolument</span>
                </div>
            </div>

            {{-- Critères --}}
            <div>
                <span class="{{ $lab }}">En détail</span>
                <div class="flex flex-col divide-y divide-sable-100 rounded-lg border border-sable-200">
                    @foreach ($categories as $key => $label)
                        <div class="flex flex-wrap items-center justify-between gap-2 px-3 py-2.5">
                            <span class="text-sm text-nuit-700">{{ $label }}</span>
                            <div class="flex gap-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    <label class="grid h-8 w-8 cursor-pointer place-items-center rounded border border-nuit-200 text-xs has-[:checked]:border-laiton-500 has-[:checked]:bg-laiton-500 has-[:checked]:text-white">
                                        <input type="radio" name="categories[{{ $key }}]" value="{{ $i }}"
                                               @checked((string) old("categories.$key") === (string) $i) class="sr-only">
                                        {{ $i }}
                                    </label>
                                @endfor
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Commentaire --}}
            <div>
                <label for="s_comment" class="{{ $lab }}">Votre commentaire</label>
                <textarea id="s_comment" name="comment" rows="4" maxlength="2000" class="{{ $ctl }}"
                          placeholder="Ce qui vous a plu, ce qui pourrait être amélioré…">{{ old('comment') }}</textarea>
            </div>

            {{-- Publication --}}
            <div class="rounded-lg bg-sable-50 p-4">
                <label class="flex items-start gap-2.5 text-sm text-nuit-700">
                    <input type="checkbox" name="consent_publish" value="1" x-model="consent" @checked(old('consent_publish')) class="mt-0.5 accent-terracotta-500">
                    J'autorise l'hôtel à publier mon commentaire (sans mon nom complet) sur son site.
                </label>
                <div x-show="consent" x-cloak class="mt-3">
                    <label for="s_author" class="{{ $lab }}">Signature affichée</label>
                    <input type="text" id="s_author" name="author_label" value="{{ old('author_label') }}" maxlength="120"
                           placeholder="Ex. Awa D., Dakar" class="{{ $ctl }}">
                </div>
            </div>

            <button type="submit" :disabled="!overall"
                    class="inline-flex items-center justify-center bg-terracotta-500 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-terracotta-600 disabled:opacity-50">
                Envoyer mon avis
            </button>
        </form>
    </x-container>
</section>
@endsection
