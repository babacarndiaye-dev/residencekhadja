@extends('admin.layout')
@section('title', 'Bilan')

@section('content')
<div class="mx-auto flex max-w-3xl flex-col gap-5">
    <a href="{{ route('admin.accounting.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Comptabilité</a>

    <form method="GET" class="flex flex-wrap items-end gap-3">
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Au <input type="date" name="as_of" value="{{ $asOf->toDateString() }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
        <button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Appliquer</button>
        <button type="button" onclick="window.print()" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700">Imprimer</button>
    </form>

    <div class="grid gap-4 sm:grid-cols-2">
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Actif</h2>
            <table class="w-full text-sm">
                <tbody>
                    @foreach ($sheet['assets'] as $r)
                        <tr class="border-t border-sable-100">
                            <td class="py-2"><span class="font-mono text-xs text-nuit-400">{{ $r['code'] }}</span> {{ $r['name'] }}</td>
                            <td class="py-2 text-right tabular-nums">{{ money($r['amount']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-nuit-900 font-semibold"><td class="py-2">Total actif</td><td class="py-2 text-right tabular-nums">{{ money($sheet['total_assets']) }}</td></tr>
                </tfoot>
            </table>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Passif</h2>
            <table class="w-full text-sm">
                <tbody>
                    @foreach ($sheet['liabilities'] as $r)
                        <tr class="border-t border-sable-100">
                            <td class="py-2"><span class="font-mono text-xs text-nuit-400">{{ $r['code'] }}</span> {{ $r['name'] }}</td>
                            <td class="py-2 text-right tabular-nums">{{ money($r['amount']) }}</td>
                        </tr>
                    @endforeach
                    @if ($sheet['pending_result'] !== 0)
                        <tr class="border-t border-sable-100 text-nuit-500">
                            <td class="py-2 italic">Résultat de l’exercice (non affecté)</td>
                            <td class="py-2 text-right tabular-nums">{{ money($sheet['pending_result']) }}</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-nuit-900 font-semibold"><td class="py-2">Total passif</td><td class="py-2 text-right tabular-nums">{{ money($sheet['total_liabilities']) }}</td></tr>
                </tfoot>
            </table>
        </section>
    </div>

    <div class="rounded-2xl p-4 text-sm shadow-card {{ $sheet['balanced'] ? 'bg-emerald-50 text-emerald-800' : 'bg-terracotta-50 text-terracotta-800' }}">
        @if ($sheet['balanced'])
            ✓ Bilan équilibré — actif = passif = {{ money($sheet['total_assets']) }}.
        @else
            ⚠ Écart actif / passif : {{ money($sheet['total_assets'] - $sheet['total_liabilities']) }}. Vérifiez les écritures manuelles.
        @endif
    </div>
</div>
@endsection
