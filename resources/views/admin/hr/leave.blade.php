@extends('admin.layout')
@section('title', 'Congés & absences')

@php
    $u = auth()->user();
    $canRequest = $u->hasRole('rh');
    $canDecide = $u->hasRole('direction');
@endphp

@section('content')
<div class="flex flex-col gap-5">
    <a href="{{ route('admin.hr.dashboard') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← RH</a>

    @if ($canRequest)
        <form method="POST" action="{{ route('admin.hr.leave.store') }}" class="grid gap-3 rounded-2xl bg-white p-5 shadow-card sm:grid-cols-5">
            @csrf
            <h2 class="font-display text-lg font-semibold text-nuit-900 sm:col-span-5">Enregistrer une demande</h2>
            <select name="employee_id" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm sm:col-span-2">
                <option value="">Employé…</option>
                @foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->fullName() }}</option>@endforeach
            </select>
            <select name="type" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                @foreach ($types as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
            </select>
            <input type="date" name="start_date" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <input type="date" name="end_date" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <input type="text" name="reason" placeholder="Motif" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm sm:col-span-4">
            <button class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Enregistrer</button>
        </form>
    @endif

    <div class="flex flex-wrap gap-2">
        @foreach (['pending' => 'En attente', 'approved' => 'Approuvés', 'rejected' => 'Refusés', 'all' => 'Tous'] as $k => $label)
            <a href="{{ route('admin.hr.leave.index', ['status' => $k]) }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-nuit-900 text-white' => $status === $k, 'bg-white text-nuit-700 shadow-card' => $status !== $k])>{{ $label }}</a>
        @endforeach
    </div>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Employé</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Période</th><th class="px-4 py-3">Jours</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody>
                @forelse ($leaves as $l)
                    <tr class="border-t border-sable-200">
                        <td class="px-4 py-3 font-medium text-nuit-900">{{ $l->employee->fullName() }}</td>
                        <td class="px-4 py-3 text-xs">{{ $l->typeLabel() }}{{ $l->isPaid() ? '' : ' (sans solde)' }}</td>
                        <td class="px-4 py-3 text-xs text-nuit-500">{{ $l->start_date->format('d/m/Y') }} → {{ $l->end_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $l->days }}</td>
                        <td class="px-4 py-3"><x-admin.badge :status="$l->status" /></td>
                        <td class="px-4 py-3 text-right">
                            @if ($l->status === 'pending')
                                <div class="flex justify-end gap-1.5">
                                    @if ($canDecide)
                                        <form method="POST" action="{{ route('admin.hr.leave.approve', $l) }}">@csrf<button class="rounded-full bg-emerald-600 px-3 py-1 text-xs font-semibold text-white">Approuver</button></form>
                                    @endif
                                    @if ($canRequest)
                                        <form method="POST" action="{{ route('admin.hr.leave.reject', $l) }}">@csrf<button class="rounded-full border border-red-200 px-3 py-1 text-xs font-semibold text-red-600">Refuser</button></form>
                                    @endif
                                </div>
                            @elseif ($l->status === 'approved' && $canRequest)
                                <form method="POST" action="{{ route('admin.hr.leave.cancel', $l) }}">@csrf<button class="text-xs text-nuit-500 hover:text-terracotta-600">Annuler</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-nuit-400">Aucune demande.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $leaves->links() }}</div>
</div>
@endsection
