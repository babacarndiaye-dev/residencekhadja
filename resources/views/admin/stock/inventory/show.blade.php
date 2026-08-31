@extends('admin.layout')
@section('title', 'Inventaire '.$count->reference)

@php $canEdit = auth()->user()->hasRole('stock') && $count->status === 'open'; @endphp

@section('content')
<div class="flex flex-col gap-5">
    <a href="{{ route('admin.stock.inventory.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Inventaires</a>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="font-display text-2xl font-semibold text-nuit-900">{{ $count->reference }}</h2>
            <p class="text-sm text-nuit-500">{{ $count->warehouse->name }} · {{ $count->status === 'open' ? 'Ouvert' : 'Clôturé le '.optional($count->closed_at)->format('d/m/Y') }}</p>
        </div>
        @if ($canEdit)
            <form method="POST" action="{{ route('admin.stock.inventory.close', $count) }}" onsubmit="return confirm('Clôturer et ajuster les stocks ?')">
                @csrf
                <button class="rounded-full bg-terracotta-500 px-5 py-2 text-sm font-semibold text-white hover:bg-terracotta-600">Clôturer & ajuster</button>
            </form>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.stock.inventory.update', $count) }}">
        @csrf @method('PUT')
        <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
            <table class="w-full text-sm">
                <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                    <tr><th class="px-4 py-3">Article</th><th class="px-4 py-3">Stock système</th><th class="px-4 py-3">Compté</th><th class="px-4 py-3">Écart</th></tr>
                </thead>
                <tbody>
                    @foreach ($count->lines as $line)
                        @php $v = $line->variance(); @endphp
                        <tr class="border-t border-sable-200">
                            <td class="px-4 py-2.5">{{ $line->item->name }} <span class="text-xs text-nuit-400">({{ $line->item->unit }})</span></td>
                            <td class="px-4 py-2.5 text-nuit-600">{{ rtrim(rtrim(number_format($line->system_qty, 3, ',', ' '), '0'), ',') }}</td>
                            <td class="px-4 py-2.5">
                                @if ($canEdit)
                                    <input type="number" step="0.001" name="lines[{{ $line->id }}]" value="{{ $line->counted_qty }}" class="w-28 rounded-lg border border-nuit-200 px-2 py-1 text-sm">
                                @else
                                    {{ $line->counted_qty !== null ? rtrim(rtrim(number_format($line->counted_qty, 3, ',', ' '), '0'), ',') : '—' }}
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-xs {{ $v === null ? 'text-nuit-300' : ($v == 0 ? 'text-nuit-400' : ($v > 0 ? 'text-emerald-700' : 'text-terracotta-700')) }}">
                                {{ $v === null ? '—' : ($v > 0 ? '+' : '').rtrim(rtrim(number_format($v, 3, ',', ' '), '0'), ',') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($canEdit)
            <div class="mt-3"><button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Enregistrer le comptage</button></div>
        @endif
    </form>
</div>
@endsection
