@extends('admin.layout')
@section('title', 'Room service')

@php $labels = \App\Models\Order::STATUSES; @endphp

@section('content')
<div class="flex flex-col gap-5" x-data x-init="setInterval(() => window.location.reload(), 30000)">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-display text-lg font-semibold text-nuit-900">Room service — livraisons</h1>
            <p class="text-sm text-nuit-500">Commandes prêtes à monter en chambre.</p>
        </div>
        <a href="{{ route('admin.pos.room_service') }}" class="rounded-full border border-sable-300 px-4 py-2 text-sm font-semibold text-nuit-600 hover:border-terracotta-400">Actualiser</a>
    </div>

    @if ($orders->isEmpty())
        <p class="rounded-2xl bg-white p-10 text-center text-sm text-nuit-400 shadow-card">Aucune commande room service en cours.</p>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($orders as $o)
                @php
                    $pipeline = ['ready' => 'Prête', 'out_for_delivery' => 'En livraison', 'delivered' => 'Livrée'];
                    $done = array_search($o->status, array_keys($pipeline), true);
                @endphp
                <div class="flex flex-col gap-3 rounded-2xl bg-white p-4 shadow-card">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-display text-lg font-semibold text-nuit-900">Chambre {{ $o->room->number ?? $o->table_label ?? '—' }}</p>
                            <p class="text-xs text-nuit-400">{{ $o->reference }} · {{ optional($o->reservation?->guest)->fullName() }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $o->status === 'delivered' ? 'bg-emerald-100 text-emerald-700' : 'bg-terracotta-100 text-terracotta-700' }}">{{ $labels[$o->status] ?? $o->status }}</span>
                    </div>

                    <ul class="flex flex-col gap-1 text-sm text-nuit-700">
                        @foreach ($o->items as $it)
                            <li>{{ $it->quantity }}× {{ $it->name }}</li>
                        @endforeach
                    </ul>

                    <div class="flex items-center gap-1 text-[0.7rem] font-semibold uppercase tracking-wider">
                        @foreach ($pipeline as $key => $label)
                            <span class="rounded px-2 py-0.5 {{ $done !== false && $loop->index <= $done ? 'bg-nuit-900 text-white' : 'bg-sable-100 text-nuit-400' }}">{{ $label }}</span>
                            @unless ($loop->last)<span class="text-nuit-300">→</span>@endunless
                        @endforeach
                    </div>

                    @if ($o->nextStatus() && $o->status !== 'delivered')
                        <form method="POST" action="{{ route('admin.pos.room_service.advance', $o) }}">
                            @csrf
                            <button class="w-full rounded-full bg-terracotta-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-terracotta-600">
                                {{ $o->status === 'ready' ? 'Partir en livraison' : 'Marquer livrée' }}
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
