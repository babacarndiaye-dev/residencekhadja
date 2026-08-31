@extends('admin.layout')
@section('title', 'Commandes')

@section('content')
<div class="flex flex-col gap-5">
    <form method="GET" class="flex flex-wrap items-end gap-3 rounded-2xl bg-white p-4 shadow-card">
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Recherche
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Réf., nom" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Statut
            <select name="status" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="">Tous</option>
                @foreach ($statuses as $k => $label)<option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $label }}</option>@endforeach
            </select>
        </label>
        <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Lieu
            <select name="venue" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="">Tous</option>
                @foreach ($venues as $v)<option value="{{ $v->id }}" @selected(($filters['venue'] ?? '') == $v->id)>{{ $v->name }}</option>@endforeach
            </select>
        </label>
        <button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Filtrer</button>
        <a href="{{ route('admin.orders.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">Réinitialiser</a>
    </form>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Réf.</th><th class="px-4 py-3">Lieu</th><th class="px-4 py-3">Articles</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3">Paiement</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody>
                @forelse ($orders as $o)
                    <tr class="border-t border-sable-200 hover:bg-sable-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $o->reference }}<span class="block text-xs text-nuit-400">{{ $o->created_at->format('d/m H:i') }}</span></td>
                        <td class="px-4 py-3">{{ $o->venue?->name ?? '—' }}<span class="block text-xs text-nuit-400">{{ $o->location?->label }}</span></td>
                        <td class="px-4 py-3 text-xs text-nuit-500">{{ $o->items->sum('quantity') }} article(s)</td>
                        <td class="px-4 py-3">{{ money($o->total) }}</td>
                        <td class="px-4 py-3"><x-admin.badge :status="$o->status" /></td>
                        <td class="px-4 py-3"><x-admin.badge :status="$o->payment_status" :label="['unpaid'=>'À régler','paid'=>'Réglé','charged_to_room'=>'Chambre'][$o->payment_status] ?? $o->payment_status" /></td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.orders.show', $o) }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Ouvrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-nuit-400">Aucune commande.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $orders->links() }}</div>
</div>
@endsection
