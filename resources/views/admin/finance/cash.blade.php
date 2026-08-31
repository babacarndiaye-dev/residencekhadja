@extends('admin.layout')
@section('title', 'Caisses')

@section('content')
<div class="flex flex-col gap-6">
    <a href="{{ route('admin.finance.dashboard') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Finance</a>

    <form method="POST" action="{{ route('admin.finance.cash.open') }}" class="flex flex-wrap items-end gap-3 rounded-2xl bg-white p-4 shadow-card">
        @csrf
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Caisse
            <select name="finance_account_id" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                @foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
            </select>
        </label>
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Fond de caisse
            <input type="number" name="opening_float" value="0" min="0" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <button class="rounded-full bg-terracotta-500 px-5 py-2 text-sm font-semibold text-white hover:bg-terracotta-600">Ouvrir une caisse</button>
    </form>

    <section>
        <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Sessions ouvertes</h2>
        <div class="flex flex-col gap-3">
            @forelse ($open as $s)
                <form method="POST" action="{{ route('admin.finance.cash.close', $s) }}" class="grid items-end gap-3 rounded-2xl bg-white p-4 shadow-card sm:grid-cols-[1.4fr_1fr_1fr_auto]">
                    @csrf
                    <div>
                        <p class="font-semibold text-nuit-900">{{ $s->account->name }}</p>
                        <p class="text-xs text-nuit-400">Ouverte {{ $s->opened_at->format('d/m H:i') }} par {{ $s->openedBy->name ?? '—' }}</p>
                        <p class="mt-1 text-xs text-nuit-500">Fond {{ money($s->opening_float) }} · Théorique <strong>{{ money($s->expected()) }}</strong></p>
                    </div>
                    <label class="text-xs font-semibold text-nuit-500">Montant compté
                        <input type="number" name="counted_amount" min="0" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    </label>
                    <input type="text" name="note" placeholder="Note" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    <button class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Clôturer</button>
                </form>
            @empty
                <p class="rounded-2xl bg-white p-6 text-center text-sm text-nuit-400 shadow-card">Aucune caisse ouverte.</p>
            @endforelse
        </div>
    </section>

    <section>
        <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Dernières clôtures</h2>
        <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
            <table class="w-full text-sm">
                <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                    <tr><th class="px-4 py-3">Caisse</th><th class="px-4 py-3">Clôturée</th><th class="px-4 py-3">Théorique</th><th class="px-4 py-3">Compté</th><th class="px-4 py-3">Écart</th></tr>
                </thead>
                <tbody>
                    @forelse ($recent as $s)
                        <tr class="border-t border-sable-200">
                            <td class="px-4 py-2.5">{{ $s->account->name }}</td>
                            <td class="px-4 py-2.5 text-xs text-nuit-500">{{ optional($s->closed_at)->format('d/m H:i') }}</td>
                            <td class="px-4 py-2.5">{{ money($s->expected_amount) }}</td>
                            <td class="px-4 py-2.5">{{ money($s->counted_amount) }}</td>
                            <td class="px-4 py-2.5 font-semibold {{ $s->variance == 0 ? 'text-nuit-400' : ($s->variance > 0 ? 'text-emerald-700' : 'text-terracotta-700') }}">
                                {{ $s->variance > 0 ? '+' : '' }}{{ money($s->variance) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-nuit-400">—</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
