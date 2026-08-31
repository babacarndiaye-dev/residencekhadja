<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <meta http-equiv="refresh" content="25">
    <title>Plan de salle · {{ $venue->name }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @include('partials.material-symbols')
    @include('partials.fonts')
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-sable-100 text-nuit-900">

<header class="flex items-center justify-between gap-4 border-b border-sable-300 bg-white px-4 py-2.5">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.pos.index') }}" class="flex items-center gap-1.5 rounded-md border border-sable-300 px-3 py-1.5 text-sm font-semibold text-nuit-700 hover:bg-sable-50">
            <x-icon name="arrow_back" size="18" /> Caisse
        </a>
        <h1 class="font-display text-lg font-semibold">Plan de salle</h1>
    </div>
    <div class="flex items-center gap-3 text-xs text-nuit-500">
        @if ($venues->count() > 1)
            <select onchange="location.href='{{ route('admin.pos.floor') }}?venue='+this.value" class="rounded-md border border-sable-300 bg-white px-2.5 py-1.5">
                @foreach ($venues as $v)<option value="{{ $v->id }}" @selected($v->id === $venue->id)>{{ $v->name }}</option>@endforeach
            </select>
        @endif
        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> Libre</span>
        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-terracotta-500"></span> Occupée</span>
    </div>
</header>

<main class="mx-auto max-w-6xl p-4">
    @forelse ($zones as $zone => $tables)
        <section class="mb-6">
            <h2 class="mb-2 text-xs font-semibold uppercase tracking-[0.15em] text-nuit-400">{{ $zone }}</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                @foreach ($tables as $t)
                    @php
                        $free = $t['status'] === 'libre';
                        $href = $free
                            ? route('admin.pos.register', ['venue' => $venue->id, 'table' => $t['id']])
                            : route('admin.pos.register', ['venue' => $venue->id, 'resume' => $t['order_id']]);
                    @endphp
                    <a href="{{ $href }}"
                       class="flex flex-col rounded-lg border bg-white p-3 transition-colors {{ $free ? 'border-emerald-200 hover:border-emerald-400' : 'border-terracotta-300 bg-terracotta-50 hover:border-terracotta-500' }}">
                        <div class="flex items-center justify-between">
                            <span class="font-display text-base font-semibold">{{ $t['label'] }}</span>
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $free ? 'bg-emerald-500' : 'bg-terracotta-500' }}"></span>
                        </div>
                        <span class="text-[0.7rem] text-nuit-400">{{ $t['seats'] ? $t['seats'].' couverts' : '' }}</span>
                        @unless ($free)
                            <div class="mt-1.5 flex flex-col gap-0.5 text-xs text-nuit-600">
                                <span>{{ $t['covers'] }} pers.@if ($t['server']) · {{ $t['server'] }}@endif</span>
                                <span class="font-semibold text-nuit-900">{{ money($t['total']) }}</span>
                                <span class="text-[0.7rem] text-nuit-400">depuis {{ $t['since'] }}</span>
                            </div>
                        @endunless
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <p class="mt-16 text-center text-sm text-nuit-400">Aucune table configurée pour ce point de vente. Ajoutez-en dans <a href="{{ route('admin.qr.index') }}" class="text-terracotta-600 hover:underline">QR codes</a>.</p>
    @endforelse
</main>

</body>
</html>
