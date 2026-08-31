@extends('admin.layout')
@section('title', 'Ressources humaines')

@section('content')
<div class="flex flex-col gap-6">

    <div class="grid gap-4 sm:grid-cols-4">
        <a href="{{ route('admin.hr.employees.index') }}" class="rounded-2xl bg-white p-5 shadow-card hover:shadow-luxe">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Effectif</p>
            <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ $headcount }}</p>
        </a>
        <a href="{{ route('admin.hr.leave.index') }}" class="rounded-2xl bg-white p-5 shadow-card hover:shadow-luxe">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Congés à traiter</p>
            <p class="mt-1 font-display text-2xl font-semibold {{ $pendingLeave->count() ? 'text-terracotta-600' : 'text-nuit-900' }}">{{ $pendingLeave->count() }}</p>
        </a>
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Alertes RH</p>
            <p class="mt-1 font-display text-2xl font-semibold {{ $alerts->where('level', 'critical')->count() ? 'text-terracotta-600' : ($alerts->count() ? 'text-laiton-600' : 'text-nuit-900') }}">{{ $alerts->count() }}</p>
            <p class="mt-1 text-xs text-nuit-400">{{ $alerts->where('level', 'critical')->count() }} urgente(s)</p>
        </div>
        <a href="{{ $lastRun ? route('admin.hr.payroll.show', $lastRun) : route('admin.hr.payroll.index') }}" class="rounded-2xl bg-white p-5 shadow-card hover:shadow-luxe">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Dernière paie</p>
            <p class="mt-1 font-display text-lg font-semibold text-nuit-900">{{ $lastRun?->periodLabel() ?? '—' }}</p>
            <p class="mt-1 text-xs text-nuit-400">{{ $lastRun?->statusLabel() }}</p>
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Effectif par service</h2>
            <ul class="flex flex-col gap-1.5 text-sm">
                @foreach ($byDepartment as $row)
                    <li class="flex justify-between border-b border-sable-100 pb-1">
                        <span>{{ $row->department->name ?? 'Non affecté' }}</span>
                        <span class="font-semibold text-nuit-900">{{ $row->t }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Congés en attente</h2>
            <ul class="flex flex-col gap-1.5 text-sm">
                @forelse ($pendingLeave as $l)
                    <li class="flex items-center justify-between">
                        <a href="{{ route('admin.hr.leave.index') }}" class="hover:text-terracotta-600">{{ $l->employee->fullName() }} — {{ $l->typeLabel() }}</a>
                        <span class="text-xs text-nuit-400">{{ $l->start_date->format('d/m') }} → {{ $l->end_date->format('d/m') }}</span>
                    </li>
                @empty
                    <li class="text-nuit-400">Rien à traiter.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-card lg:col-span-2">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Alertes RH</h2>
            <ul class="flex flex-col divide-y divide-sable-100 text-sm">
                @forelse ($alerts as $a)
                    <li class="flex items-center justify-between gap-3 py-2">
                        <span class="flex items-center gap-2">
                            <span @class([
                                'inline-block h-2 w-2 rounded-full',
                                'bg-terracotta-500' => $a['level'] === 'critical',
                                'bg-laiton-500' => $a['level'] === 'warning',
                                'bg-nuit-300' => $a['level'] === 'info',
                            ])></span>
                            <a href="{{ url($a['url']) }}" class="hover:text-terracotta-600">{{ $a['label'] }}</a>
                        </span>
                        @if ($a['date'])<span class="shrink-0 text-xs {{ $a['level'] === 'critical' ? 'text-terracotta-700' : 'text-nuit-400' }}">{{ \Illuminate\Support\Carbon::parse($a['date'])->format('d/m/Y') }}</span>@endif
                    </li>
                @empty
                    <li class="py-4 text-center text-nuit-400">Aucune alerte — tout est à jour.</li>
                @endforelse
            </ul>
        </section>
    </div>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('admin.hr.employees.index') }}" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Employés</a>
        <a href="{{ route('admin.hr.departments') }}" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Services & fonctions</a>
        <a href="{{ route('admin.hr.schedule') }}" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Planning</a>
        <a href="{{ route('admin.hr.attendance') }}" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Pointage</a>
        <a href="{{ route('admin.hr.trainings') }}" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Formations</a>
        <a href="{{ route('admin.hr.payroll.index') }}" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Paie</a>
        <a href="{{ route('admin.hr.badges') }}" target="_blank" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Badges QR ↗</a>
        <a href="{{ route('clock.show') }}" target="_blank" class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Ouvrir la borne de pointage ↗</a>
    </div>
</div>
@endsection
