@extends('admin.layout')
@section('title', 'Demandes clients')

@section('content')
<div class="flex flex-col gap-4">

    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.guest_requests.index') }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-nuit-900 text-white' => ! $routed, 'bg-white text-nuit-700 shadow-card' => $routed])>Toutes</a>
        @foreach (['reception' => 'Réception', 'housekeeping' => 'Housekeeping', 'maintenance' => 'Maintenance'] as $k => $label)
            <a href="{{ route('admin.guest_requests.index', ['routed' => $k]) }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-nuit-900 text-white' => $routed === $k, 'bg-white text-nuit-700 shadow-card' => $routed !== $k])>{{ $label }}</a>
        @endforeach
        <a href="{{ route('admin.guest_requests.index', array_filter(['routed' => $routed, 'done' => $showDone ? null : 1])) }}"
           class="ml-auto rounded-full border border-nuit-200 px-3 py-1.5 text-xs font-semibold text-nuit-600">
            {{ $showDone ? 'Masquer les traitées' : 'Afficher les traitées' }}
        </a>
    </div>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Demande</th><th class="px-4 py-3">Chambre / client</th><th class="px-4 py-3">Service</th><th class="px-4 py-3">Reçue</th><th class="px-4 py-3">Statut</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($requests as $r)
                    <tr class="border-t border-sable-200 align-top">
                        <td class="px-4 py-3">
                            <p class="font-medium text-nuit-900">{{ $r->typeLabel() }}</p>
                            @if ($r->charge_id)
                                <p class="text-xs font-semibold text-terracotta-600">{{ money($r->chargeAmount()) }} imputés au folio</p>
                            @endif
                            @if ($r->note)<p class="text-xs text-nuit-500">{{ $r->note }}</p>@endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-semibold">Ch. {{ $r->room->number ?? '—' }}</span>
                            <span class="block text-xs text-nuit-400">{{ $r->reservation->guest->fullName() }} · {{ $r->reservation->reference }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-nuit-500">{{ ucfirst($r->routed_to) }}</td>
                        <td class="px-4 py-3 text-xs text-nuit-400">{{ $r->created_at->diffForHumans(short: true) }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold
                                {{ ['open' => 'bg-laiton-100 text-laiton-800', 'acknowledged' => 'bg-nuit-100 text-nuit-700', 'done' => 'bg-emerald-100 text-emerald-800', 'cancelled' => 'bg-sable-200 text-nuit-400'][$r->status] }}">
                                {{ $r->statusLabel() }}
                            </span>
                            @if ($r->handledBy)<span class="block text-xs text-nuit-400">{{ $r->handledBy->name }}</span>@endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($r->status === 'open')
                                <form method="POST" action="{{ route('admin.guest_requests.ack', $r) }}" class="inline">@csrf<button class="text-xs font-semibold text-nuit-600 hover:underline">Prendre</button></form>
                            @endif
                            @if (in_array($r->status, ['open', 'acknowledged']))
                                <form method="POST" action="{{ route('admin.guest_requests.resolve', $r) }}" class="inline">@csrf<button class="ml-2 text-xs font-semibold text-emerald-700 hover:underline">Traitée</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-nuit-400">Aucune demande.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
