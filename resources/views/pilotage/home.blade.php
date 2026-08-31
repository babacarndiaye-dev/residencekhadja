<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#202628">
    <meta http-equiv="refresh" content="60">
    <link rel="manifest" href="{{ route('pilotage.manifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/app-icon-192.png') }}">
    <title>Pilotage · {{ config('hotel.short_name') }}</title>
    @include('partials.fonts')
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-nuit-900 text-white">
<script>
    if ('serviceWorker' in navigator) navigator.serviceWorker.register('{{ route('pilotage.sw') }}', { scope: '/pilotage' }).catch(() => {});
</script>

<header class="flex items-center justify-between px-5 pb-2 pt-6">
    <div>
        <p class="text-[0.7rem] uppercase tracking-[0.2em] text-white/40">{{ config('hotel.name') }}</p>
        <h1 class="font-display text-xl font-semibold">Pilotage</h1>
    </div>
    <p class="text-right text-xs text-white/50">{{ now()->translatedFormat('D d M · H:i') }}</p>
</header>

<main class="flex flex-col gap-4 p-5 pt-2">

    {{-- CA du jour --}}
    <section class="rounded-2xl bg-white/5 p-4">
        <p class="text-[0.7rem] uppercase tracking-[0.15em] text-white/40">Chiffre d'affaires caisse — aujourd'hui</p>
        <p class="mt-1 font-display text-3xl font-bold">{{ money($sales['gross']) }}</p>
        <p class="text-sm text-white/50">{{ $sales['orders'] }} tickets · panier moyen {{ money($sales['avg_check']) }}</p>
        <div class="mt-3 flex flex-wrap gap-2 text-xs">
            @foreach ($sales['by_type'] as $type => $row)
                <span class="rounded-full bg-white/10 px-2.5 py-1">{{ $saleTypes[$type] ?? $type }} · {{ money($row['total']) }}</span>
            @endforeach
        </div>
    </section>

    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-2xl bg-white/5 p-4">
            <p class="text-[0.7rem] uppercase tracking-[0.15em] text-white/40">Occupation</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ $occ['occupancy'] }} %</p>
            <p class="text-xs text-white/50">ADR {{ money($occ['adr']) }}</p>
        </div>
        <div class="rounded-2xl bg-white/5 p-4">
            <p class="text-[0.7rem] uppercase tracking-[0.15em] text-white/40">Room service en cours</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ $roomServicePending }}</p>
            <p class="text-xs text-white/50">Annulations 7 j : {{ $pms['bookings_cancelled'] }}</p>
        </div>
        <div class="rounded-2xl bg-white/5 p-4">
            <p class="text-[0.7rem] uppercase tracking-[0.15em] text-white/40">Remises / remb. (jour)</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ money($sales['discounts'] + $sales['refunds']) }}</p>
        </div>
        <div class="rounded-2xl bg-white/5 p-4">
            <p class="text-[0.7rem] uppercase tracking-[0.15em] text-white/40">Paiements en ligne 30 j</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ money($payments['online_amount']) }}</p>
            <p class="text-xs text-white/50">{{ $payments['success_rate'] }} % de succès</p>
        </div>
    </div>

    {{-- Alertes --}}
    <section class="rounded-2xl bg-white/5 p-4">
        <p class="mb-2 text-[0.7rem] uppercase tracking-[0.15em] text-white/40">Alertes ({{ $alerts->count() }})</p>
        <div class="flex flex-col gap-2">
            @forelse ($alerts as $a)
                <div class="rounded-xl bg-white/5 p-3">
                    <p class="text-sm font-semibold">{{ $a->data['title'] ?? 'Alerte' }}</p>
                    @if (! empty($a->data['body']))<p class="text-xs text-white/50">{{ $a->data['body'] }}</p>@endif
                    <p class="mt-0.5 text-[0.7rem] text-white/30">{{ $a->created_at->diffForHumans() }}</p>
                </div>
            @empty
                <p class="text-sm text-white/40">Aucune alerte en cours.</p>
            @endforelse
        </div>
    </section>

    <div class="flex flex-wrap gap-2 pt-1">
        <a href="{{ route('admin.bi.assistant') }}" class="rounded-full bg-terracotta-500 px-4 py-2 text-sm font-semibold">Poser une question à KHADJA AI</a>
        <a href="{{ route('admin.bi.dashboard') }}" class="rounded-full border border-white/20 px-4 py-2 text-sm font-semibold text-white/70">Tableau de bord complet</a>
    </div>
</main>
</body>
</html>
