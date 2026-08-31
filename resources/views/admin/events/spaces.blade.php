@extends('admin.layout')
@section('title', 'Salles événementielles')

@section('content')
<div class="flex flex-col gap-6">
    <a href="{{ route('admin.events.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Événements</a>

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($spaces as $space)
            <form method="POST" action="{{ route('admin.events.spaces.update', $space) }}" class="rounded-2xl bg-white p-5 shadow-card">
                @csrf @method('PUT')
                <div class="flex items-center justify-between">
                    <input name="name" value="{{ $space->name }}" class="font-display text-lg font-semibold text-nuit-900 outline-none">
                    <label class="text-xs text-nuit-500"><input type="checkbox" name="is_active" value="1" @checked($space->is_active)> actif</label>
                </div>
                <div class="mt-2 grid grid-cols-3 gap-2 text-xs">
                    <label class="text-nuit-500">Surface m²<input type="number" name="area" value="{{ $space->area }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-2 py-1"></label>
                    <label class="text-nuit-500">½ journée<input type="number" name="half_day_price" value="{{ $space->half_day_price }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-2 py-1"></label>
                    <label class="text-nuit-500">Journée<input type="number" name="full_day_price" value="{{ $space->full_day_price }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-2 py-1"></label>
                </div>
                <p class="mt-3 text-xs font-semibold uppercase tracking-wider text-nuit-400">Capacités</p>
                <div class="mt-1 grid grid-cols-3 gap-2 text-xs sm:grid-cols-4">
                    @foreach ($layouts as $l)
                        <label class="text-nuit-500">{{ $l }}
                            <input type="number" name="layout_capacity[{{ $l }}]" value="{{ $space->layouts[$l] ?? '' }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-2 py-1">
                        </label>
                    @endforeach
                </div>
                <button class="mt-3 rounded-full bg-nuit-900 px-4 py-1.5 text-xs font-semibold text-white">Enregistrer</button>
            </form>
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.events.spaces.store') }}" class="rounded-2xl bg-white p-5 shadow-card">
        @csrf
        <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Nouvelle salle</h3>
        <div class="grid gap-3 sm:grid-cols-4">
            <label class="text-xs font-semibold text-nuit-500 sm:col-span-2">Nom<input name="name" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
            <label class="text-xs font-semibold text-nuit-500">Surface m²<input type="number" name="area" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
            <label class="text-xs font-semibold text-nuit-500">Prix journée<input type="number" name="full_day_price" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
            @foreach ($layouts as $l)
                <label class="text-xs text-nuit-500">{{ $l }}<input type="number" name="layout_capacity[{{ $l }}]" class="mt-1 w-full rounded-lg border border-nuit-200 px-2 py-1.5 text-sm"></label>
            @endforeach
        </div>
        <button class="mt-3 rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Créer la salle</button>
    </form>
</div>
@endsection
