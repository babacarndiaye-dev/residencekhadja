@extends('admin.layout')
@section('title', 'Employés')

@php $canEdit = auth()->user()->hasRole('rh'); @endphp

@section('content')
<div class="flex flex-col gap-5">
    <form method="GET" class="flex flex-wrap items-end gap-3 rounded-2xl bg-white p-4 shadow-card">
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Recherche
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom, matricule" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Service
            <select name="department" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="">Tous</option>
                @foreach ($departments as $d)<option value="{{ $d->id }}" @selected(($filters['department'] ?? '') == $d->id)>{{ $d->name }}</option>@endforeach
            </select>
        </label>
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Statut
            <select name="status" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="">Tous</option>
                @foreach ($statuses as $k => $label)<option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $label }}</option>@endforeach
            </select>
        </label>
        <button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Filtrer</button>
        @if ($canEdit)<a href="{{ route('admin.hr.employees.create') }}" class="ml-auto rounded-full bg-terracotta-500 px-4 py-2 text-sm font-semibold text-white hover:bg-terracotta-600">+ Employé</a>@endif
    </form>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Matricule</th><th class="px-4 py-3">Nom</th><th class="px-4 py-3">Service</th><th class="px-4 py-3">Fonction</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody>
                @forelse ($employees as $e)
                    <tr class="border-t border-sable-200 hover:bg-sable-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $e->matricule }}</td>
                        <td class="px-4 py-3 font-medium text-nuit-900">{{ $e->civility }} {{ $e->fullName() }}</td>
                        <td class="px-4 py-3 text-xs text-nuit-500">{{ $e->department->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-nuit-500">{{ $e->position->title ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <x-admin.badge :status="$e->employment_status" />
                            @unless ($e->tracks_attendance)<span class="ml-1 rounded-full bg-sable-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-nuit-500" title="Non soumis au pointage">hors pointage</span>@endunless
                        </td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.hr.employees.show', $e) }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Fiche</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-nuit-400">Aucun employé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $employees->links() }}</div>
</div>
@endsection
