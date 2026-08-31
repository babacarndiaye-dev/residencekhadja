@extends('admin.layout')
@section('title', 'Paie')

@php $canEdit = auth()->user()->hasRole('rh'); @endphp

@section('content')
<div class="flex flex-col gap-5">

    @if ($canEdit)
        <form method="POST" action="{{ route('admin.hr.payroll.store') }}" class="flex flex-wrap items-end gap-3 rounded-2xl bg-white p-4 shadow-card">
            @csrf
            <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Nouvelle période de paie
                <input type="month" name="period" value="{{ now()->subMonthNoOverflow()->format('Y-m') }}" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            </label>
            <button class="rounded-full bg-terracotta-500 px-5 py-2 text-sm font-semibold text-white hover:bg-terracotta-600">Créer la paie</button>
        </form>
    @endif

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Période</th><th class="px-4 py-3">Bulletins</th><th class="px-4 py-3">Brut</th><th class="px-4 py-3">Retenues</th><th class="px-4 py-3">Net</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody>
                @forelse ($runs as $run)
                    <tr class="border-t border-sable-200">
                        <td class="px-4 py-3 font-medium text-nuit-900">{{ $run->periodLabel() }}</td>
                        <td class="px-4 py-3 text-xs text-nuit-500">{{ $run->payslips_count }}</td>
                        <td class="px-4 py-3">{{ money($run->gross_total) }}</td>
                        <td class="px-4 py-3">{{ money($run->deduction_total) }}</td>
                        <td class="px-4 py-3 font-semibold">{{ money($run->net_total) }}</td>
                        <td class="px-4 py-3"><x-admin.badge :status="$run->status" /></td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.hr.payroll.show', $run) }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Ouvrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-nuit-400">Aucune paie.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $runs->links() }}</div>
</div>
@endsection
