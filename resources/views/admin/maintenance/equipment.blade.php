@extends('admin.layout')
@section('title', 'Équipements')

@section('content')
<div class="flex flex-col gap-6">
    <a href="{{ route('admin.maintenance.tickets') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Maintenance</a>

    <details class="rounded-2xl bg-white p-4 shadow-card">
        <summary class="cursor-pointer text-sm font-semibold text-nuit-900">+ Ajouter un équipement</summary>
        <form method="POST" action="{{ route('admin.maintenance.equipment.store') }}" class="mt-3 grid gap-3 sm:grid-cols-3">
            @csrf
            <input type="text" name="name" placeholder="Nom" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <select name="category" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                @foreach ($categories as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
            </select>
            <input type="text" name="location" placeholder="Emplacement" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <input type="text" name="brand" placeholder="Marque" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <input type="text" name="model" placeholder="Modèle" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <select name="status" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                @foreach ($statuses as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
            </select>
            <div class="sm:col-span-3"><button class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Ajouter</button></div>
        </form>
    </details>

    @foreach ($equipment as $category => $items)
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">{{ $categories[$category] ?? $category }}</h2>
            <div class="flex flex-col divide-y divide-sable-100">
                @foreach ($items as $e)
                    <form method="POST" action="{{ route('admin.maintenance.equipment.update', $e) }}" class="grid items-center gap-2 py-2.5 sm:grid-cols-[1.4fr_1fr_1fr_auto_auto]">
                        @csrf @method('PUT')
                        <input type="hidden" name="category" value="{{ $e->category }}">
                        <input type="text" name="name" value="{{ $e->name }}" class="rounded-lg border border-nuit-200 px-3 py-1.5 text-sm font-medium">
                        <input type="text" name="location" value="{{ $e->location }}" placeholder="Emplacement" class="rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">
                        <select name="status" class="rounded-lg border border-nuit-200 px-2 py-1.5 text-sm">
                            @foreach ($statuses as $k => $label)<option value="{{ $k }}" @selected($e->status === $k)>{{ $label }}</option>@endforeach
                        </select>
                        <span class="text-xs text-nuit-400">{{ $e->open_tickets_count }} ticket(s)</span>
                        <button class="rounded-full bg-nuit-900 px-3 py-1.5 text-xs font-semibold text-white">OK</button>
                    </form>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
@endsection
