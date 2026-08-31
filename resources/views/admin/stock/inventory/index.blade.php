@extends('admin.layout')
@section('title', 'Inventaires')

@php $canEdit = auth()->user()->hasRole('stock'); @endphp

@section('content')
<div class="flex flex-col gap-5">
    <a href="{{ route('admin.stock.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Stocks</a>

    @if ($canEdit)
        <form method="POST" action="{{ route('admin.stock.inventory.store') }}" class="flex flex-wrap items-end gap-3 rounded-2xl bg-white p-4 shadow-card">
            @csrf
            <label class="flex flex-col gap-1 text-xs font-semibold text-nuit-500">Magasin à inventorier
                <select name="warehouse_id" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    @foreach ($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
                </select>
            </label>
            <input type="text" name="note" placeholder="Note" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <button class="rounded-full bg-terracotta-500 px-5 py-2 text-sm font-semibold text-white hover:bg-terracotta-600">Démarrer un inventaire</button>
        </form>
    @endif

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Référence</th><th class="px-4 py-3">Magasin</th><th class="px-4 py-3">Lignes</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3">Date</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody>
                @forelse ($counts as $c)
                    <tr class="border-t border-sable-200">
                        <td class="px-4 py-3 font-mono text-xs">{{ $c->reference }}</td>
                        <td class="px-4 py-3">{{ $c->warehouse->name }}</td>
                        <td class="px-4 py-3 text-xs text-nuit-500">{{ $c->lines_count }}</td>
                        <td class="px-4 py-3">
                            @if ($c->status === 'open')<span class="rounded-full bg-laiton-100 px-2.5 py-0.5 text-xs font-semibold text-laiton-600">Ouvert</span>
                            @else<span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Clôturé</span>@endif
                        </td>
                        <td class="px-4 py-3 text-xs text-nuit-500">{{ $c->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.stock.inventory.show', $c) }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Ouvrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-nuit-400">Aucun inventaire.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $counts->links() }}</div>
</div>
@endsection
