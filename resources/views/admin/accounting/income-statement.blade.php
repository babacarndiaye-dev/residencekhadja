@extends('admin.layout')
@section('title', 'Compte de résultat')

@section('content')
<div class="mx-auto flex max-w-3xl flex-col gap-5">
    <a href="{{ route('admin.accounting.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Comptabilité</a>

    <form method="GET" class="flex flex-wrap items-end gap-3">
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Du <input type="date" name="from" value="{{ $from->toDateString() }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Au <input type="date" name="to" value="{{ $to->toDateString() }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
        <button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Appliquer</button>
        <button type="button" onclick="window.print()" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700">Imprimer</button>
    </form>

    <div class="grid gap-4 sm:grid-cols-2">
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Produits</h2>
            <table class="w-full text-sm">
                <tbody>
                    @forelse ($report['income'] as $r)
                        <tr class="border-t border-sable-100">
                            <td class="py-2"><span class="font-mono text-xs text-nuit-400">{{ $r['code'] }}</span> {{ $r['name'] }}</td>
                            <td class="py-2 text-right tabular-nums">{{ money($r['amount']) }}</td>
                        </tr>
                    @empty
                        <tr><td class="py-3 text-nuit-400">Aucun produit sur la période.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-nuit-900 font-semibold"><td class="py-2">Total produits</td><td class="py-2 text-right tabular-nums">{{ money($report['total_income']) }}</td></tr>
                </tfoot>
            </table>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Charges</h2>
            <table class="w-full text-sm">
                <tbody>
                    @forelse ($report['expense'] as $r)
                        <tr class="border-t border-sable-100">
                            <td class="py-2"><span class="font-mono text-xs text-nuit-400">{{ $r['code'] }}</span> {{ $r['name'] }}</td>
                            <td class="py-2 text-right tabular-nums">{{ money($r['amount']) }}</td>
                        </tr>
                    @empty
                        <tr><td class="py-3 text-nuit-400">Aucune charge sur la période.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-nuit-900 font-semibold"><td class="py-2">Total charges</td><td class="py-2 text-right tabular-nums">{{ money($report['total_expense']) }}</td></tr>
                </tfoot>
            </table>
        </section>
    </div>

    <div class="rounded-2xl p-5 text-white shadow-card {{ $report['result'] >= 0 ? 'bg-emerald-700' : 'bg-terracotta-700' }}">
        <div class="flex items-center justify-between">
            <span class="font-display text-lg font-semibold">{{ $report['result'] >= 0 ? 'Résultat net — bénéfice' : 'Résultat net — perte' }}</span>
            <span class="font-display text-2xl font-semibold tabular-nums">{{ money($report['result']) }}</span>
        </div>
        <p class="mt-1 text-xs text-white/70">Produits {{ money($report['total_income']) }} − Charges {{ money($report['total_expense']) }} · {{ $from->format('d/m/Y') }} → {{ $to->format('d/m/Y') }}</p>
    </div>
</div>
@endsection
