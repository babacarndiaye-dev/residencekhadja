@extends('admin.layout')
@section('title', 'Balance générale')

@section('content')
<div class="flex flex-col gap-5">
    <a href="{{ route('admin.accounting.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Comptabilité</a>

    <form method="GET" class="flex flex-wrap items-end gap-3">
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Du <input type="date" name="from" value="{{ $from->toDateString() }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Au <input type="date" name="to" value="{{ $to->toDateString() }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
        <button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Appliquer</button>
        <a href="{{ route('admin.accounting.export', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700">Export CSV</a>
    </form>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Compte</th><th class="px-4 py-3">Intitulé</th><th class="px-4 py-3 text-right">Débit</th><th class="px-4 py-3 text-right">Crédit</th><th class="px-4 py-3 text-right">Solde</th></tr>
            </thead>
            <tbody>
                @foreach ($rows as $r)
                    <tr class="border-t border-sable-200 {{ ($r['debit'] == 0 && $r['credit'] == 0) ? 'text-nuit-300' : '' }}">
                        <td class="px-4 py-2 font-mono text-xs">
                            <a href="{{ route('admin.accounting.ledger', ['account' => \App\Models\LedgerAccount::where('code', $r['code'])->value('id'), 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="hover:text-terracotta-600">{{ $r['code'] }}</a>
                        </td>
                        <td class="px-4 py-2">{{ $r['name'] }}</td>
                        <td class="px-4 py-2 text-right">{{ $r['debit'] ? money($r['debit']) : '—' }}</td>
                        <td class="px-4 py-2 text-right">{{ $r['credit'] ? money($r['credit']) : '—' }}</td>
                        <td class="px-4 py-2 text-right font-semibold {{ $r['balance'] < 0 ? 'text-terracotta-700' : 'text-nuit-900' }}">{{ money($r['balance']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-nuit-900 font-semibold text-nuit-900">
                    <td class="px-4 py-3" colspan="2">Totaux</td>
                    <td class="px-4 py-3 text-right">{{ money($totalDebit) }}</td>
                    <td class="px-4 py-3 text-right">{{ money($totalCredit) }}</td>
                    <td class="px-4 py-3 text-right {{ $totalDebit === $totalCredit ? 'text-emerald-700' : 'text-terracotta-700' }}">{{ $totalDebit === $totalCredit ? 'équilibrée' : money($totalDebit - $totalCredit) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
