@extends('admin.layout')
@section('title', 'Rapports')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.bi.dashboard') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Décisionnel</a>
        <a href="{{ route('admin.bi.schedules') }}" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Planifications</a>
    </div>

    @include('admin.bi._period', ['routeName' => 'admin.bi.reports'])
    <p class="text-sm text-nuit-500">Période appliquée aux rapports : {{ $from->format('d/m/Y') }} → {{ $to->format('d/m/Y') }}</p>

    @foreach ($definitions as $group => $items)
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">{{ $group }}</h2>
            <ul class="flex flex-col divide-y divide-sable-100 text-sm">
                @foreach ($items as $key => $def)
                    <li class="flex items-center justify-between py-2">
                        <span>{{ $def['label'] }}</span>
                        <span class="flex gap-2">
                            <a href="{{ route('admin.bi.report', ['key' => $key, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="rounded-full border border-nuit-200 px-3 py-1 text-xs font-semibold text-nuit-700 hover:border-terracotta-400">Ouvrir</a>
                            <a href="{{ route('admin.bi.export', ['key' => $key, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="rounded-full bg-nuit-900 px-3 py-1 text-xs font-semibold text-white">CSV</a>
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach
</div>
@endsection
