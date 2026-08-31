@extends('admin.layout')
@section('title', 'Cuisine — KDS')

@section('content')
<div x-data="{ }" x-init="setInterval(() => location.reload(), 15000)" class="flex flex-col gap-4">

    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.kds.index') }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-nuit-900 text-white' => ! $activeVenue, 'bg-white text-nuit-700 shadow-card' => $activeVenue])>Tous</a>
        @foreach ($venues as $v)
            <a href="{{ route('admin.kds.index', ['venue' => $v->id]) }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-nuit-900 text-white' => $activeVenue == $v->id, 'bg-white text-nuit-700 shadow-card' => $activeVenue != $v->id])>{{ $v->name }}</a>
        @endforeach
        <span class="ml-auto text-xs text-nuit-400">Rafraîchissement auto toutes les 15 s</span>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        @foreach (['new' => 'Nouvelles', 'preparing' => 'En préparation', 'ready' => 'Prêtes'] as $status => $title)
            @php $bucket = $orders->get($status) ?? collect(); @endphp
            <section class="rounded-2xl bg-white p-3 shadow-card">
                <h2 class="mb-2 px-1 text-sm font-semibold uppercase tracking-wider text-nuit-500">
                    {{ $title }} <span class="text-nuit-300">({{ $bucket->count() }})</span>
                </h2>
                <div class="flex flex-col gap-3">
                    @forelse ($bucket as $order)
                        <article @class([
                            'rounded-xl border p-3',
                            'border-laiton-300 bg-laiton-50' => $status === 'new' && $order->placed_at?->lt(now()->subMinutes(10)),
                            'border-sable-200' => ! ($status === 'new' && $order->placed_at?->lt(now()->subMinutes(10))),
                        ])>
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-sm font-bold text-nuit-900">{{ $order->reference }}</span>
                                <span class="text-xs text-nuit-400">{{ $order->placed_at?->diffForHumans(short: true) }}</span>
                            </div>
                            <p class="text-xs text-nuit-500">
                                {{ $order->type === 'room_service' ? 'Room service' : ($order->venue?->name) }} · {{ $order->location?->label }}
                            </p>
                            <ul class="mt-2 flex flex-col gap-1 text-sm">
                                @foreach ($order->items as $line)
                                    <li>
                                        <span class="font-semibold text-nuit-900">{{ $line->quantity }}×</span> {{ $line->name }}
                                        @if ($line->options)<span class="block pl-5 text-xs text-nuit-500">{{ collect($line->options)->pluck('name')->join(', ') }}</span>@endif
                                        @if ($line->note)<span class="block pl-5 text-xs italic text-terracotta-700">{{ $line->note }}</span>@endif
                                    </li>
                                @endforeach
                            </ul>
                            @if ($order->note)<p class="mt-2 rounded bg-sable-100 px-2 py-1 text-xs italic text-nuit-600">{{ $order->note }}</p>@endif
                            <form method="POST" action="{{ route('admin.kds.advance', $order) }}" class="mt-3">
                                @csrf
                                <button class="w-full rounded-full bg-nuit-900 px-3 py-2 text-xs font-semibold text-white hover:bg-nuit-800">
                                    @switch($status)
                                        @case('new') Commencer la préparation @break
                                        @case('preparing') Marquer prête @break
                                        @case('ready') Marquer servie @break
                                    @endswitch
                                </button>
                            </form>
                        </article>
                    @empty
                        <p class="px-1 py-6 text-center text-xs text-nuit-400">Aucune commande.</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    @if ($served->isNotEmpty())
        <section class="rounded-2xl bg-white p-4 shadow-card">
            <h2 class="mb-2 text-sm font-semibold uppercase tracking-wider text-nuit-500">Servies récemment</h2>
            <div class="flex flex-wrap gap-2">
                @foreach ($served as $order)
                    <form method="POST" action="{{ route('admin.kds.advance', $order) }}">
                        @csrf
                        <button class="rounded-full bg-sable-100 px-3 py-1.5 text-xs text-nuit-700 hover:bg-sable-200">
                            {{ $order->reference }} · terminer
                        </button>
                    </form>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
