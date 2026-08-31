@extends('admin.layout')
@section('title', 'Fidélité')

@section('content')
<div class="flex flex-col gap-6">

    <section class="grid gap-4 sm:grid-cols-3">
        @foreach ($tiers as $t)
            <a href="{{ route('admin.crm.loyalty', ['tier' => $t->code]) }}"
               class="rounded-2xl bg-white p-5 shadow-card hover:shadow-luxe {{ $activeTier === $t->code ? 'ring-2 ring-terracotta-400' : '' }}">
                <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold text-white" style="background: {{ $t->color }}">{{ $t->name }}</span>
                <p class="mt-2 font-display text-2xl font-semibold text-nuit-900">{{ $t->accounts_count }}</p>
                <p class="text-xs text-nuit-400">dès {{ number_format($t->min_points, 0, ',', ' ') }} pts · gain x{{ rtrim(rtrim(number_format($t->earn_rate, 2), '0'), '.') }}</p>
            </a>
        @endforeach
    </section>

    <div class="flex items-center gap-3 text-sm">
        <span class="text-nuit-500">1 point = {{ money($pointValue) }}</span>
        @if ($activeTier)<a href="{{ route('admin.crm.loyalty') }}" class="rounded-full border border-nuit-200 px-3 py-1 text-xs font-semibold">Tous les paliers</a>@endif
    </div>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Carte</th><th class="px-4 py-3">Client</th><th class="px-4 py-3">Palier</th><th class="px-4 py-3 text-right">Solde</th><th class="px-4 py-3 text-right">Cumul à vie</th><th class="px-4 py-3">Depuis</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($members as $m)
                    <tr class="border-t border-sable-200 hover:bg-sable-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $m->member_no }}</td>
                        <td class="px-4 py-3 font-medium text-nuit-900">{{ $m->guest->fullName() }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-semibold text-white" style="background: {{ $m->tier->color ?? '#596d7a' }}">{{ $m->tier->name ?? '—' }}</span></td>
                        <td class="px-4 py-3 text-right">{{ number_format($m->points_balance, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-right text-nuit-500">{{ number_format($m->lifetime_points, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-xs text-nuit-400">{{ optional($m->enrolled_at)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.guests.show', $m->guest) }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Fiche</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-nuit-400">Aucun membre.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $members->links() }}</div>
</div>
@endsection
