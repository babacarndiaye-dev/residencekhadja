@extends('admin.layout')
@section('title', 'Formations')

@php $canEdit = auth()->user()->hasRole('rh'); @endphp

@section('content')
<div class="flex flex-col gap-5">
    <a href="{{ route('admin.hr.dashboard') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← RH</a>

    @if ($canEdit)
        <form method="POST" action="{{ route('admin.hr.trainings.store') }}" class="grid gap-3 rounded-2xl bg-white p-5 shadow-card sm:grid-cols-2">
            @csrf
            <h2 class="font-display text-lg font-semibold text-nuit-900 sm:col-span-2">Nouvelle formation</h2>
            <input type="text" name="title" placeholder="Intitulé" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <input type="text" name="provider" placeholder="Organisme" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <input type="date" name="starts_on" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <input type="date" name="ends_on" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <input type="number" name="cost" placeholder="Coût (FCFA)" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <select name="employees[]" multiple size="4" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm sm:col-span-2">
                @foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->fullName() }}</option>@endforeach
            </select>
            <button class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white sm:col-span-2">Enregistrer</button>
        </form>
    @endif

    <div class="flex flex-col gap-3">
        @forelse ($trainings as $t)
            <div class="rounded-2xl bg-white p-4 shadow-card">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="font-medium text-nuit-900">{{ $t->title }}</span>
                    <span class="text-xs text-nuit-500">{{ $t->provider }} · {{ optional($t->starts_on)->format('d/m/Y') }} · {{ money($t->cost) }}</span>
                    <span class="rounded-full bg-sable-100 px-2.5 py-0.5 text-xs">{{ ['planned' => 'Planifiée', 'done' => 'Réalisée', 'cancelled' => 'Annulée'][$t->status] }}</span>
                </div>
                <p class="mt-1 text-xs text-nuit-400">{{ $t->participants->pluck('first_name')->join(', ') ?: 'Aucun participant' }}</p>
            </div>
        @empty
            <p class="rounded-2xl bg-white p-8 text-center text-nuit-400 shadow-card">Aucune formation.</p>
        @endforelse
    </div>
</div>
@endsection
