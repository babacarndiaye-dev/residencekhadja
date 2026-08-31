@extends('admin.layout')
@section('title', 'CRM 360°')

@section('content')
<div class="flex flex-col gap-6">

    <div class="grid gap-4 sm:grid-cols-4">
        <a href="{{ route('admin.guests.index') }}" class="rounded-2xl bg-white p-5 shadow-card hover:shadow-luxe">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Clients</p>
            <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ number_format($guests, 0, ',', ' ') }}</p>
        </a>
        <a href="{{ route('admin.crm.loyalty') }}" class="rounded-2xl bg-white p-5 shadow-card hover:shadow-luxe">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Membres fidélité</p>
            <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ number_format($members, 0, ',', ' ') }}</p>
        </a>
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Opt-in marketing</p>
            <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ number_format($optedIn, 0, ',', ' ') }}</p>
            <p class="text-xs text-nuit-400">{{ $guests ? round($optedIn / $guests * 100) : 0 }} % de la base</p>
        </div>
        <a href="{{ route('admin.marketing.index') }}" class="rounded-2xl bg-white p-5 shadow-card hover:shadow-luxe">
            <p class="text-xs uppercase tracking-wider text-nuit-400">Campagnes</p>
            <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ $lastCampaigns->count() }}</p>
            <p class="text-xs text-nuit-400">Voir le marketing →</p>
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Paliers de fidélité</h2>
            <ul class="flex flex-col gap-2 text-sm">
                @foreach ($tiers as $t)
                    <li class="flex items-center justify-between border-b border-sable-100 pb-2">
                        <span class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full" style="background: {{ $t->color }}"></span>
                            {{ $t->name }}
                            <span class="text-xs text-nuit-400">dès {{ number_format($t->min_points, 0, ',', ' ') }} pts · x{{ rtrim(rtrim(number_format($t->earn_rate, 2), '0'), '.') }}</span>
                        </span>
                        <span class="font-semibold text-nuit-900">{{ $t->accounts_count }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Anniversaires du mois</h2>
            <ul class="flex flex-col gap-1.5 text-sm">
                @forelse ($birthdays as $g)
                    <li class="flex justify-between">
                        <a href="{{ route('admin.guests.show', $g) }}" class="hover:text-terracotta-600">{{ $g->fullName() }}</a>
                        <span class="text-xs text-nuit-400">{{ $g->birthdate->format('d/m') }}</span>
                    </li>
                @empty
                    <li class="text-nuit-400">Aucun anniversaire ce mois-ci.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Top membres (cumul à vie)</h2>
            <table class="w-full text-sm">
                <tbody>
                    @foreach ($topMembers as $m)
                        <tr class="border-b border-sable-100">
                            <td class="py-1.5"><a href="{{ route('admin.guests.show', $m->guest) }}" class="hover:text-terracotta-600">{{ $m->guest->fullName() }}</a></td>
                            <td class="py-1.5 text-xs text-nuit-400">{{ $m->tier->name ?? '—' }}</td>
                            <td class="py-1.5 text-right font-semibold text-nuit-900">{{ number_format($m->lifetime_points, 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-card">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-display text-lg font-semibold text-nuit-900">Segments</h2>
                <a href="{{ route('admin.crm.segments') }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Gérer</a>
            </div>
            <ul class="flex flex-col gap-1.5 text-sm">
                @forelse ($segments as $s)
                    <li class="flex justify-between">
                        <span>{{ $s->name }}</span>
                        <span class="text-xs text-nuit-400">{{ $s->size() }} clients · {{ $s->campaigns_count }} camp.</span>
                    </li>
                @empty
                    <li class="text-nuit-400">Aucun segment.</li>
                @endforelse
            </ul>
        </section>
    </div>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('admin.crm.loyalty') }}" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Membres fidélité</a>
        <a href="{{ route('admin.crm.segments') }}" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Segments</a>
        <a href="{{ route('admin.crm.promos') }}" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Codes promo</a>
        <a href="{{ route('admin.marketing.index') }}" class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Campagnes marketing</a>
    </div>
</div>
@endsection
