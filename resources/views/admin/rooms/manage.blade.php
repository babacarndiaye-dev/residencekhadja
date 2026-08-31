@extends('admin.layout')
@section('title', 'Gestion des chambres')

@php $hasFilter = collect($filters)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty(); @endphp

@section('content')
<div class="flex flex-col gap-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-nuit-500">Créez, renommez, activez ou retirez les chambres physiques de l’hôtel.</p>
        <a href="{{ route('admin.rooms.index') }}" class="text-sm font-medium text-terracotta-600 hover:text-terracotta-700">← Plan des chambres</a>
    </div>

    {{-- Synthèse par catégorie --}}
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($summary as $row)
            <div class="rounded-2xl bg-white p-4 shadow-card">
                <p class="text-xs uppercase tracking-wider text-nuit-400">{{ $row['name'] }}</p>
                <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ $row['sellable'] }}<span class="ml-1 text-xs font-medium text-nuit-400">vendables</span></p>
                <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-nuit-500">
                    <span>{{ $row['total'] }} au total</span>
                    <span>{{ $row['active'] }} actives</span>
                    @if ($row['oos'])<span class="text-red-600">{{ $row['oos'] }} hors service</span>@endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Ajouter des chambres --}}
    <div x-data="{ open: {{ old('mode') ? 'true' : 'false' }}, mode: '{{ old('mode', 'single') }}' }" class="rounded-2xl bg-white shadow-card">
        <button type="button" x-on:click="open = !open" class="flex w-full items-center justify-between px-5 py-4 text-left">
            <span class="font-display text-sm font-semibold text-nuit-900">+ Ajouter des chambres</span>
            <svg class="h-4 w-4 text-nuit-400 transition-transform" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 8l5 5 5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>

        <form method="POST" action="{{ route('admin.rooms.store') }}" x-show="open" x-cloak x-transition class="border-t border-sable-200 p-5">
            @csrf
            <input type="hidden" name="mode" :value="mode">

            <div class="mb-4 inline-flex rounded-xl bg-sable-100 p-1 text-sm">
                <button type="button" x-on:click="mode = 'single'" :class="mode === 'single' ? 'bg-white shadow-card text-nuit-900' : 'text-nuit-500'" class="rounded-lg px-4 py-1.5 font-medium">Une chambre</button>
                <button type="button" x-on:click="mode = 'bulk'" :class="mode === 'bulk' ? 'bg-white shadow-card text-nuit-900' : 'text-nuit-500'" class="rounded-lg px-4 py-1.5 font-medium">Une série</button>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-nuit-600">Catégorie</span>
                    <select name="room_category_id" required class="w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                        <option value="">—</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" @selected(old('room_category_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-nuit-600">Statut initial</span>
                    <select name="status" required class="w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                        @foreach ($statuses as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', 'propre') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-nuit-600">Étage</span>
                    <input name="floor" value="{{ old('floor') }}" maxlength="10" placeholder="Ex. 2" class="w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-nuit-600">Bâtiment <span class="text-nuit-400">(facultatif)</span></span>
                    <input name="building" value="{{ old('building') }}" maxlength="40" placeholder="Ex. Aile jardin" class="w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>

                {{-- Une chambre --}}
                <label class="block" x-show="mode === 'single'">
                    <span class="mb-1 block text-xs font-medium text-nuit-600">Numéro</span>
                    <input name="number" value="{{ old('number') }}" maxlength="20" placeholder="Ex. 204" class="w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    @error('number')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>

                {{-- Une série --}}
                <template x-if="mode === 'bulk'">
                    <div class="contents">
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-nuit-600">Préfixe <span class="text-nuit-400">(facultatif)</span></span>
                            <input name="prefix" value="{{ old('prefix') }}" maxlength="10" placeholder="Ex. A-" class="w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-nuit-600">De</span>
                            <input type="number" name="from" value="{{ old('from') }}" min="1" max="9999" class="w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                            @error('from')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-nuit-600">À</span>
                            <input type="number" name="to" value="{{ old('to') }}" min="1" max="9999" class="w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                            @error('to')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-nuit-600">Remplir à N chiffres <span class="text-nuit-400">(facultatif)</span></span>
                            <input type="number" name="pad" value="{{ old('pad') }}" min="1" max="6" placeholder="Ex. 3 → 007" class="w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                        </label>
                    </div>
                </template>
            </div>

            <div class="mt-4 flex items-center gap-3">
                <button class="rounded-xl bg-nuit-900 px-5 py-2 text-sm font-semibold text-white hover:bg-nuit-800">Créer</button>
                <span class="text-xs text-nuit-400" x-show="mode === 'bulk'">Les numéros déjà utilisés seront ignorés.</span>
            </div>
        </form>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('admin.rooms.manage') }}" class="flex flex-wrap items-end gap-3 rounded-2xl bg-white p-4 shadow-card">
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-nuit-600">Recherche n°</span>
            <input name="q" value="{{ $filters['q'] ?? '' }}" maxlength="20" placeholder="Numéro…" class="w-32 rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-nuit-600">Catégorie</span>
            <select name="category" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="">Toutes</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected(($filters['category'] ?? null) == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-nuit-600">Étage</span>
            <select name="floor" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="">Tous</option>
                @foreach ($floors as $f)
                    <option value="{{ $f }}" @selected(($filters['floor'] ?? null) === (string) $f)>{{ $f }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-nuit-600">Statut</span>
            <select name="status" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="">Tous</option>
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['status'] ?? null) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-nuit-600">État</span>
            <select name="state" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="">Tous</option>
                <option value="active" @selected(($filters['state'] ?? null) === 'active')>Actives</option>
                <option value="inactive" @selected(($filters['state'] ?? null) === 'inactive')>Inactives</option>
            </select>
        </label>
        <button class="rounded-xl bg-nuit-900 px-4 py-2 text-sm font-semibold text-white hover:bg-nuit-800">Filtrer</button>
        @if ($hasFilter)
            <a href="{{ route('admin.rooms.manage') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">Réinitialiser</a>
        @endif
    </form>

    {{-- Liste --}}
    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="min-w-full divide-y divide-sable-200 text-sm">
            <thead class="bg-sable-50 text-left text-xs uppercase tracking-wide text-nuit-400">
                <tr>
                    <th class="px-4 py-3">Chambre</th>
                    <th class="px-4 py-3">Catégorie</th>
                    <th class="px-4 py-3">Étage / Bât.</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3">Résas</th>
                    <th class="px-4 py-3">État</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            @forelse ($rooms as $room)
                <tbody x-data="{ edit: false }" class="divide-y divide-sable-100 @if (! $room->is_active) bg-sable-50/60 @endif">
                    <tr class="align-middle">
                        <td class="px-4 py-3 font-display font-semibold text-nuit-900">{{ $room->number }}</td>
                        <td class="px-4 py-3 text-nuit-600">{{ $room->category?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-nuit-500">{{ $room->floor ?: '—' }}@if ($room->building) · {{ $room->building }}@endif</td>
                        <td class="px-4 py-3"><x-admin.badge :status="$room->status" /></td>
                        <td class="px-4 py-3 text-nuit-500">{{ $room->reservations_count }}</td>
                        <td class="px-4 py-3">
                            @if ($room->is_active)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-sable-200 px-2.5 py-0.5 text-xs font-semibold text-nuit-500">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" x-on:click="edit = !edit" class="rounded-lg border border-nuit-200 px-3 py-1.5 text-xs font-medium text-nuit-700 hover:border-terracotta-400 hover:text-terracotta-600">Modifier</button>

                                <form method="POST" action="{{ route('admin.rooms.toggle', $room) }}">
                                    @csrf
                                    <button class="rounded-lg border border-nuit-200 px-3 py-1.5 text-xs font-medium text-nuit-700 hover:border-nuit-400">
                                        {{ $room->is_active ? 'Désactiver' : 'Réactiver' }}
                                    </button>
                                </form>

                                @if (! $room->reservations_count)
                                    <form method="POST" action="{{ route('admin.rooms.destroy', $room) }}" onsubmit="return confirm('Supprimer définitivement la chambre {{ $room->number }} ?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">Supprimer</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>

                    <tr x-show="edit" x-cloak>
                        <td colspan="7" class="bg-sable-50 px-4 py-4">
                            <form method="POST" action="{{ route('admin.rooms.update', $room) }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                                @csrf @method('PUT')
                                <label class="block">
                                    <span class="mb-1 block text-xs font-medium text-nuit-600">Numéro</span>
                                    <input name="number" value="{{ $room->number }}" required maxlength="20" class="w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-xs font-medium text-nuit-600">Catégorie</span>
                                    <select name="room_category_id" required class="w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                                        @foreach ($categories as $c)
                                            <option value="{{ $c->id }}" @selected($room->room_category_id == $c->id)>{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-xs font-medium text-nuit-600">Étage</span>
                                    <input name="floor" value="{{ $room->floor }}" maxlength="10" class="w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-xs font-medium text-nuit-600">Bâtiment</span>
                                    <input name="building" value="{{ $room->building }}" maxlength="40" class="w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-xs font-medium text-nuit-600">Notes</span>
                                    <input name="notes" value="{{ $room->notes }}" maxlength="300" class="w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                                </label>
                                <div class="sm:col-span-2 lg:col-span-5">
                                    <button class="rounded-xl bg-nuit-900 px-5 py-2 text-sm font-semibold text-white hover:bg-nuit-800">Enregistrer</button>
                                    <button type="button" x-on:click="edit = false" class="ml-2 text-sm text-nuit-500 hover:text-nuit-700">Annuler</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                </tbody>
            @empty
                <tbody>
                    <tr><td colspan="7" class="px-4 py-10 text-center text-nuit-400">Aucune chambre ne correspond à ces critères.</td></tr>
                </tbody>
            @endforelse
        </table>
    </div>

    <div>{{ $rooms->links() }}</div>
</div>
@endsection
