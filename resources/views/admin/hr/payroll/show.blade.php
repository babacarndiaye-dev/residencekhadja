@extends('admin.layout')
@section('title', 'Paie '.$run->periodLabel())

@php
    $u = auth()->user();
    $canEdit = $u->hasRole('rh') && $run->status === 'draft';
    $canApprove = $u->hasRole('direction') && $run->status === 'draft';
    $canPay = $u->hasRole('rh') && $run->status === 'approved';
@endphp

@section('content')
<div class="flex flex-col gap-6">
    <a href="{{ route('admin.hr.payroll.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Paie</a>

    <div class="flex flex-wrap items-center gap-3">
        <h2 class="font-display text-2xl font-semibold text-nuit-900">{{ $run->periodLabel() }}</h2>
        <x-admin.badge :status="$run->status" />
        <span class="text-sm text-nuit-500">{{ $run->payslips->count() }} bulletins · Brut {{ money($run->gross_total) }} · Net {{ money($run->net_total) }}</span>
    </div>

    <div class="flex flex-wrap gap-2">
        @if ($canEdit)
            <form method="POST" action="{{ route('admin.hr.payroll.generate', $run) }}">@csrf<button class="rounded-full bg-terracotta-500 px-4 py-2 text-sm font-semibold text-white hover:bg-terracotta-600">{{ $run->payslips->count() ? 'Régénérer les bulletins' : 'Générer les bulletins' }}</button></form>
        @endif
        @if ($canApprove && $run->payslips->count())
            <form method="POST" action="{{ route('admin.hr.payroll.approve', $run) }}">@csrf<button class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Approuver</button></form>
        @endif
        @if ($canPay)
            <form method="POST" action="{{ route('admin.hr.payroll.pay', $run) }}" onsubmit="return confirm('Marquer la paie payée ? Trésorerie et comptabilité seront mises à jour.')">@csrf<button class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Marquer payée</button></form>
        @endif
    </div>

    @if ($canEdit)
        <details class="rounded-2xl bg-white p-4 shadow-card">
            <summary class="cursor-pointer text-sm font-semibold text-nuit-900">+ Élément variable (prime / retenue)</summary>
            <form method="POST" action="{{ route('admin.hr.payroll.adjustment', $run) }}" class="mt-3 grid gap-2 sm:grid-cols-5">
                @csrf
                <select name="employee_id" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm sm:col-span-2">
                    @foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->fullName() }}</option>@endforeach
                </select>
                <select name="kind" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    <option value="earning">Prime</option><option value="deduction">Retenue</option>
                </select>
                <input type="text" name="label" placeholder="Libellé" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <div class="flex gap-1">
                    <input type="number" name="amount" placeholder="Montant" required class="flex-1 rounded-lg border border-nuit-200 px-2 py-2 text-sm">
                    <button class="rounded-full bg-nuit-900 px-3 py-2 text-xs font-semibold text-white">+</button>
                </div>
            </form>
            @if ($run->adjustments->isNotEmpty())
                <ul class="mt-3 flex flex-col gap-1 text-sm">
                    @foreach ($run->adjustments as $adj)
                        <li class="flex items-center justify-between border-t border-sable-100 py-1">
                            <span>{{ $adj->employee->fullName() }} — {{ $adj->label }} ({{ $adj->kind === 'earning' ? '+' : '−' }}{{ money($adj->amount) }})</span>
                            <form method="POST" action="{{ route('admin.hr.payroll.adjustment.destroy', [$run, $adj]) }}">@csrf @method('DELETE')<button class="text-xs text-red-600">retirer</button></form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </details>
    @endif

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Employé</th><th class="px-4 py-3">Service</th><th class="px-4 py-3">J. travaillés</th><th class="px-4 py-3">HS</th><th class="px-4 py-3">Brut</th><th class="px-4 py-3">Retenues</th><th class="px-4 py-3">Net</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody>
                @forelse ($run->payslips->sortBy(fn($p) => $p->employee->last_name) as $p)
                    <tr class="border-t border-sable-200">
                        <td class="px-4 py-2.5 font-medium text-nuit-900">{{ $p->employee->fullName() }}</td>
                        <td class="px-4 py-2.5 text-xs text-nuit-500">{{ $p->employee->department->name ?? '—' }}</td>
                        <td class="px-4 py-2.5">{{ $p->worked_days }}</td>
                        <td class="px-4 py-2.5">{{ $p->overtime_hours }} h</td>
                        <td class="px-4 py-2.5">{{ money($p->gross) }}</td>
                        <td class="px-4 py-2.5">{{ money($p->total_deductions) }}</td>
                        <td class="px-4 py-2.5 font-semibold">{{ money($p->net) }}</td>
                        <td class="px-4 py-2.5 text-right"><a href="{{ route('admin.hr.payroll.payslip', [$run, $p]) }}" target="_blank" class="text-xs font-semibold text-terracotta-600 hover:underline">Bulletin ↗</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-nuit-400">Aucun bulletin. Cliquez sur « Générer les bulletins ».</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
