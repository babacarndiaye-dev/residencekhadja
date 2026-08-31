@extends('admin.layout')
@section('title', 'Écritures comptables')

@php $canEdit = auth()->user()->hasRole('finance'); @endphp

@section('content')
<div class="flex flex-col gap-5">
    <a href="{{ route('admin.accounting.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Comptabilité</a>

    @if ($canEdit)
        <form method="POST" action="{{ route('admin.accounting.entries.store') }}"
              x-data="{ lines: [{ code: '', debit: 0, credit: 0 }, { code: '', debit: 0, credit: 0 }],
                        get d() { return this.lines.reduce((s,l) => s + (+l.debit||0), 0); },
                        get c() { return this.lines.reduce((s,l) => s + (+l.credit||0), 0); } }"
              class="flex flex-col gap-3 rounded-2xl bg-white p-5 shadow-card">
            @csrf
            <h2 class="font-display text-lg font-semibold text-nuit-900">Écriture manuelle</h2>
            <div class="grid gap-3 sm:grid-cols-3">
                <select name="journal" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    @foreach ($journals as $k => $label)<option value="{{ $k }}" @selected($k==='OD')>{{ $k }} — {{ $label }}</option>@endforeach
                </select>
                <input type="date" name="entry_date" value="{{ now()->toDateString() }}" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <input type="text" name="label" placeholder="Libellé" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            </div>
            <template x-for="(l, i) in lines" :key="i">
                <div class="flex items-center gap-2">
                    <select :name="`lines[${i}][code]`" x-model="l.code" class="flex-1 rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                        <option value="">Compte…</option>
                        @foreach ($accounts as $a)<option value="{{ $a->code }}">{{ $a->code }} — {{ $a->name }}</option>@endforeach
                    </select>
                    <input type="number" :name="`lines[${i}][debit]`" x-model.number="l.debit" placeholder="Débit" class="w-28 rounded-lg border border-nuit-200 px-2 py-2 text-sm">
                    <input type="number" :name="`lines[${i}][credit]`" x-model.number="l.credit" placeholder="Crédit" class="w-28 rounded-lg border border-nuit-200 px-2 py-2 text-sm">
                    <button type="button" x-on:click="lines.splice(i,1)" x-show="lines.length > 2" class="text-xs text-red-600">✕</button>
                </div>
            </template>
            <div class="flex items-center justify-between text-sm">
                <button type="button" x-on:click="lines.push({ code:'', debit:0, credit:0 })" class="rounded-full border border-nuit-200 px-3 py-1 text-xs font-semibold text-nuit-700">+ Ligne</button>
                <span :class="d === c && d > 0 ? 'text-emerald-700' : 'text-terracotta-700'">
                    Débit <span x-text="new Intl.NumberFormat('fr-FR').format(d)"></span> / Crédit <span x-text="new Intl.NumberFormat('fr-FR').format(c)"></span>
                </span>
            </div>
            <button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Enregistrer l’écriture</button>
        </form>
    @endif

    <form method="GET" class="flex flex-wrap gap-3 rounded-2xl bg-white p-4 shadow-card">
        <select name="journal" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <option value="">Tous journaux</option>
            @foreach ($journals as $k => $label)<option value="{{ $k }}" @selected(($filters['journal'] ?? '') === $k)>{{ $k }} — {{ $label }}</option>@endforeach
        </select>
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        <button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Filtrer</button>
    </form>

    <div class="flex flex-col gap-3">
        @forelse ($entries as $e)
            <div class="rounded-2xl bg-white p-4 text-sm shadow-card">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-sable-100 pb-2">
                    <span class="font-mono text-xs text-nuit-500">{{ $e->reference }}</span>
                    <span class="text-nuit-500">{{ $e->journalLabel() }} · {{ $e->entry_date->format('d/m/Y') }}</span>
                    <span class="font-medium text-nuit-900">{{ $e->label }}</span>
                    <span class="flex items-center gap-3">
                        <span class="font-semibold">{{ money($e->amount) }}</span>
                        @if ($canEdit && $e->journal !== 'CL')
                            <form method="POST" action="{{ route('admin.accounting.entries.reverse', $e) }}" class="inline"
                                  onsubmit="return confirm('Contre-passer l’écriture {{ $e->reference }} ? Une écriture miroir sera créée.')">
                                @csrf
                                <button class="text-xs font-semibold text-terracotta-600 hover:underline">Contre-passer</button>
                            </form>
                        @endif
                    </span>
                </div>
                <table class="mt-2 w-full text-xs">
                    @foreach ($e->lines as $l)
                        <tr>
                            <td class="py-0.5 font-mono text-nuit-400">{{ $l->account->code }}</td>
                            <td class="py-0.5">{{ $l->account->name }}</td>
                            <td class="py-0.5 text-right text-nuit-700">{{ $l->debit ? money($l->debit) : '' }}</td>
                            <td class="py-0.5 text-right text-nuit-700">{{ $l->credit ? money($l->credit) : '' }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @empty
            <p class="rounded-2xl bg-white p-8 text-center text-nuit-400 shadow-card">Aucune écriture.</p>
        @endforelse
    </div>
    <div>{{ $entries->links() }}</div>
</div>
@endsection
