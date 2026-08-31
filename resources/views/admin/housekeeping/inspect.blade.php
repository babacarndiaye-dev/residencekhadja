@extends('admin.layout')
@section('title', 'Contrôle — Chambre '.$task->room->number)

@section('content')
<div class="mx-auto max-w-2xl">
    <a href="{{ route('admin.housekeeping.index', ['date' => $task->service_date->toDateString()]) }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Plan de ménage</a>

    <div class="mt-4 flex items-center gap-3">
        <h2 class="font-display text-2xl font-semibold text-nuit-900">Chambre {{ $task->room->number }}</h2>
        <x-admin.badge :status="$task->status" />
    </div>
    <p class="mt-1 text-sm text-nuit-500">{{ $task->typeLabel() }} · {{ $task->assignee->name ?? 'non affecté' }} · seuil de validation {{ config('housekeeping.qc_pass_score') }}%</p>

    <form method="POST" action="{{ route('admin.housekeeping.inspect.store', $task) }}" class="mt-5 flex flex-col gap-2">
        @csrf
        @foreach ($task->checks as $check)
            <div class="flex flex-wrap items-center gap-3 rounded-xl bg-white p-3 shadow-card">
                <label class="flex flex-1 items-center gap-2 text-sm text-nuit-800">
                    <input type="checkbox" name="checks[{{ $check->id }}][passed]" value="1" @checked($check->passed)
                           class="h-5 w-5 rounded border-nuit-300 text-terracotta-500 focus:ring-terracotta-500">
                    {{ $check->label }}
                </label>
                <input type="text" name="checks[{{ $check->id }}][comment]" value="{{ $check->comment }}" placeholder="Remarque"
                       class="w-full rounded-lg border border-nuit-200 px-3 py-1.5 text-xs sm:w-56">
            </div>
        @endforeach

        <div class="mt-3 flex items-center justify-between">
            <p class="text-xs text-nuit-400">Une chambre sous le seuil repart automatiquement en nettoyage.</p>
            <button class="rounded-full bg-terracotta-500 px-6 py-2.5 text-sm font-semibold text-white hover:bg-terracotta-600">Valider le contrôle</button>
        </div>
    </form>
</div>
@endsection
