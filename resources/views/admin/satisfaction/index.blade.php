@extends('admin.layout')
@section('title', 'Satisfaction')

@section('content')
@php
    $stars = fn ($n) => str_repeat('★', (int) $n).str_repeat('☆', 5 - (int) $n);
@endphp
<div class="flex flex-col gap-5">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2 text-xs">
            @foreach (['today' => 'Aujourd’hui', '7d' => '7 j', '30d' => '30 j', '90d' => '90 j', 'mtd' => 'Mois'] as $k => $label)
                <a href="{{ route('admin.satisfaction.index', ['period' => $k, 'filter' => $filter]) }}"
                   @class([
                       'rounded-md px-3 py-1.5 font-semibold',
                       'bg-nuit-900 text-white' => $period === $k,
                       'border border-sable-300 text-nuit-700 hover:border-terracotta-400' => $period !== $k,
                   ])>{{ $label }}</a>
            @endforeach
            <span class="ml-1 text-nuit-400">{{ $from->translatedFormat('d M') }} – {{ $to->translatedFormat('d M Y') }}</span>
        </div>
        <form method="POST" action="{{ route('admin.satisfaction.invite') }}" class="flex items-center gap-2">
            @csrf
            <input type="text" name="reference" placeholder="Réf. séjour" required
                   class="w-32 rounded-md border border-sable-300 px-2.5 py-1.5 text-sm focus:border-terracotta-500 focus:outline-none">
            <button class="rounded-md border border-sable-300 px-3 py-1.5 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Inviter</button>
        </form>
    </div>

    {{-- KPIs --}}
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-sable-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wider text-nuit-400">NPS</p>
            <p class="font-display text-2xl font-semibold {{ ($stats['nps'] ?? 0) >= 0 ? 'text-emerald-700' : 'text-terracotta-700' }}">
                {{ $stats['nps'] !== null ? $stats['nps'] : '—' }}
            </p>
            <p class="text-xs text-nuit-400">{{ $stats['promoters'] }} prom. · {{ $stats['detractors'] }} détr. · base {{ $stats['nps_base'] }}</p>
        </div>
        <div class="rounded-lg border border-sable-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Note moyenne</p>
            <p class="font-display text-2xl font-semibold text-nuit-900">{{ $stats['avg_overall'] !== null ? number_format($stats['avg_overall'], 2, ',', ' ') : '—' }}<span class="text-base text-nuit-400"> / 5</span></p>
            <p class="text-xs text-nuit-400">{{ $stats['completed'] }} réponse(s)</p>
        </div>
        <div class="rounded-lg border border-sable-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Taux de réponse</p>
            <p class="font-display text-2xl font-semibold text-nuit-900">{{ $stats['response_rate'] !== null ? $stats['response_rate'].' %' : '—' }}</p>
            <p class="text-xs text-nuit-400">{{ $stats['sent'] }} envoyée(s) · {{ $stats['pending'] }} en attente</p>
        </div>
        <div class="rounded-lg border border-sable-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wider text-nuit-400">À traiter</p>
            <p class="font-display text-2xl font-semibold {{ $stats['to_handle'] ? 'text-terracotta-700' : 'text-nuit-900' }}">{{ $stats['to_handle'] }}</p>
            <p class="text-xs text-nuit-400">{{ $stats['published'] }} avis publié(s)</p>
        </div>
    </div>

    <div class="grid gap-3 lg:grid-cols-[1.4fr_1fr]">
        {{-- Tendance + répartition --}}
        <div class="rounded-lg border border-sable-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Tendance de la note (hebdo)</p>
            <div class="mt-2 h-10 text-terracotta-500">{!! $trendSvg !!}</div>
            <div class="mt-3 space-y-1.5">
                @foreach ($stats['distribution'] as $note => $count)
                    @php $pct = $stats['completed'] ? round($count / $stats['completed'] * 100) : 0; @endphp
                    <div class="flex items-center gap-2 text-xs">
                        <span class="w-8 shrink-0 text-laiton-500">{{ $stars($note) }}</span>
                        <span class="h-2 flex-1 overflow-hidden rounded bg-sable-100">
                            <span class="block h-full bg-laiton-400" style="width: {{ $pct }}%"></span>
                        </span>
                        <span class="w-8 shrink-0 text-right tabular-nums text-nuit-500">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        {{-- Critères --}}
        <div class="rounded-lg border border-sable-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Notes par critère</p>
            <div class="mt-2 space-y-2">
                @foreach ($stats['categories'] as $cat)
                    <div class="flex items-center justify-between gap-2 text-sm">
                        <span class="text-nuit-600">{{ $cat['label'] }}</span>
                        <span class="font-semibold text-nuit-900">
                            {{ $cat['avg'] !== null ? number_format($cat['avg'], 2, ',', ' ').' / 5' : '—' }}
                            <span class="text-xs font-normal text-nuit-400">({{ $cat['count'] }})</span>
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="flex flex-wrap gap-2 text-xs">
        @foreach ([
            'all' => 'Tous', 'attention' => 'À traiter', 'to_publish' => 'À publier',
            'published' => 'Publiés', 'open' => 'Non traités',
        ] as $k => $label)
            <a href="{{ route('admin.satisfaction.index', ['period' => $period, 'filter' => $k]) }}"
               @class([
                   'rounded-md px-3 py-1.5 font-semibold',
                   'bg-terracotta-500 text-white' => $filter === $k,
                   'border border-sable-300 text-nuit-700 hover:border-terracotta-400' => $filter !== $k,
               ])>{{ $label }}</a>
        @endforeach
    </div>

    {{-- Liste --}}
    <div class="overflow-x-auto rounded-lg border border-sable-200 bg-white">
        <table class="w-full min-w-[52rem] text-sm">
            <thead class="bg-sable-50 text-left text-xs uppercase tracking-wider text-nuit-400">
                <tr>
                    <th class="px-3 py-2.5">Date</th>
                    <th class="px-3 py-2.5">Client</th>
                    <th class="px-3 py-2.5">Note</th>
                    <th class="px-3 py-2.5">NPS</th>
                    <th class="px-3 py-2.5">Commentaire</th>
                    <th class="px-3 py-2.5">Statut</th>
                    <th class="px-3 py-2.5"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($responses as $r)
                    <tr class="border-t border-sable-100 align-top {{ $r->needsAttention() && $r->status !== 'triaged' ? 'bg-terracotta-50/40' : '' }}">
                        <td class="whitespace-nowrap px-3 py-2.5 text-nuit-500">{{ $r->completed_at->translatedFormat('d M Y') }}</td>
                        <td class="px-3 py-2.5">
                            <p class="font-medium text-nuit-900">{{ $r->displayAuthor() }}</p>
                            @if ($r->reservation)<p class="text-xs text-nuit-400">{{ $r->reservation->reference }}</p>@endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-2.5 text-laiton-500">{{ $stars($r->rating_overall) }}</td>
                        <td class="px-3 py-2.5">
                            @if ($r->nps_score !== null)
                                <span @class([
                                    'rounded px-1.5 py-0.5 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-700' => $r->isPromoter(),
                                    'bg-terracotta-100 text-terracotta-700' => $r->isDetractor(),
                                    'bg-sable-100 text-nuit-500' => ! $r->isPromoter() && ! $r->isDetractor(),
                                ])>{{ $r->nps_score }}</span>
                            @else <span class="text-nuit-300">—</span> @endif
                        </td>
                        <td class="max-w-xs px-3 py-2.5 text-nuit-600">
                            <span class="line-clamp-2">{{ $r->comment ?: '—' }}</span>
                        </td>
                        <td class="px-3 py-2.5">
                            <span class="rounded bg-sable-100 px-1.5 py-0.5 text-[11px] text-nuit-500">{{ $r->statusLabel() }}</span>
                            @if ($r->is_published)<span class="ml-1 rounded bg-emerald-100 px-1.5 py-0.5 text-[11px] text-emerald-700">publié</span>@endif
                        </td>
                        <td class="px-3 py-2.5 text-right">
                            <a href="{{ route('admin.satisfaction.show', $r->id) }}" class="font-semibold text-terracotta-600 hover:underline">Ouvrir</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-nuit-400">Aucun avis sur cette sélection.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $responses->links() }}
</div>
@endsection
