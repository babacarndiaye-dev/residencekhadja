@extends('admin.layout')
@section('title', 'Réservations de canal')

@section('content')
<div class="flex flex-col gap-6">
    <a href="{{ route('admin.distribution.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Distribution</a>

    <section class="rounded-2xl bg-white p-5 shadow-card">
        <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Simuler une réservation OTA</h2>
        <form method="POST" action="{{ route('admin.distribution.reservations.simulate') }}" class="grid gap-3 sm:grid-cols-4">
            @csrf
            <label class="text-xs font-semibold text-nuit-500">Canal
                <select name="channel" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    @foreach ($channels as $c)<option value="{{ $c->key }}">{{ $c->name }}</option>@endforeach
                </select>
            </label>
            <label class="text-xs font-semibold text-nuit-500">Catégorie
                <select name="room_slug" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    @foreach ($categories as $cat)<option value="{{ $cat->slug }}">{{ $cat->name }}</option>@endforeach
                </select>
            </label>
            <label class="text-xs font-semibold text-nuit-500">Prénom<input name="first_name" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
            <label class="text-xs font-semibold text-nuit-500">Nom<input name="last_name" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
            <label class="text-xs font-semibold text-nuit-500">Arrivée<input type="date" name="check_in" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
            <label class="text-xs font-semibold text-nuit-500">Départ<input type="date" name="check_out" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
            <label class="text-xs font-semibold text-nuit-500">Adultes<input type="number" name="adults" value="2" min="1" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
            <label class="text-xs font-semibold text-nuit-500">Montant brut (FCFA)<input type="number" name="gross_amount" min="1000" step="1000" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm"></label>
            <div class="sm:col-span-4"><button class="rounded-full bg-terracotta-500 px-5 py-2 text-sm font-semibold text-white hover:bg-terracotta-600">Importer</button></div>
        </form>
        <p class="mt-2 text-xs text-nuit-400">Équivaut à un appel du webhook <code>POST /distribution/webhook/{canal}</code>.</p>
    </section>

    <section class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Canal</th><th class="px-4 py-3">Réf. externe</th><th class="px-4 py-3">Client</th><th class="px-4 py-3">Réservation</th><th class="px-4 py-3 text-right">Brut</th><th class="px-4 py-3 text-right">Commission</th><th class="px-4 py-3">Statut</th></tr>
            </thead>
            <tbody>
                @forelse ($rows as $cr)
                    <tr class="border-t border-sable-200">
                        <td class="px-4 py-2">{{ $cr->channel->name }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ $cr->external_ref }}</td>
                        <td class="px-4 py-2">{{ $cr->reservation?->guest?->fullName() ?? '—' }}</td>
                        <td class="px-4 py-2">
                            @if ($cr->reservation)
                                <a href="{{ route('admin.reservations.show', $cr->reservation) }}" class="font-mono text-xs text-terracotta-600 hover:underline">{{ $cr->reservation->reference }}</a>
                            @else — @endif
                        </td>
                        <td class="px-4 py-2 text-right">{{ money($cr->gross_amount) }}</td>
                        <td class="px-4 py-2 text-right">{{ money($cr->commission_amount) }} @if($cr->commission_posted)<span class="text-xs text-emerald-700">✓</span>@endif</td>
                        <td class="px-4 py-2"><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ ['imported' => 'bg-emerald-100 text-emerald-800', 'failed' => 'bg-terracotta-100 text-terracotta-800'][$cr->status] ?? 'bg-sable-200 text-nuit-600' }}">{{ $cr->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-nuit-400">Aucune réservation de canal.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
    <div>{{ $rows->links() }}</div>
</div>
@endsection
