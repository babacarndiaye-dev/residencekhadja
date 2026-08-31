@extends('admin.layout')
@section('title', 'Journal financier')

@php $canEdit = auth()->user()->hasRole('finance'); @endphp

@section('content')
<div class="flex flex-col gap-5">
    <a href="{{ route('admin.finance.dashboard') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Finance</a>

    @if ($canEdit)
        <form method="POST" action="{{ route('admin.finance.transaction.store') }}"
              x-data="{ dir: 'expense' }" class="grid gap-3 rounded-2xl bg-white p-5 shadow-card sm:grid-cols-3">
            @csrf
            <h2 class="font-display text-lg font-semibold text-nuit-900 sm:col-span-3">Saisir une opération</h2>
            <select name="direction" x-model="dir" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="income">Recette</option>
                <option value="expense">Dépense</option>
            </select>
            <select name="category" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <template x-if="dir === 'income'"><optgroup label="Recettes">
                    @foreach ($incomeCategories as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                </optgroup></template>
                <template x-if="dir === 'expense'"><optgroup label="Dépenses">
                    @foreach ($expenseCategories as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                </optgroup></template>
            </select>
            <select name="finance_account_id" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                @foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
            </select>
            <select name="method" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                @foreach ($methods as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
            </select>
            <input type="number" name="amount" placeholder="Montant" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <input type="date" name="operation_date" value="{{ now()->toDateString() }}" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <input type="text" name="label" placeholder="Libellé" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm sm:col-span-2">
            <button class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Enregistrer</button>
        </form>
    @endif

    <form method="GET" class="flex flex-wrap gap-3 rounded-2xl bg-white p-4 shadow-card">
        <select name="direction" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <option value="">Tous</option>
            <option value="income" @selected(($filters['direction'] ?? '') === 'income')>Recettes</option>
            <option value="expense" @selected(($filters['direction'] ?? '') === 'expense')>Dépenses</option>
        </select>
        <select name="account" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <option value="">Tous comptes</option>
            @foreach ($accounts as $a)<option value="{{ $a->id }}" @selected(($filters['account'] ?? '') == $a->id)>{{ $a->name }}</option>@endforeach
        </select>
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        <button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Filtrer</button>
    </form>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Libellé</th><th class="px-4 py-3">Catégorie</th><th class="px-4 py-3">Compte</th><th class="px-4 py-3 text-right">Montant</th></tr>
            </thead>
            <tbody>
                @forelse ($transactions as $t)
                    <tr class="border-t border-sable-200">
                        <td class="px-4 py-2.5 text-xs text-nuit-500">{{ $t->operation_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-2.5">{{ $t->label }}</td>
                        <td class="px-4 py-2.5 text-xs text-nuit-500">{{ $t->categoryLabel() }}</td>
                        <td class="px-4 py-2.5 text-xs text-nuit-500">{{ $t->account->name }}</td>
                        <td class="px-4 py-2.5 text-right font-semibold {{ $t->direction === 'income' ? 'text-emerald-700' : 'text-terracotta-700' }}">
                            {{ $t->direction === 'income' ? '+' : '−' }}{{ money($t->amount) }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-nuit-400">Aucune opération.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $transactions->links() }}</div>
</div>
@endsection
