@extends('admin.layout')
@section('title', 'Créances & dettes')

@section('content')
<div class="flex flex-col gap-6">
    <a href="{{ route('admin.finance.dashboard') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Finance</a>

    <section class="rounded-2xl bg-white p-5 shadow-card">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-display text-lg font-semibold text-nuit-900">Créances clients</h2>
            <span class="text-sm font-semibold text-terracotta-700">{{ money($total) }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wider text-nuit-400">
                    <tr><th class="py-2">Réservation</th><th class="py-2">Client</th><th class="py-2">Séjour</th><th class="py-2">Statut</th><th class="py-2 text-right">Solde</th></tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="border-t border-sable-100">
                            <td class="py-2"><a href="{{ route('admin.reservations.show', $row['reservation']) }}" class="font-mono text-xs text-terracotta-600 hover:underline">{{ $row['reservation']->reference }}</a></td>
                            <td class="py-2">{{ $row['reservation']->guest->fullName() }}</td>
                            <td class="py-2 text-xs text-nuit-500">{{ $row['reservation']->check_in->format('d/m') }} → {{ $row['reservation']->check_out->format('d/m') }}</td>
                            <td class="py-2"><x-admin.badge :status="$row['reservation']->status" /></td>
                            <td class="py-2 text-right font-semibold">{{ money($row['balance']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-nuit-400">Aucune créance.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-2xl bg-white p-5 shadow-card">
        <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Dettes fournisseurs</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wider text-nuit-400">
                    <tr><th class="py-2">Fournisseur</th><th class="py-2">Facture</th><th class="py-2">Échéance</th><th class="py-2">Statut</th><th class="py-2 text-right">Reste dû</th></tr>
                </thead>
                <tbody>
                    @forelse ($payables as $inv)
                        <tr class="border-t border-sable-100">
                            <td class="py-2">{{ $inv->supplier->name }}</td>
                            <td class="py-2 text-xs">{{ $inv->reference }}</td>
                            <td class="py-2 text-xs text-nuit-500">{{ optional($inv->due_on)->format('d/m/Y') ?? '—' }}</td>
                            <td class="py-2"><x-admin.badge :status="$inv->status" :label="$inv->statusLabel()" /></td>
                            <td class="py-2 text-right font-semibold">{{ money($inv->balance()) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-nuit-400">Aucune dette.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
