@extends('admin.layout')
@section('title', 'Planning du personnel')

@php $canEdit = auth()->user()->hasRole('rh'); @endphp

@section('content')
<div class="flex flex-col gap-5"
     x-data="{ open: false, emp: null, date: null, name: '' }">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.hr.schedule', ['week' => $monday->copy()->subWeek()->toDateString(), 'department' => $activeDepartment]) }}" class="rounded-lg border border-nuit-200 px-2.5 py-1.5 text-sm">←</a>
            <span class="text-sm font-semibold text-nuit-900">Semaine du {{ $monday->format('d/m/Y') }}</span>
            <a href="{{ route('admin.hr.schedule', ['week' => $monday->copy()->addWeek()->toDateString(), 'department' => $activeDepartment]) }}" class="rounded-lg border border-nuit-200 px-2.5 py-1.5 text-sm">→</a>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <select name="department" onchange="this.form.submit()" class="rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">
                <option value="">Tous les services</option>
                @foreach ($departments as $d)<option value="{{ $d->id }}" @selected($activeDepartment == $d->id)>{{ $d->name }}</option>@endforeach
            </select>
            <input type="hidden" name="week" value="{{ $monday->toDateString() }}">
        </form>
        @if ($canEdit)
            <form method="POST" action="{{ route('admin.hr.schedule.duplicate') }}">
                @csrf
                <input type="hidden" name="week" value="{{ $monday->toDateString() }}">
                <button class="rounded-full bg-terracotta-500 px-4 py-1.5 text-sm font-semibold text-white hover:bg-terracotta-600">Copier vers la semaine suivante</button>
            </form>
        @endif
    </div>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full min-w-[900px] text-xs">
            <thead class="bg-sable-100 text-left uppercase tracking-wider text-nuit-500">
                <tr>
                    <th class="px-3 py-2">Employé</th>
                    @foreach ($days as $day)
                        <th class="px-3 py-2 {{ $day->isToday() ? 'text-terracotta-600' : '' }}">{{ $day->locale('fr')->isoFormat('ddd D/MM') }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($employees as $emp)
                    <tr class="border-t border-sable-200">
                        <td class="px-3 py-2 font-medium text-nuit-900">{{ $emp->fullName() }}<span class="block text-xs text-nuit-400">{{ $emp->position->title ?? '' }}</span></td>
                        @foreach ($days as $day)
                            @php
                                $key = $emp->id.'|'.$day->toDateString();
                                $group = $shifts[$key] ?? collect();
                                $conflict = isset($conflicts[$key]);
                            @endphp
                            <td class="px-2 py-1.5 align-top {{ $conflict ? 'bg-terracotta-50' : '' }}">
                                @foreach ($group as $s)
                                    <div class="mb-1 flex items-center gap-1 rounded-md px-1.5 py-1 text-white" style="background: {{ $s->template->color ?? '#596d7a' }}">
                                        <span>{{ $s->start_at->format('H:i') }}–{{ $s->end_at->format('H:i') }}</span>
                                        @if ($s->status === 'swapped')<span title="Remplacé par {{ $s->replacement?->first_name }}">⇄</span>@endif
                                        @if ($canEdit)
                                            <form method="POST" action="{{ route('admin.hr.schedule.destroy', $s) }}" class="ml-auto">@csrf @method('DELETE')<button class="opacity-70 hover:opacity-100">✕</button></form>
                                        @endif
                                    </div>
                                @endforeach
                                @if ($conflict)<p class="text-xs font-semibold text-terracotta-700">conflit</p>@endif
                                @if ($canEdit)
                                    <button type="button" x-on:click="emp = {{ $emp->id }}; name = @js($emp->fullName()); date = '{{ $day->toDateString() }}'; open = true"
                                            class="mt-0.5 w-full rounded border border-dashed border-nuit-200 py-0.5 text-nuit-400 hover:border-terracotta-400 hover:text-terracotta-600">+</button>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Modale ajout de shift --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-nuit-900/40 p-4" x-on:click.self="open = false">
        <form method="POST" action="{{ route('admin.hr.schedule.store') }}" class="w-full max-w-sm rounded-2xl bg-white p-5">
            @csrf
            <input type="hidden" name="employee_id" :value="emp">
            <input type="hidden" name="work_date" :value="date">
            <h3 class="font-display text-lg font-semibold text-nuit-900">Ajouter un shift</h3>
            <p class="mt-1 text-sm text-nuit-500"><span x-text="name"></span> · <span x-text="date"></span></p>
            <div class="mt-3 flex flex-col gap-2">
                <select name="shift_template_id" x-on:change="if($event.target.selectedOptions[0].dataset.s){ $refs.start.value = $event.target.selectedOptions[0].dataset.s; $refs.end.value = $event.target.selectedOptions[0].dataset.e; $refs.brk.value = $event.target.selectedOptions[0].dataset.b; }" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    <option value="">Modèle…</option>
                    @foreach ($templates as $t)
                        <option value="{{ $t->id }}" data-s="{{ substr($t->start_time,0,5) }}" data-e="{{ substr($t->end_time,0,5) }}" data-b="{{ $t->break_minutes }}">{{ $t->name }} ({{ substr($t->start_time,0,5) }}–{{ substr($t->end_time,0,5) }})</option>
                    @endforeach
                </select>
                <div class="grid grid-cols-3 gap-2">
                    <input type="time" name="start" x-ref="start" required class="rounded-lg border border-nuit-200 px-2 py-2 text-sm">
                    <input type="time" name="end" x-ref="end" required class="rounded-lg border border-nuit-200 px-2 py-2 text-sm">
                    <input type="number" name="break_minutes" x-ref="brk" value="0" title="Pause (min)" class="rounded-lg border border-nuit-200 px-2 py-2 text-sm">
                </div>
                <input type="text" name="note" placeholder="Note" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" x-on:click="open = false" class="rounded-full border border-nuit-200 px-4 py-2 text-sm">Annuler</button>
                <button class="rounded-full bg-terracotta-500 px-4 py-2 text-sm font-semibold text-white">Ajouter</button>
            </div>
        </form>
    </div>
</div>
@endsection
