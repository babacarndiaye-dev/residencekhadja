@extends('admin.layout')
@section('title', 'Événements')

@php
    $start = $month->copy()->startOfMonth();
    $gridStart = $start->copy()->startOfWeek(\Carbon\CarbonInterface::MONDAY);
    $gridEnd = $month->copy()->endOfMonth()->endOfWeek(\Carbon\CarbonInterface::MONDAY);
    $byDay = [];
    foreach ($events as $e) {
        for ($d = $e->starts_at->copy()->startOfDay(); $d->lte($e->ends_at); $d->addDay()) {
            $byDay[$d->toDateString()][] = $e;
        }
    }
    $tone = ['option' => 'bg-laiton-100 text-laiton-800', 'confirme' => 'bg-emerald-100 text-emerald-800', 'realise' => 'bg-nuit-100 text-nuit-700', 'annule' => 'bg-sable-200 text-nuit-400 line-through'];
@endphp

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.events.index', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}" class="rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">←</a>
            <h2 class="font-display text-lg font-semibold text-nuit-900">{{ ucfirst($month->translatedFormat('F Y')) }}</h2>
            <a href="{{ route('admin.events.index', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}" class="rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">→</a>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.events.spaces') }}" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Salles</a>
            <a href="{{ route('admin.events.pipeline') }}" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Pipeline</a>
            <button onclick="document.getElementById('newEvent').classList.toggle('hidden')" class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Nouvel événement</button>
        </div>
    </div>

    <form id="newEvent" method="POST" action="{{ route('admin.events.store') }}" class="hidden grid gap-3 rounded-2xl bg-white p-5 shadow-card sm:grid-cols-3">
        @csrf
        <label class="text-xs font-semibold text-nuit-500 sm:col-span-2">Nom<input name="name" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
        <label class="text-xs font-semibold text-nuit-500">Type
            <select name="event_type" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                @foreach (config('events.event_types') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
            </select>
        </label>
        <label class="text-xs font-semibold text-nuit-500">Client<input name="client_name" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
        <label class="text-xs font-semibold text-nuit-500">Pax<input type="number" name="pax" min="0" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
        <label class="text-xs font-semibold text-nuit-500">Chambres à bloquer<input type="number" name="rooms_to_block" min="0" value="0" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
        <label class="text-xs font-semibold text-nuit-500">Début<input type="datetime-local" name="starts_at" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
        <label class="text-xs font-semibold text-nuit-500">Fin<input type="datetime-local" name="ends_at" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
        <div class="sm:col-span-3"><button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Créer en option</button></div>
    </form>

    @if ($expiringOptions->isNotEmpty())
        <div class="rounded-2xl border border-laiton-300 bg-laiton-50 p-4 text-sm text-nuit-800">
            <p class="font-semibold">Options bientôt expirées</p>
            <ul class="mt-1 flex flex-col gap-0.5">
                @foreach ($expiringOptions as $e)
                    <li><a href="{{ route('admin.events.show', $e) }}" class="hover:underline">{{ $e->name }} — expire le {{ $e->option_expires_on->format('d/m/Y') }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="overflow-x-auto rounded-2xl bg-white p-3 shadow-card">
        <div class="grid min-w-[720px] grid-cols-7 gap-px bg-sable-200 text-xs">
            @foreach (['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'] as $d)
                <div class="bg-white p-2 text-center font-semibold text-nuit-500">{{ $d }}</div>
            @endforeach
            @for ($day = $gridStart->copy(); $day->lte($gridEnd); $day->addDay())
                <div class="min-h-24 bg-white p-1.5 {{ $day->month !== $month->month ? 'opacity-40' : '' }}">
                    <p class="text-right text-xs text-nuit-400">{{ $day->day }}</p>
                    @foreach ($byDay[$day->toDateString()] ?? [] as $e)
                        <a href="{{ route('admin.events.show', $e) }}" class="mt-0.5 block truncate rounded px-1.5 py-0.5 text-xs font-medium {{ $tone[$e->status] ?? 'bg-sable-100' }}">
                            {{ $e->starts_at->isSameDay($day) ? $e->starts_at->format('H\hi').' ' : '' }}{{ $e->name }}
                        </a>
                    @endforeach
                </div>
            @endfor
        </div>
    </div>

    <section class="rounded-2xl bg-white p-5 shadow-card">
        <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">À venir</h3>
        <table class="w-full text-sm">
            <tbody>
                @forelse ($upcoming as $e)
                    <tr class="border-b border-sable-100">
                        <td class="py-2"><a href="{{ route('admin.events.show', $e) }}" class="font-medium text-nuit-900 hover:text-terracotta-600">{{ $e->name }}</a><span class="block text-xs text-nuit-400">{{ $e->client_name }} · {{ $e->pax }} pax</span></td>
                        <td class="py-2 text-xs text-nuit-500">{{ $e->starts_at->format('d/m/Y H\hi') }} → {{ $e->ends_at->format('d/m H\hi') }}</td>
                        <td class="py-2 text-right"><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $tone[$e->status] ?? 'bg-sable-100' }}">{{ $e->statusLabel() }}</span></td>
                    </tr>
                @empty
                    <tr><td class="py-6 text-center text-nuit-400">Aucun événement à venir.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
@endsection
