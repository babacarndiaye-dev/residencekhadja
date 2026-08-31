@extends('admin.layout')
@section('title', 'QR codes')

@section('content')
<div class="flex flex-col gap-5">

    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.qr.index') }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-nuit-900 text-white' => ! $activeVenue && ! $activeType, 'bg-white text-nuit-700 shadow-card' => $activeVenue || $activeType])>Tous</a>
        @foreach ($venues as $v)
            <a href="{{ route('admin.qr.index', ['venue' => $v->id]) }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-nuit-900 text-white' => $activeVenue == $v->id, 'bg-white text-nuit-700 shadow-card' => $activeVenue != $v->id])>{{ $v->name }}</a>
        @endforeach
        <a href="{{ route('admin.qr.index', ['type' => 'room']) }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-nuit-900 text-white' => $activeType === 'room', 'bg-white text-nuit-700 shadow-card' => $activeType !== 'room'])>Chambres</a>
        <a href="{{ route('admin.qr.print', ['venue' => $activeVenue]) }}" target="_blank" class="ml-auto rounded-full bg-terracotta-500 px-4 py-1.5 text-sm font-semibold text-white">Feuille d’impression</a>
    </div>

    <details class="rounded-2xl bg-white p-4 shadow-card">
        <summary class="cursor-pointer text-sm font-semibold text-nuit-900">+ Ajouter un point QR</summary>
        <form method="POST" action="{{ route('admin.qr.store') }}" class="mt-3 grid gap-3 sm:grid-cols-[1fr_1fr_1fr_auto_auto]">
            @csrf
            <select name="venue_id" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                @foreach ($venues as $v)<option value="{{ $v->id }}">{{ $v->name }}</option>@endforeach
            </select>
            <input type="text" name="label" placeholder="Libellé (ex: Table 20)" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <input type="text" name="zone" placeholder="Zone (ex: Terrasse)" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <select name="type" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm"><option value="table">Table</option><option value="spot">Espace</option></select>
            <button class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Créer</button>
        </form>
    </details>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($locations as $loc)
            <div class="flex gap-3 rounded-2xl bg-white p-3 shadow-card">
                <img src="{{ route('admin.qr.svg', $loc) }}" alt="QR {{ $loc->label }}" width="88" height="88" class="h-22 w-22 shrink-0 rounded-lg border border-sable-200">
                <div class="flex min-w-0 flex-1 flex-col">
                    <p class="truncate text-sm font-semibold text-nuit-900">{{ $loc->label }}</p>
                    <p class="font-mono text-xs text-nuit-400">{{ $loc->code }}</p>
                    <p class="text-xs text-nuit-400">{{ $loc->venue?->name }}{{ $loc->zone ? ' · '.$loc->zone : '' }}</p>
                    <form method="POST" action="{{ route('admin.qr.update', $loc) }}" class="mt-auto flex flex-wrap items-center gap-2 pt-2">
                        @csrf @method('PUT')
                        <input type="hidden" name="label" value="{{ $loc->label }}">
                        @if ($loc->type === 'table')
                            <input type="text" name="zone" value="{{ $loc->zone }}" placeholder="Zone" class="w-24 rounded border border-nuit-200 px-2 py-1 text-xs">
                        @endif
                        <label class="flex items-center gap-1 text-xs text-nuit-600"><input type="checkbox" name="is_active" value="1" @checked($loc->is_active) class="rounded border-nuit-300 text-terracotta-500"> Actif</label>
                        <button class="rounded-full bg-nuit-900 px-3 py-1 text-xs font-semibold text-white">OK</button>
                        <a href="{{ $loc->url() }}" target="_blank" class="text-xs text-terracotta-600 hover:underline">Ouvrir</a>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
    <div>{{ $locations->links() }}</div>
</div>
@endsection
