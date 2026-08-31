@extends('admin.layout')
@section('title', 'Service en salle')

@section('content')
<div x-data x-init="setInterval(() => location.reload(), 15000)" class="flex flex-col gap-6">

    <section class="rounded-2xl bg-white p-5 shadow-card">
        <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Demandes en cours ({{ $open->count() }})</h2>
        @forelse ($open as $req)
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-sable-200 py-3 text-sm">
                <div>
                    <span class="font-semibold text-nuit-900">{{ $req->typeLabel() }}</span>
                    <span class="text-nuit-500"> — {{ $req->location?->label ?? 'Lieu inconnu' }}</span>
                    @if ($req->note)<span class="block text-xs italic text-nuit-400">{{ $req->note }}</span>@endif
                    <span class="block text-xs text-nuit-400">{{ $req->created_at->diffForHumans() }}</span>
                </div>
                <div class="flex gap-2">
                    @if ($req->status === 'open')
                        <form method="POST" action="{{ route('admin.service.ack', $req) }}">
                            @csrf
                            <button class="rounded-full border border-nuit-200 px-3 py-1.5 text-xs font-semibold text-nuit-700 hover:border-terracotta-400">Pris en charge</button>
                        </form>
                    @else
                        <span class="rounded-full bg-nuit-100 px-3 py-1.5 text-xs font-semibold text-nuit-700">En cours</span>
                    @endif
                    <form method="POST" action="{{ route('admin.service.resolve', $req) }}">
                        @csrf
                        <button class="rounded-full bg-terracotta-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-terracotta-600">Traité</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="border-t border-sable-200 py-6 text-center text-sm text-nuit-400">Aucune demande en attente.</p>
        @endforelse
    </section>

    <section class="rounded-2xl bg-white p-5 shadow-card">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-nuit-500">Traitées récemment</h2>
        <ul class="flex flex-col gap-1.5 text-sm text-nuit-500">
            @forelse ($recentDone as $req)
                <li>{{ $req->typeLabel() }} — {{ $req->location?->label }} <span class="text-xs text-nuit-400">({{ optional($req->resolved_at)->format('d/m H:i') }})</span></li>
            @empty
                <li class="text-nuit-400">—</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
