@extends('app.layout')
@section('title', 'Fidélité')

@section('content')
<div class="flex flex-col gap-4">

    @if ($account)
        <section class="overflow-hidden rounded-2xl text-white shadow-luxe" style="background: linear-gradient(135deg, #374249, {{ $account->tier->color ?? '#61767d' }})">
            <div class="p-5">
                <p class="text-[0.7rem] uppercase tracking-[0.25em] text-white/60">{{ $programName }}</p>
                <p class="mt-1 font-display text-xl font-semibold">{{ $stay->guest->fullName() }}</p>
                <p class="text-sm text-white/70">{{ $account->tier->name ?? 'Membre' }} · Carte {{ $account->member_no }}</p>

                <div class="mt-4 flex items-end justify-between">
                    <div>
                        <p class="text-[0.7rem] text-white/60">Points disponibles</p>
                        <p class="font-display text-3xl font-bold">{{ number_format($account->points_balance, 0, ',', ' ') }}</p>
                    </div>
                    <p class="text-sm text-white/70">≈ {{ money($account->balanceValue()) }}</p>
                </div>
            </div>
        </section>

        @php $next = $account->pointsToNextTier(); @endphp
        @if ($next)
            <div class="rounded-2xl bg-white p-4 text-sm text-nuit-600 shadow-card">
                Plus que <b class="text-nuit-900">{{ number_format($next['missing'], 0, ',', ' ') }}</b> points pour atteindre le palier <b>{{ $next['tier']->name }}</b>.
            </div>
        @endif

        @if ($account->tier && $account->tier->perks)
            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h2 class="mb-2 text-sm font-semibold uppercase tracking-wider text-nuit-500">Vos avantages</h2>
                <ul class="flex flex-col gap-1.5 text-sm text-nuit-700">
                    @foreach ($account->tier->perks as $perk)
                        <li class="flex items-start gap-2"><span class="text-terracotta-500">◆</span>{{ $perk }}</li>
                    @endforeach
                </ul>
            </section>
        @endif
    @else
        <section class="rounded-2xl bg-white p-6 text-center shadow-card">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-terracotta-50 text-terracotta-600"><x-icon name="workspace_premium" :size="28" /></span>
            <h1 class="mt-3 font-display text-lg font-semibold text-nuit-900">{{ $programName }}</h1>
            <p class="mt-1 text-sm text-nuit-500">Cumulez des points sur vos séjours et vos consommations, échangeables en remise à la réception.</p>
            <form method="POST" action="{{ route('guest.loyalty.enrol') }}" class="mt-4">
                @csrf
                <button class="w-full rounded-full bg-terracotta-500 px-6 py-3.5 text-base font-semibold text-white hover:bg-terracotta-600">M’inscrire</button>
            </form>
        </section>
    @endif
</div>
@endsection
