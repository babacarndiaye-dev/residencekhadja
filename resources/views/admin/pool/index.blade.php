@extends('admin.layout')
@section('title', 'Piscine')

@section('content')
<div class="flex flex-col gap-4" x-data="{ form: false }">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.pool.index', ['date' => $date->copy()->subDay()->toDateString()]) }}" class="inline-flex items-center rounded-md border border-sable-300 px-2 py-1.5 text-nuit-600 hover:border-terracotta-400" aria-label="Jour précédent"><x-icon name="chevron_left" size="18" /></a>
            <form method="GET" class="flex items-center gap-2">
                <input type="date" name="date" value="{{ $date->toDateString() }}" onchange="this.form.submit()" class="rounded-md border border-sable-300 px-3 py-1.5 text-sm">
            </form>
            <a href="{{ route('admin.pool.index', ['date' => $date->copy()->addDay()->toDateString()]) }}" class="inline-flex items-center rounded-md border border-sable-300 px-2 py-1.5 text-nuit-600 hover:border-terracotta-400" aria-label="Jour suivant"><x-icon name="chevron_right" size="18" /></a>
            <span class="ml-1 text-sm font-medium text-nuit-700">{{ ucfirst($date->translatedFormat('l d F Y')) }}</span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.pool.assets') }}" class="rounded-md border border-sable-300 px-3 py-1.5 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Parc</a>
            <button x-on:click="form = !form" class="rounded-md bg-terracotta-500 px-4 py-1.5 text-sm font-semibold text-white hover:bg-terracotta-600">+ Réservation</button>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-lg border border-sable-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Emplacements réservés</p>
            <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ $summary['booked'] }}</p>
        </div>
        <div class="rounded-lg border border-sable-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Personnes attendues</p>
            <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ $summary['guests'] }}</p>
        </div>
        <div class="rounded-lg border border-sable-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Recette du jour</p>
            <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ money($summary['revenue']) }}</p>
        </div>
    </div>

    {{-- Formulaire --}}
    <form x-show="form" x-cloak method="POST" action="{{ route('admin.pool.store') }}" class="grid gap-3 rounded-lg border border-sable-200 bg-white p-4 sm:grid-cols-3 lg:grid-cols-4">
        @csrf
        <input type="hidden" name="date" value="{{ $date->toDateString() }}">
        <label class="text-sm">
            <span class="mb-1 block text-xs font-semibold text-nuit-600">Emplacement</span>
            <select name="pool_asset_id" required class="w-full rounded-md border border-nuit-200 px-3 py-2 text-sm">
                @foreach ($assets as $a)
                    <option value="{{ $a->id }}">{{ $a->label }} · {{ $a->kindLabel() }} ({{ $a->capacity }} pers.)</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm">
            <span class="mb-1 block text-xs font-semibold text-nuit-600">Créneau</span>
            <select name="slot" required class="w-full rounded-md border border-nuit-200 px-3 py-2 text-sm">
                @foreach ($slots as $key => $s)
                    <option value="{{ $key }}">{{ $s['label'] }} ({{ $s['from'] }}–{{ $s['to'] }})</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm">
            <span class="mb-1 block text-xs font-semibold text-nuit-600">Client</span>
            <input name="guest_name" required maxlength="120" class="w-full rounded-md border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <label class="text-sm">
            <span class="mb-1 block text-xs font-semibold text-nuit-600">Séjour lié <span class="text-nuit-400">(facultatif)</span></span>
            <select name="reservation_id" class="w-full rounded-md border border-nuit-200 px-3 py-2 text-sm">
                <option value="">—</option>
                @foreach ($inHouse as $r)
                    <option value="{{ $r->id }}">Ch. {{ $r->room->number ?? '?' }} · {{ $r->guest->last_name }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm">
            <span class="mb-1 block text-xs font-semibold text-nuit-600">Personnes</span>
            <input type="number" name="guests" value="1" min="1" max="12" class="w-full rounded-md border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <label class="text-sm">
            <span class="mb-1 block text-xs font-semibold text-nuit-600">Téléphone</span>
            <input name="guest_phone" maxlength="40" class="w-full rounded-md border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <label class="text-sm sm:col-span-2">
            <span class="mb-1 block text-xs font-semibold text-nuit-600">Prix <span class="text-nuit-400">(auto si vide)</span> / Note</span>
            <div class="flex gap-2">
                <input type="number" name="price" min="0" placeholder="Prix" class="w-24 rounded-md border border-nuit-200 px-3 py-2 text-sm">
                <input name="note" maxlength="200" placeholder="Remarque" class="flex-1 rounded-md border border-nuit-200 px-3 py-2 text-sm">
            </div>
        </label>
        <div class="sm:col-span-3 lg:col-span-4">
            <button class="rounded-md bg-nuit-900 px-5 py-2 text-sm font-semibold text-white hover:bg-nuit-800">Réserver</button>
        </div>
    </form>

    {{-- Plan des emplacements --}}
    <div class="overflow-x-auto rounded-lg border border-sable-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-sable-50 text-left text-xs uppercase tracking-wider text-nuit-400">
                <tr><th class="px-4 py-3">Emplacement</th><th class="px-4 py-3">Réservations du jour</th></tr>
            </thead>
            <tbody>
                @foreach ($board as $row)
                    <tr class="border-t border-sable-100 align-top">
                        <td class="px-4 py-3">
                            <p class="font-medium text-nuit-900">{{ $row['asset']->label }}</p>
                            <p class="text-xs text-nuit-400">{{ $row['asset']->kindLabel() }} · {{ $row['asset']->capacity }} pers.</p>
                        </td>
                        <td class="px-4 py-3">
                            @forelse ($row['reservations'] as $res)
                                <div class="mb-1.5 flex flex-wrap items-center gap-2 rounded-md border border-sable-200 px-3 py-1.5 last:mb-0
                                    {{ in_array($res->status, ['cancelled','no_show']) ? 'opacity-50' : '' }}">
                                    <span class="rounded bg-sable-100 px-1.5 py-0.5 text-xs font-semibold">{{ $res->slotLabel() }}</span>
                                    <span class="font-medium text-nuit-900">{{ $res->guest_name }}</span>
                                    <span class="text-xs text-nuit-400">{{ $res->guests }} pers. · {{ money($res->price) }}</span>
                                    @if ($res->reservation)<span class="text-xs text-nuit-400">Ch. {{ $res->reservation->room->number ?? '?' }}</span>@endif
                                    <span class="ml-auto rounded-full px-2 py-0.5 text-xs font-semibold
                                        {{ ['booked'=>'bg-laiton-100 text-laiton-800','checked_in'=>'bg-emerald-100 text-emerald-800','done'=>'bg-sable-200 text-nuit-600','no_show'=>'bg-red-100 text-red-700','cancelled'=>'bg-sable-200 text-nuit-400'][$res->status] }}">
                                        {{ $res->statusLabel() }}
                                    </span>
                                    @if (in_array($res->status, ['booked','checked_in']))
                                        <form method="POST" action="{{ route('admin.pool.status', $res) }}" class="flex gap-1">
                                            @csrf
                                            @if ($res->status === 'booked')
                                                <button name="status" value="checked_in" class="text-xs font-semibold text-emerald-700 hover:underline">Installer</button>
                                            @endif
                                            <button name="status" value="done" class="text-xs font-semibold text-nuit-500 hover:underline">Terminer</button>
                                            <button name="status" value="no_show" class="text-xs font-semibold text-red-600 hover:underline">Absent</button>
                                            <button name="status" value="cancelled" class="text-xs font-semibold text-nuit-400 hover:underline">Annuler</button>
                                        </form>
                                    @endif
                                </div>
                            @empty
                                <span class="text-xs text-nuit-400">Libre toute la journée</span>
                            @endforelse
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
