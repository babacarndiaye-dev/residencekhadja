@extends('admin.layout')
@section('title', 'Grand livre général')

@section('content')
<div class="flex flex-col gap-5">
    <a href="{{ route('admin.accounting.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Comptabilité</a>

    <form method="GET" class="flex flex-wrap items-end gap-3">
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Du <input type="date" name="from" value="{{ $from->toDateString() }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Au <input type="date" name="to" value="{{ $to->toDateString() }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
        <button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Appliquer</button>
        <button type="button" onclick="window.print()" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700">Imprimer</button>
    </form>

    @forelse ($accounts as $row)
        <section class="overflow-x-auto rounded-2xl bg-white shadow-card">
            <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-sable-200 px-4 py-3">
                <h2 class="font-semibold text-nuit-900"><span class="font-mono text-xs text-nuit-400">{{ $row['account']->code }}</span> {{ $row['account']->name }}</h2>
                <span class="text-xs text-nuit-500">
                    À nouveau {{ money($row['opening']) }} · Solde {{ money($row['closing']) }}
                </span>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-sable-50 text-left text-xs uppercase tracking-wider text-nuit-400">
                    <tr><th class="px-4 py-2">Date</th><th class="px-4 py-2">Pièce</th><th class="px-4 py-2">Libellé</th><th class="px-4 py-2 text-right">Débit</th><th class="px-4 py-2 text-right">Crédit</th></tr>
                </thead>
                <tbody>
                    @foreach ($row['lines'] as $l)
                        <tr class="border-t border-sable-100">
                            <td class="px-4 py-2 text-xs text-nuit-500">{{ $l->entry->entry_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 font-mono text-xs">{{ $l->entry->reference }}</td>
                            <td class="px-4 py-2">{{ $l->label ?? $l->entry->label }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ $l->debit ? money($l->debit) : '' }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ $l->credit ? money($l->credit) : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-nuit-900 font-semibold">
                        <td class="px-4 py-2" colspan="3">Mouvements de la période</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ money($row['debit']) }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ money($row['credit']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </section>
    @empty
        <p class="rounded-2xl bg-white px-4 py-10 text-center text-sm text-nuit-400">Aucun mouvement sur la période.</p>
    @endforelse
</div>
@endsection
