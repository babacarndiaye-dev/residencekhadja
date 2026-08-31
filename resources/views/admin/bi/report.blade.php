@extends('admin.layout')
@section('title', $report['label'])

@section('content')
<div class="flex flex-col gap-5">
    <a href="{{ route('admin.bi.reports') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Rapports</a>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="font-display text-lg font-semibold text-nuit-900">{{ $report['label'] }}</h2>
            <p class="text-sm text-nuit-500">{{ $from->format('d/m/Y') }} → {{ $to->format('d/m/Y') }} · {{ count($report['rows']) }} ligne(s)</p>
        </div>
        <a href="{{ route('admin.bi.export', ['key' => $report['key'], 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
           class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Exporter en CSV</a>
    </div>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr>@foreach ($report['columns'] as $col)<th class="whitespace-nowrap px-4 py-3">{{ $col }}</th>@endforeach</tr>
            </thead>
            <tbody>
                @forelse ($report['rows'] as $row)
                    <tr class="border-t border-sable-200">
                        @foreach ($report['columns'] as $col)
                            @php $v = $row[$col] ?? ''; $num = is_int($v) || (is_string($v) && preg_match('/^-?\d{3,}$/', $v)); @endphp
                            <td class="whitespace-nowrap px-4 py-2 {{ $num ? 'text-right tabular-nums' : '' }}">{{ $num ? number_format((int) $v, 0, ',', ' ') : $v }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($report['columns']) }}" class="px-4 py-10 text-center text-nuit-400">Aucune donnée sur la période.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
