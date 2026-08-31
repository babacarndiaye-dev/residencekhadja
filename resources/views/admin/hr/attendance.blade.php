@extends('admin.layout')
@section('title', 'Pointage')

@php $canEdit = auth()->user()->hasRole('rh'); @endphp

@section('content')
<div class="flex flex-col gap-5">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2">
            <a href="{{ route('admin.hr.attendance', ['date' => $date->copy()->subDay()->toDateString()]) }}" class="rounded-lg border border-nuit-200 px-2.5 py-1.5 text-sm">←</a>
            <input type="date" name="date" value="{{ $date->toDateString() }}" onchange="this.form.submit()" class="rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">
            <a href="{{ route('admin.hr.attendance', ['date' => $date->copy()->addDay()->toDateString()]) }}" class="rounded-lg border border-nuit-200 px-2.5 py-1.5 text-sm">→</a>
        </form>
        <p class="text-sm text-nuit-500">{{ $summary['present'] }} présents · {{ $summary['absent'] }} absents · {{ $summary['late'] }} en retard · {{ $summary['planned'] }} planifiés</p>
        <a href="{{ route('clock.show') }}" target="_blank" class="rounded-full bg-nuit-900 px-4 py-1.5 text-sm font-semibold text-white">Borne ↗</a>
    </div>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Employé</th><th class="px-4 py-3">Shift prévu</th><th class="px-4 py-3">Entrée</th><th class="px-4 py-3">Sortie</th><th class="px-4 py-3">Travaillé</th><th class="px-4 py-3">Retard / HS</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody>
                @forelse ($employees as $e)
                    @php $log = $logs[$e->id] ?? null; $shift = $shifts[$e->id] ?? null; @endphp
                    <tr class="border-t border-sable-200 {{ ($log && $log->status === 'absent') ? 'bg-red-50/40' : '' }}">
                        <td class="px-4 py-3 font-medium text-nuit-900">{{ $e->fullName() }}<span class="block text-xs text-nuit-400">{{ $e->matricule }}</span></td>
                        <td class="px-4 py-3 text-xs text-nuit-500">{{ $shift ? $shift->start_at->format('H:i').'–'.$shift->end_at->format('H:i') : '—' }}</td>
                        <td class="px-4 py-3">{{ $log && $log->clock_in ? $log->clock_in->format('H:i') : ($log && $log->status === 'absent' ? 'Absent' : '—') }}</td>
                        <td class="px-4 py-3">{{ $log && $log->clock_out ? $log->clock_out->format('H:i') : '—' }}</td>
                        <td class="px-4 py-3">{{ $log ? $log->workedHours().' h' : '—' }}</td>
                        <td class="px-4 py-3 text-xs">
                            @if ($log && $log->late_minutes)<span class="text-terracotta-700">{{ $log->late_minutes }} min retard</span>@endif
                            @if ($log && $log->overtime_minutes)<span class="text-emerald-700"> +{{ $log->overtimeHours() }} h</span>@endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($canEdit)
                                <div class="flex justify-end gap-1.5">
                                    @if (! $log || (! $log->clock_out && $log->status !== 'absent'))
                                        <form method="POST" action="{{ route('admin.hr.attendance.clock', $e) }}">@csrf<button class="rounded-full border border-nuit-200 px-2.5 py-1 text-xs text-nuit-600 hover:border-terracotta-400">{{ $log && $log->clock_in ? 'Sortie' : 'Entrée' }}</button></form>
                                    @endif
                                    @if (! $log || $log->status !== 'absent')
                                        <form method="POST" action="{{ route('admin.hr.attendance.absent', $e) }}">@csrf<input type="hidden" name="date" value="{{ $date->toDateString() }}"><button class="rounded-full border border-red-200 px-2.5 py-1 text-xs text-red-600 hover:bg-red-50">Absent</button></form>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-nuit-400">Aucun shift planifié ni pointage pour ce jour.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
