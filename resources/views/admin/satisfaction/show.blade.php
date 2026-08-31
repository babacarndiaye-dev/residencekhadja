@extends('admin.layout')
@section('title', 'Avis client')

@section('content')
@php
    $stars = fn ($n) => str_repeat('★', (int) $n).str_repeat('☆', 5 - (int) $n);
@endphp
<div class="flex flex-col gap-4">
    <a href="{{ route('admin.satisfaction.index') }}" class="inline-flex items-center gap-1 text-sm text-nuit-500 hover:text-terracotta-600"><x-icon name="chevron_left" size="16" /> Satisfaction</a>

    <div class="grid gap-4 lg:grid-cols-[1.6fr_1fr]">
        {{-- Réponse --}}
        <div class="flex flex-col gap-4">
            <div class="rounded-lg border border-sable-200 bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-display text-lg font-semibold text-nuit-900">{{ $survey->displayAuthor() }}</p>
                        <p class="text-sm text-nuit-500">
                            @if ($survey->reservation)
                                Séjour {{ $survey->reservation->reference }}
                                @if ($survey->reservation->roomCategory) · {{ $survey->reservation->roomCategory->name }} @endif ·
                            @endif
                            Répondu le {{ $survey->completed_at?->translatedFormat('d F Y') }}
                        </p>
                    </div>
                    @if ($survey->needsAttention())
                        <span class="rounded bg-terracotta-100 px-2 py-1 text-xs font-semibold text-terracotta-700">À traiter en priorité</span>
                    @endif
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-6">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-nuit-400">Note globale</p>
                        <p class="text-2xl text-laiton-500">{{ $stars($survey->rating_overall) }} <span class="text-base text-nuit-500">{{ $survey->rating_overall }}/5</span></p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-nuit-400">NPS</p>
                        <p class="font-display text-2xl font-semibold {{ $survey->isDetractor() ? 'text-terracotta-700' : ($survey->isPromoter() ? 'text-emerald-700' : 'text-nuit-900') }}">
                            {{ $survey->nps_score !== null ? $survey->nps_score.' / 10' : '—' }}
                        </p>
                    </div>
                </div>

                @if ($survey->category_ratings)
                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        @foreach ($categories as $key => $label)
                            @continue($survey->categoryRating($key) === null)
                            <div class="flex items-center justify-between rounded border border-sable-200 px-3 py-2 text-sm">
                                <span class="text-nuit-600">{{ $label }}</span>
                                <span class="text-laiton-500">{{ $stars($survey->categoryRating($key)) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($survey->comment)
                    <blockquote class="mt-4 border-l-2 border-terracotta-400 bg-sable-50 px-4 py-3 text-sm leading-relaxed text-nuit-700">
                        « {{ $survey->comment }} »
                    </blockquote>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-sable-100 pt-3 text-xs text-nuit-500">
                    @if ($survey->consent_publish)
                        <span class="rounded bg-emerald-100 px-1.5 py-0.5 font-semibold text-emerald-700">Accord de publication</span>
                        <span>Signature : « {{ $survey->author_label ?: $survey->displayAuthor() }} »</span>
                    @else
                        <span class="rounded bg-sable-100 px-1.5 py-0.5">Pas d’accord de publication</span>
                    @endif
                </div>

                @if ($survey->consent_publish && $survey->comment)
                    <form method="POST" action="{{ route('admin.satisfaction.publish', $survey->id) }}" class="mt-3">
                        @csrf
                        <button class="rounded-md {{ $survey->is_published ? 'border border-sable-300 text-nuit-700 hover:border-terracotta-400' : 'bg-terracotta-500 text-white hover:bg-terracotta-600' }} px-4 py-2 text-sm font-semibold">
                            {{ $survey->is_published ? 'Retirer du site' : 'Publier sur le site' }}
                        </button>
                        @if ($survey->is_published)<span class="ml-2 text-xs text-nuit-400">Publié le {{ $survey->published_at?->translatedFormat('d M Y') }}</span>@endif
                    </form>
                @endif
            </div>

            {{-- Suivi interne --}}
            <form method="POST" action="{{ route('admin.satisfaction.update', $survey->id) }}" class="rounded-lg border border-sable-200 bg-white p-5">
                @csrf
                @method('PUT')
                <p class="font-display text-lg font-semibold text-nuit-900">Suivi interne</p>
                <label for="staff_note" class="mt-3 block text-xs font-semibold uppercase tracking-wider text-nuit-400">Note d’équipe</label>
                <textarea id="staff_note" name="staff_note" rows="4" maxlength="2000"
                          class="mt-1 block w-full rounded-lg border border-sable-300 px-3 py-2 text-sm focus:border-terracotta-500 focus:outline-none">{{ old('staff_note', $survey->staff_note) }}</textarea>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <select name="status" class="rounded-md border border-sable-300 px-3 py-2 text-sm">
                        <option value="received" @selected($survey->status === 'received')>Reçu</option>
                        <option value="triaged" @selected($survey->status === 'triaged')>Traité</option>
                    </select>
                    <button class="rounded-md bg-nuit-900 px-5 py-2 text-sm font-semibold text-white hover:bg-terracotta-500">Enregistrer</button>
                </div>
            </form>
        </div>

        {{-- Méta --}}
        <div class="rounded-lg border border-sable-200 bg-white p-5 text-sm">
            <p class="font-display text-base font-semibold text-nuit-900">Informations</p>
            <dl class="mt-3 space-y-2">
                <div class="flex justify-between gap-2"><dt class="text-nuit-400">Canal</dt><dd class="text-nuit-700">{{ $survey->channel }}</dd></div>
                <div class="flex justify-between gap-2"><dt class="text-nuit-400">Invitation</dt><dd class="text-nuit-700">{{ $survey->sent_at?->translatedFormat('d M Y') ?? '—' }}</dd></div>
                <div class="flex justify-between gap-2"><dt class="text-nuit-400">Relance</dt><dd class="text-nuit-700">{{ $survey->reminded_at?->translatedFormat('d M Y') ?? '—' }}</dd></div>
                <div class="flex justify-between gap-2"><dt class="text-nuit-400">Réponse</dt><dd class="text-nuit-700">{{ $survey->completed_at?->translatedFormat('d M Y H:i') ?? '—' }}</dd></div>
                <div class="flex justify-between gap-2"><dt class="text-nuit-400">Traité par</dt><dd class="text-nuit-700">{{ $survey->handledBy?->name ?? '—' }}</dd></div>
                @if ($survey->guest)
                    <div class="flex justify-between gap-2"><dt class="text-nuit-400">Client</dt><dd class="text-right text-nuit-700">{{ $survey->guest->fullName() }}<br><span class="text-xs text-nuit-400">{{ $survey->guest->email }}</span></dd></div>
                @endif
            </dl>
            @if ($survey->reservation)
                <a href="{{ route('admin.reservations.show', $survey->reservation) }}" class="mt-4 inline-block text-sm font-semibold text-terracotta-600 hover:underline">Voir la réservation ↗</a>
            @endif
        </div>
    </div>
</div>
@endsection
