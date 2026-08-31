@extends('admin.layout')
@section('title', 'Achats')

@php $canCreate = auth()->user()->hasRole('stock'); @endphp

@section('content')
<div class="flex flex-col gap-5">
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-xs uppercase tracking-wider text-nuit-400">À valider</p>
            <p class="mt-1 font-display text-2xl font-semibold {{ $summary['to_approve'] ? 'text-terracotta-600' : 'text-nuit-900' }}">{{ $summary['to_approve'] }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-xs uppercase tracking-wider text-nuit-400">En cours de réception</p>
            <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ $summary['ordered'] }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Dettes fournisseurs</p>
            <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ money($summary['payable'] ?? 0) }}</p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.purchases.index') }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-nuit-900 text-white' => ! $status, 'bg-white text-nuit-700 shadow-card' => $status])>Toutes</a>
        @foreach ($statuses as $k => $label)
            <a href="{{ route('admin.purchases.index', ['status' => $k]) }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-nuit-900 text-white' => $status === $k, 'bg-white text-nuit-700 shadow-card' => $status !== $k])>{{ $label }}</a>
        @endforeach
        @if ($canCreate)<a href="{{ route('admin.purchases.create') }}" class="ml-auto rounded-full bg-terracotta-500 px-4 py-1.5 text-sm font-semibold text-white hover:bg-terracotta-600">+ Demande d’achat</a>@endif
    </div>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Réf.</th><th class="px-4 py-3">Fournisseur</th><th class="px-4 py-3">Magasin</th><th class="px-4 py-3">Total TTC</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody>
                @forelse ($orders as $o)
                    <tr class="border-t border-sable-200">
                        <td class="px-4 py-3 font-mono text-xs">{{ $o->reference }}<span class="block text-xs text-nuit-400">{{ $o->created_at->format('d/m/Y') }}</span></td>
                        <td class="px-4 py-3">{{ $o->supplier->name }}</td>
                        <td class="px-4 py-3 text-xs text-nuit-500">{{ $o->warehouse->name }}</td>
                        <td class="px-4 py-3">{{ money($o->total) }}</td>
                        <td class="px-4 py-3"><x-admin.badge :status="$o->status" /></td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.purchases.show', $o) }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Ouvrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-nuit-400">Aucune commande.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $orders->links() }}</div>
</div>
@endsection
