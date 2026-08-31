@extends('admin.layout')
@section('title', 'Parc piscine')

@section('content')
<div class="mx-auto flex max-w-3xl flex-col gap-4">
    <a href="{{ route('admin.pool.index') }}" class="inline-flex items-center gap-1 text-sm text-nuit-500 hover:text-terracotta-600"><x-icon name="chevron_left" size="16" /> Piscine</a>

    <form method="POST" action="{{ route('admin.pool.assets.store') }}" class="grid gap-3 rounded-lg border border-sable-200 bg-white p-4 sm:grid-cols-3 lg:grid-cols-6">
        @csrf
        <label class="text-sm lg:col-span-1">
            <span class="mb-1 block text-xs font-semibold text-nuit-600">Type</span>
            <select name="kind" class="w-full rounded-md border border-nuit-200 px-3 py-2 text-sm">
                @foreach ($kinds as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
            </select>
        </label>
        <label class="text-sm lg:col-span-2">
            <span class="mb-1 block text-xs font-semibold text-nuit-600">Nom</span>
            <input name="label" required maxlength="60" placeholder="Ex. Cabana 4" class="w-full rounded-md border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <label class="text-sm">
            <span class="mb-1 block text-xs font-semibold text-nuit-600">Capacité</span>
            <input type="number" name="capacity" value="1" min="1" max="12" class="w-full rounded-md border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <label class="text-sm">
            <span class="mb-1 block text-xs font-semibold text-nuit-600">½ journée</span>
            <input type="number" name="half_day_price" value="0" min="0" class="w-full rounded-md border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <label class="text-sm">
            <span class="mb-1 block text-xs font-semibold text-nuit-600">Journée</span>
            <input type="number" name="full_day_price" value="0" min="0" class="w-full rounded-md border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <div class="sm:col-span-3 lg:col-span-6">
            <button class="rounded-md bg-nuit-900 px-5 py-2 text-sm font-semibold text-white hover:bg-nuit-800">Ajouter</button>
        </div>
    </form>

    <div class="overflow-x-auto rounded-lg border border-sable-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-sable-50 text-left text-xs uppercase tracking-wider text-nuit-400">
                <tr><th class="px-4 py-3">Emplacement</th><th class="px-4 py-3">Capacité</th><th class="px-4 py-3">Tarifs</th><th class="px-4 py-3">Réservations</th><th class="px-4 py-3">État</th><th></th></tr>
            </thead>
            <tbody>
                @foreach ($assets as $a)
                    <tr class="border-t border-sable-100">
                        <td class="px-4 py-3"><span class="font-medium text-nuit-900">{{ $a->label }}</span> <span class="text-xs text-nuit-400">· {{ $a->kindLabel() }}</span></td>
                        <td class="px-4 py-3">{{ $a->capacity }} pers.</td>
                        <td class="px-4 py-3 text-xs text-nuit-500">{{ money($a->half_day_price) }} / {{ money($a->full_day_price) }}</td>
                        <td class="px-4 py-3 text-nuit-500">{{ $a->reservations_count }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $a->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-sable-200 text-nuit-500' }}">{{ $a->is_active ? 'En service' : 'Retiré' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.pool.assets.toggle', $a) }}">
                                @csrf
                                <button class="text-xs font-semibold text-nuit-500 hover:text-terracotta-600">{{ $a->is_active ? 'Retirer' : 'Remettre' }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
