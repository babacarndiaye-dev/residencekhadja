@extends('admin.layout')
@section('title', 'Ménage — terrain')

@php $isAgent = auth()->user()->role === 'housekeeping'; @endphp

@section('content')
<div class="mx-auto flex max-w-xl flex-col gap-3">

    <div class="flex items-center justify-between">
        <p class="text-sm text-nuit-500">{{ $done }} / {{ $tasks->count() }} faites aujourd’hui</p>
        @if ($isAgent)
            <a href="{{ route('admin.housekeeping.mobile', ['mine' => $mine ? 0 : 1]) }}"
               class="rounded-full border border-nuit-200 px-3 py-1.5 text-xs font-semibold text-nuit-700">
                {{ $mine ? 'Voir tout l’étage' : 'Mes chambres seulement' }}
            </a>
        @endif
    </div>

    @forelse ($tasks as $task)
        <div x-data="{ incident: false }" @class([
            'rounded-2xl bg-white p-4 shadow-card',
            'opacity-60' => in_array($task->status, ['done', 'inspected']),
        ])>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-display text-xl font-semibold text-nuit-900">Ch. {{ $task->room->number }}</p>
                    <p class="text-xs text-nuit-500">
                        {{ $types[$task->type] ?? $task->type }}
                        @if ($task->reservation?->guest) · {{ $task->reservation->guest->last_name }} @endif
                    </p>
                </div>
                <x-admin.badge :status="$task->status" />
            </div>

            @if ($isAgent && ! in_array($task->status, ['done', 'inspected']))
                <div class="mt-3 grid grid-cols-3 gap-2">
                    <form method="POST" action="{{ route('admin.housekeeping.status', $task) }}">
                        @csrf <input type="hidden" name="status" value="in_progress">
                        <button @class([
                            'w-full rounded-xl py-3 text-sm font-semibold',
                            'bg-nuit-900 text-white' => $task->status !== 'in_progress',
                            'bg-nuit-200 text-nuit-500' => $task->status === 'in_progress',
                        ]) @disabled($task->status === 'in_progress')>Commencer</button>
                    </form>
                    <form method="POST" action="{{ route('admin.housekeeping.status', $task) }}">
                        @csrf <input type="hidden" name="status" value="done">
                        <button class="w-full rounded-xl bg-emerald-600 py-3 text-sm font-semibold text-white">Terminé</button>
                    </form>
                    <form method="POST" action="{{ route('admin.housekeeping.status', $task) }}">
                        @csrf <input type="hidden" name="status" value="blocked">
                        <button class="w-full rounded-xl border border-red-200 py-3 text-sm font-semibold text-red-600">Bloquée</button>
                    </form>
                </div>
            @endif

            <button type="button" x-on:click="incident = !incident" class="mt-3 text-xs font-semibold text-terracotta-600">
                <span x-show="!incident">+ Signaler un incident</span>
                <span x-show="incident" x-cloak>Annuler</span>
            </button>

            <form x-show="incident" x-cloak method="POST" action="{{ route('admin.housekeeping.incidents.store') }}" class="mt-2 flex flex-col gap-2 border-t border-sable-200 pt-3">
                @csrf
                <input type="hidden" name="room_id" value="{{ $task->room_id }}">
                <select name="category" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    @foreach ($incidentCategories as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <textarea name="description" rows="2" required placeholder="Décrire le problème…" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm"></textarea>
                <label class="flex items-center gap-2 text-xs text-nuit-600">
                    <input type="checkbox" name="raise_ticket" value="1" class="rounded border-nuit-300 text-terracotta-500"> Ouvrir un ticket maintenance
                </label>
                <button class="rounded-xl bg-nuit-900 py-2.5 text-sm font-semibold text-white">Envoyer l’incident</button>
            </form>
        </div>
    @empty
        <p class="rounded-2xl bg-white px-4 py-12 text-center text-sm text-nuit-400">Aucune chambre à faire.</p>
    @endforelse
</div>
@endsection
