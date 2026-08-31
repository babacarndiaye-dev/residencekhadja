@extends('admin.layout')
@section('title', 'Finance')

@section('content')
<div class="flex flex-col gap-6">

    <form method="GET" class="flex flex-wrap items-end gap-3">
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Du <input type="date" name="from" value="{{ $from->toDateString() }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Au <input type="date" name="to" value="{{ $to->toDateString() }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
        <button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Appliquer</button>
        <a href="{{ route('admin.finance.journal') }}" class="text-sm font-semibold text-terracotta-600 hover:underline">Journal financier →</a>
        <a href="{{ route('admin.finance.receivables') }}" class="text-sm font-semibold text-terracotta-600 hover:underline">Créances / Dettes →</a>
        <a href="{{ route('admin.finance.cash') }}" class="text-sm font-semibold text-terracotta-600 hover:underline">Caisses →</a>
    </form>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Recettes</p>
            <p class="mt-1 font-display text-2xl font-semibold text-emerald-700">{{ money($incomeTotal) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Dépenses</p>
            <p class="mt-1 font-display text-2xl font-semibold text-terracotta-700">{{ money($expenseTotal) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Résultat de période</p>
            <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ money($incomeTotal - $expenseTotal) }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Trésorerie</h2>
            <ul class="flex flex-col gap-2 text-sm">
                @foreach ($accounts as $a)
                    <li class="flex justify-between border-b border-sable-100 pb-1.5">
                        <span>{{ $a->name }} <span class="text-xs text-nuit-400">· {{ $a->typeLabel() }}</span></span>
                        <span class="font-semibold text-nuit-900">{{ money($a->balance()) }}</span>
                    </li>
                @endforeach
            </ul>
            @if ($openSessions->isNotEmpty())
                <p class="mt-3 text-xs text-nuit-400">Caisses ouvertes : {{ $openSessions->map(fn($s) => $s->account->name)->join(', ') }}</p>
            @endif
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Par catégorie</h2>
            <div class="grid gap-4 sm:grid-cols-2 text-sm">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Recettes</p>
                    @forelse ($incomeByCat as $cat => $t)
                        <div class="flex justify-between border-b border-sable-100 py-1"><span>{{ config('finance.income_categories')[$cat] ?? $cat }}</span><span>{{ money($t) }}</span></div>
                    @empty <p class="py-1 text-nuit-400">—</p> @endforelse
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-terracotta-700">Dépenses</p>
                    @forelse ($expenseByCat as $cat => $t)
                        <div class="flex justify-between border-b border-sable-100 py-1"><span>{{ config('finance.expense_categories')[$cat] ?? $cat }}</span><span>{{ money($t) }}</span></div>
                    @empty <p class="py-1 text-nuit-400">—</p> @endforelse
                </div>
            </div>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Créances clients</h2>
            <ul class="flex flex-col gap-1.5 text-sm">
                @forelse ($receivables as $r)
                    <li class="flex justify-between"><a href="{{ route('admin.reservations.show', $r) }}" class="hover:text-terracotta-600">{{ $r->reference }} — {{ $r->guest->last_name }}</a><span class="font-semibold text-terracotta-700">{{ money($r->balance()) }}</span></li>
                @empty <li class="text-nuit-400">Aucune créance.</li> @endforelse
            </ul>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Dettes fournisseurs</h2>
            <ul class="flex flex-col gap-1.5 text-sm">
                @forelse ($payables as $inv)
                    <li class="flex justify-between"><span>{{ $inv->supplier->name }} — {{ $inv->reference }}</span><span class="font-semibold text-terracotta-700">{{ money($inv->balance()) }}</span></li>
                @empty <li class="text-nuit-400">Aucune dette.</li> @endforelse
            </ul>
        </section>
    </div>
</div>
@endsection
