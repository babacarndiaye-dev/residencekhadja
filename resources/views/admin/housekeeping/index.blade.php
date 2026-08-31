@extends('admin.layout')
@section('title', 'Ménage')

@php $canEdit = auth()->user()->hasRole('housekeeping'); @endphp

@section('content')
<div class="flex flex-col gap-5">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2">
            <a href="{{ route('admin.housekeeping.index', ['date' => $date->copy()->subDay()->toDateString()]) }}" class="rounded-lg border border-nuit-200 px-2.5 py-1.5 text-sm">←</a>
            <input type="date" name="date" value="{{ $date->toDateString() }}" onchange="this.form.submit()" class="rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">
            <a href="{{ route('admin.housekeeping.index', ['date' => $date->copy()->addDay()->toDateString()]) }}" class="rounded-lg border border-nuit-200 px-2.5 py-1.5 text-sm">→</a>
        </form>
        <div class="flex items-center gap-3">
            <span class="text-sm text-nuit-500">{{ $summary['done'] }} / {{ $summary['total'] }} faites · {{ $summary['unassigned'] }} non affectées</span>
            @if ($canEdit)
                <form method="POST" action="{{ route('admin.housekeeping.generate') }}">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                    <button class="rounded-full bg-terracotta-500 px-4 py-2 text-sm font-semibold text-white hover:bg-terracotta-600">Générer le plan</button>
                </form>
            @endif
        </div>
    </div>

    <a href="{{ route('admin.housekeeping.incidents') }}" class="text-sm font-semibold text-terracotta-600 hover:underline">Incidents & objets trouvés →</a>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Chambre</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Agent</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3">QC</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody>
                @forelse ($tasks as $task)
                    <tr class="border-t border-sable-200">
                        <td class="px-4 py-3">
                            <span class="font-semibold text-nuit-900">Ch. {{ $task->room->number }}</span>
                            <span class="block text-xs text-nuit-400">{{ $task->room->category->name }} · {{ $task->room->statusLabel() }}</span>
                        </td>
                        <td class="px-4 py-3">
                            {{ $task->typeLabel() }}
                            @if ($task->priority === 'high')<span class="ml-1 text-xs font-semibold text-terracotta-600">prioritaire</span>@endif
                            @if ($task->reservation)<span class="block text-xs text-nuit-400">{{ $task->reservation->guest->last_name ?? '' }}</span>@endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($canEdit)
                                <form method="POST" action="{{ route('admin.housekeeping.assign', $task) }}">
                                    @csrf
                                    <select name="assigned_to" onchange="this.form.submit()" class="rounded-lg border border-nuit-200 px-2 py-1.5 text-xs">
                                        <option value="">— non affecté —</option>
                                        @foreach ($agents as $agent)
                                            <option value="{{ $agent->id }}" @selected($task->assigned_to == $agent->id)>{{ $agent->name }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            @else
                                {{ $task->assignee->name ?? '—' }}
                            @endif
                        </td>
                        <td class="px-4 py-3"><x-admin.badge :status="$task->status" /></td>
                        <td class="px-4 py-3 text-xs text-nuit-500">{{ $task->qc_score !== null ? $task->qc_score.'%' : '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($canEdit)
                                <div class="flex justify-end gap-1.5">
                                    @foreach (['in_progress' => 'Démarrer', 'done' => 'Terminer', 'blocked' => 'Bloquer'] as $s => $label)
                                        @if ($task->status !== $s && $task->status !== 'inspected')
                                            <form method="POST" action="{{ route('admin.housekeeping.status', $task) }}">
                                                @csrf
                                                <input type="hidden" name="status" value="{{ $s }}">
                                                <button class="rounded-full border border-nuit-200 px-2.5 py-1 text-xs text-nuit-600 hover:border-terracotta-400">{{ $label }}</button>
                                            </form>
                                        @endif
                                    @endforeach
                                    @if (in_array($task->status, ['done', 'pending'], true))
                                        <a href="{{ route('admin.housekeeping.inspect', $task) }}" class="rounded-full bg-nuit-900 px-2.5 py-1 text-xs font-semibold text-white">Contrôler</a>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-nuit-400">
                        Aucune tâche pour cette date. Cliquez sur « Générer le plan ».
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
