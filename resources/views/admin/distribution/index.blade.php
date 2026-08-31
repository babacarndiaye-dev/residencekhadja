@extends('admin.layout')
@section('title', 'Distribution')

@section('content')
<div class="flex flex-col gap-6">

    <div class="grid gap-4 sm:grid-cols-4">
        <a href="{{ route('admin.distribution.calendar') }}" class="rounded-2xl bg-white p-5 shadow-card hover:shadow-luxe">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Calendrier ARI</p>
            <p class="mt-1 font-display text-lg font-semibold text-nuit-900">Disponibilité & restrictions →</p>
        </a>
        <a href="{{ route('admin.distribution.rates') }}" class="rounded-2xl bg-white p-5 shadow-card hover:shadow-luxe">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Grille tarifaire</p>
            <p class="mt-1 font-display text-lg font-semibold text-nuit-900">Parité & majorations →</p>
        </a>
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Commissions (30 j)</p>
            <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ money($commission30d) }}</p>
            <p class="text-xs text-nuit-400">à comptabiliser : {{ money($pendingCommission) }}</p>
        </div>
        <a href="{{ route('admin.distribution.reservations') }}" class="rounded-2xl bg-white p-5 shadow-card hover:shadow-luxe">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Réservations de canal</p>
            <p class="mt-1 font-display text-lg font-semibold text-nuit-900">Entrantes & simulateur →</p>
        </a>
    </div>

    <section class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Canal</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Connecteur</th><th class="px-4 py-3">Commission</th><th class="px-4 py-3">Résas</th><th class="px-4 py-3">Dernière sync</th><th class="px-4 py-3">iCal</th><th></th></tr>
            </thead>
            <tbody>
                @foreach ($channels as $c)
                    <tr class="border-t border-sable-200 align-top">
                        <td class="px-4 py-3 font-medium text-nuit-900">{{ $c->name }}<span class="block text-xs text-nuit-400">{{ $c->key }}</span></td>
                        <td class="px-4 py-3">{{ $c->typeLabel() }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.distribution.channels.update', $c) }}"
                                  x-data="{ connector: '{{ $c->connector }}' }" class="flex flex-wrap items-center gap-2">
                                @csrf @method('PUT')
                                <select name="connector" x-model="connector" class="rounded-lg border border-nuit-200 px-2 py-1 text-xs" {{ $c->isDirect() ? 'disabled' : '' }}>
                                    @foreach ($connectors as $k => $l)<option value="{{ $k }}" @selected($c->connector === $k)>{{ $l }}</option>@endforeach
                                </select>
                                <input type="number" step="0.01" min="0" max="0.5" name="commission_rate" value="{{ $c->commission_rate }}" class="w-20 rounded-lg border border-nuit-200 px-2 py-1 text-xs">
                                <label class="text-xs"><input type="checkbox" name="is_active" value="1" @checked($c->is_active)> actif</label>
                                <button class="rounded-full bg-nuit-900 px-3 py-1 text-xs font-semibold text-white">OK</button>
                                <template x-if="connector === 'ical'">
                                    <span class="flex w-full flex-wrap items-center gap-2">
                                        <input type="url" name="ical_url" value="{{ $c->credentials['ical_url'] ?? '' }}" placeholder="https://…/calendar.ics"
                                               class="w-64 rounded-lg border border-nuit-200 px-2 py-1 text-xs">
                                        <select name="room_slug" class="rounded-lg border border-nuit-200 px-2 py-1 text-xs">
                                            <option value="">— catégorie cible —</option>
                                            @foreach ($roomCategories as $slug => $name)
                                                <option value="{{ $slug }}" @selected(($c->credentials['room_slug'] ?? null) === $slug)>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </span>
                                </template>
                            </form>
                        </td>
                        <td class="px-4 py-3">{{ number_format($c->commission_rate * 100, 1) }} %</td>
                        <td class="px-4 py-3">{{ $c->reservations_count }}</td>
                        <td class="px-4 py-3 text-xs text-nuit-400">{{ optional($c->last_sync_at)->format('d/m/y H:i') ?: 'jamais' }}</td>
                        <td class="px-4 py-3 text-xs">
                            @unless ($c->isDirect())
                                <div class="flex flex-wrap gap-1.5">
                                    <form method="POST" action="{{ route('admin.distribution.channels.push', $c) }}">@csrf<button class="rounded-full border border-nuit-200 px-3 py-1 font-semibold text-nuit-700 hover:border-terracotta-400">Pousser</button></form>
                                    <form method="POST" action="{{ route('admin.distribution.channels.test', $c) }}">@csrf<button class="rounded-full border border-nuit-200 px-3 py-1 font-semibold text-nuit-700 hover:border-terracotta-400">Tester</button></form>
                                    @if ($c->connector === 'ical')
                                        <form method="POST" action="{{ route('admin.distribution.channels.pull', $c) }}">@csrf<button class="rounded-full border border-nuit-200 px-3 py-1 font-semibold text-nuit-700 hover:border-terracotta-400">Importer</button></form>
                                    @endif
                                </div>
                            @endunless
                        </td>
                        <td class="px-4 py-3"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="rounded-2xl bg-white p-5 shadow-card">
        <div class="mb-2 flex items-center justify-between">
            <h2 class="font-display text-lg font-semibold text-nuit-900">Flux iCal (indisponibilités par catégorie)</h2>
            <a href="{{ route('admin.distribution.log') }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Journal de synchronisation</a>
        </div>
        <ul class="flex flex-col gap-1 text-sm">
            @foreach (\App\Models\RoomCategory::active()->orderBy('sort_order')->get() as $cat)
                <li class="flex items-center justify-between border-b border-sable-100 py-1">
                    <span>{{ $cat->name }}</span>
                    <a href="{{ route('channel.ical', $cat->slug) }}" target="_blank" class="font-mono text-xs text-terracotta-600 hover:underline">{{ url(route('channel.ical', $cat->slug, false)) }}</a>
                </li>
            @endforeach
        </ul>
        <p class="mt-2 text-xs text-nuit-400">À coller dans l'extranet d'un canal géré manuellement (import calendrier).</p>
    </section>

    <section class="rounded-2xl bg-white p-5 shadow-card">
        <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Dernières synchronisations</h2>
        <table class="w-full text-sm">
            <tbody>
                @forelse ($lastLogs as $l)
                    <tr class="border-b border-sable-100">
                        <td class="py-1.5">{{ $l->channel->name }}</td>
                        <td class="py-1.5 text-xs text-nuit-500">{{ $l->actionLabel() }}</td>
                        <td class="py-1.5 text-right">{{ $l->records }} enr.</td>
                        <td class="py-1.5 text-right text-xs text-nuit-400">{{ $l->created_at->format('d/m H:i') }}</td>
                    </tr>
                @empty
                    <tr><td class="py-4 text-center text-nuit-400">Aucune synchronisation.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
@endsection
