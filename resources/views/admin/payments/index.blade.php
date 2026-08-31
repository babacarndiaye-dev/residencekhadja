@extends('admin.layout')
@section('title', 'Paiements en ligne')

@section('content')
<div class="flex flex-col gap-5">

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Encaissé en ligne</p>
            <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ money($totalPaid) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-xs uppercase tracking-wider text-nuit-400">En attente</p>
            <p class="mt-1 font-display text-2xl font-semibold {{ $pendingCount ? 'text-terracotta-600' : 'text-nuit-900' }}">{{ $pendingCount }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Prestataire</p>
            <p class="mt-1 font-display text-lg font-semibold text-nuit-900">{{ config('payments.providers.'.config('payments.driver').'.label') }}</p>
        </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-3 rounded-2xl bg-white p-4 shadow-card">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Réf., nom, e-mail" class="min-w-48 flex-1 rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        <select name="status" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <option value="">Tous statuts</option>
            @foreach ($statuses as $k => $l)<option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $l }}</option>@endforeach
        </select>
        <select name="purpose" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <option value="">Tous objets</option>
            @foreach ($purposes as $k => $l)<option value="{{ $k }}" @selected(($filters['purpose'] ?? '') === $k)>{{ $l }}</option>@endforeach
        </select>
        <button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Filtrer</button>
    </form>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Référence</th><th class="px-4 py-3">Objet</th><th class="px-4 py-3">Payeur</th><th class="px-4 py-3 text-right">Montant</th><th class="px-4 py-3">Moyen</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3">Date</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($intents as $i)
                    <tr class="border-t border-sable-200 hover:bg-sable-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $i->reference }}</td>
                        <td class="px-4 py-3">{{ $i->purposeLabel() }}</td>
                        <td class="px-4 py-3 text-xs text-nuit-600">{{ $i->payer_name ?: '—' }}<br>{{ $i->payer_email }}</td>
                        <td class="px-4 py-3 text-right font-medium">{{ money($i->amount) }}</td>
                        <td class="px-4 py-3 text-xs">{{ $i->method ? config('payments.methods.'.$i->method, $i->method) : '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ ['paid' => 'bg-emerald-100 text-emerald-800', 'failed' => 'bg-terracotta-100 text-terracotta-800', 'refunded' => 'bg-laiton-100 text-laiton-800', 'pending' => 'bg-sable-200 text-nuit-600'][$i->status] ?? 'bg-sable-200 text-nuit-600' }}">{{ $i->statusLabel() }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-nuit-400">{{ optional($i->paid_at ?? $i->created_at)->format('d/m/y H:i') }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.payments.show', $i->reference) }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Détail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-nuit-400">Aucune intention de paiement.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $intents->links() }}</div>
</div>
@endsection
