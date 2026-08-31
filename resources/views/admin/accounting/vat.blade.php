@extends('admin.layout')
@section('title', 'Déclaration de TVA')

@section('content')
<div class="mx-auto flex max-w-xl flex-col gap-5">
    <a href="{{ route('admin.accounting.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Comptabilité</a>

    <form method="GET" class="flex flex-wrap items-end gap-3">
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Du <input type="date" name="from" value="{{ $from->toDateString() }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Au <input type="date" name="to" value="{{ $to->toDateString() }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
        <button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Appliquer</button>
        <button type="button" onclick="window.print()" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700">Imprimer</button>
    </form>

    <div class="rounded-2xl bg-white p-6 shadow-card">
        <table class="w-full text-sm">
            <tbody>
                <tr class="border-b border-sable-100">
                    <td class="py-3">TVA collectée <span class="text-xs text-nuit-400">(compte {{ config('accounting.posting.accounts.vat_collected') }})</span></td>
                    <td class="py-3 text-right font-semibold tabular-nums">{{ money($report['collected']) }}</td>
                </tr>
                <tr class="border-b border-sable-100">
                    <td class="py-3">TVA déductible <span class="text-xs text-nuit-400">(compte {{ config('accounting.posting.accounts.vat_deductible') }})</span></td>
                    <td class="py-3 text-right font-semibold tabular-nums">− {{ money($report['deductible']) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="mt-4 rounded-xl p-4 {{ $report['due'] > 0 ? 'bg-terracotta-50' : 'bg-emerald-50' }}">
            @if ($report['due'] > 0)
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-terracotta-800">TVA à décaisser</span>
                    <span class="font-display text-xl font-semibold text-terracotta-800 tabular-nums">{{ money($report['due']) }}</span>
                </div>
            @else
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-emerald-800">Crédit de TVA reportable</span>
                    <span class="font-display text-xl font-semibold text-emerald-800 tabular-nums">{{ money($report['credit']) }}</span>
                </div>
            @endif
        </div>
        <p class="mt-3 text-xs text-nuit-400">Période du {{ $from->format('d/m/Y') }} au {{ $to->format('d/m/Y') }}. Récapitulatif indicatif — la liquidation officielle relève du régime fiscal en vigueur.</p>
    </div>
</div>
@endsection
