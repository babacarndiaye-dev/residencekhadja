@extends('admin.layout')
@section('title', 'Grand livre — '.$account->code)

@section('content')
<div class="flex flex-col gap-5">
    <a href="{{ route('admin.accounting.balance') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Balance</a>

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="font-display text-2xl font-semibold text-nuit-900"><span class="font-mono text-lg text-nuit-400">{{ $account->code }}</span> {{ $account->name }}</h2>
            <p class="text-sm text-nuit-500">{{ $account->typeLabel() }}</p>
        </div>
        <form method="GET" class="flex items-end gap-2">
            <select onchange="location.href='{{ url('admin/comptabilite/grand-livre') }}/'+this.value+'?from={{ $from->toDateString() }}&to={{ $to->toDateString() }}'" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                @foreach ($accounts as $a)<option value="{{ $a->id }}" @selected($a->id === $account->id)>{{ $a->code }} — {{ $a->name }}</option>@endforeach
            </select>
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <button class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">OK</button>
        </form>
    </div>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Pièce</th><th class="px-4 py-3">Libellé</th><th class="px-4 py-3 text-right">Débit</th><th class="px-4 py-3 text-right">Crédit</th><th class="px-4 py-3 text-right">Solde</th></tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-t border-sable-200">
                        <td class="px-4 py-2 text-xs text-nuit-500">{{ $row['line']->entry->entry_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ $row['line']->entry->reference }}</td>
                        <td class="px-4 py-2">{{ $row['line']->label ?? $row['line']->entry->label }}</td>
                        <td class="px-4 py-2 text-right">{{ $row['line']->debit ? money($row['line']->debit) : '' }}</td>
                        <td class="px-4 py-2 text-right">{{ $row['line']->credit ? money($row['line']->credit) : '' }}</td>
                        <td class="px-4 py-2 text-right font-semibold {{ $row['running'] < 0 ? 'text-terracotta-700' : 'text-nuit-900' }}">{{ money($row['running']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-nuit-400">Aucun mouvement sur la période.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
