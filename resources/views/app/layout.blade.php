<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'Mon séjour') · {{ config('guestapp.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="manifest" href="{{ route('guest.manifest') }}">
    <meta name="theme-color" content="{{ config('guestapp.theme_color') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="{{ asset('img/app-icon-192.png') }}">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('img/app-icon-192.png') }}">
    <link rel="apple-touch-icon" sizes="512x512" href="{{ asset('img/app-icon-512.png') }}">
    @include('partials.fonts')
    @include('partials.material-symbols')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body data-surface="site" class="min-h-screen bg-sable-100 text-ink antialiased">

<div class="mx-auto flex min-h-screen max-w-md flex-col bg-sable-50">

    @hasSection('bare')
        @yield('bare')
    @else
        <header class="sticky top-0 z-20 flex items-center justify-between gap-3 bg-nuit-900 px-4 py-3 text-white">
            <div class="flex items-center gap-2.5">
                <img src="{{ \App\Support\Branding::logo() }}" alt="" class="h-8 w-8">
                <div class="leading-tight">
                    <p class="font-display text-sm font-semibold tracking-wide">{{ config('guestapp.name') }}</p>
                    @isset($stay)<p class="text-[0.7rem] text-white/55">Chambre {{ $stay->room->number ?? '—' }} · {{ $stay->guest->first_name }}</p>@endisset
                </div>
            </div>
            <form method="POST" action="{{ route('guest.logout') }}">
                @csrf
                <button class="flex items-center gap-1 rounded-full border border-white/20 px-3 py-1 text-xs text-white/70 hover:bg-white/10">
                    <x-icon name="logout" :size="15" /> Quitter
                </button>
            </form>
        </header>

        @if (session('status'))
            <div class="mx-4 mt-3 flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-sm text-emerald-900">
                <x-icon name="check_circle" :size="18" fill class="mt-0.5 text-emerald-600" /><span>{{ session('status') }}</span>
            </div>
        @endif
        @if ($errors->any())
            <div class="mx-4 mt-3 flex items-start gap-2 rounded-xl border border-terracotta-300 bg-terracotta-50 px-3 py-2.5 text-sm text-terracotta-800">
                <x-icon name="error" :size="18" fill class="mt-0.5 text-terracotta-600" /><span>{{ $errors->first() }}</span>
            </div>
        @endif

        <main class="flex-1 px-4 py-4 pb-24">
            @yield('content')
        </main>

        @isset($stay)
        <nav class="fixed inset-x-0 bottom-0 z-20 mx-auto flex max-w-md items-stretch justify-around border-t border-sable-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur">
            @php
                $nav = [
                    ['guest.home', 'home', 'Accueil'],
                    ['guest.stay', 'receipt_long', 'Séjour'],
                    ['guest.requests', 'concierge', 'Services'],
                    ['guest.loyalty', 'workspace_premium', 'Fidélité'],
                ];
            @endphp
            @foreach ($nav as [$route, $icon, $label])
                @php $on = request()->routeIs($route); @endphp
                <a href="{{ route($route) }}" @class(['relative flex flex-1 flex-col items-center gap-0.5 py-2 text-[0.62rem] font-semibold', 'text-terracotta-600' => $on, 'text-nuit-400' => ! $on])>
                    <span @class(['absolute inset-x-4 top-0 h-0.5 rounded-full bg-terracotta-500' => $on])></span>
                    <x-icon name="{{ $icon }}" :size="22" :fill="$on" />
                    {{ $label }}
                </a>
            @endforeach
        </nav>
        @endisset
    @endif
</div>

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            @if (config('guestapp.service_worker'))
                navigator.serviceWorker.register('{{ route('guest.sw') }}', { scope: '/app' }).catch(() => {});
            @else
                // SW désactivé : on purge toute installation antérieure (cache hors-ligne cassé).
                navigator.serviceWorker.getRegistrations().then((rs) => rs.forEach((r) => r.unregister()));
                if (window.caches) caches.keys().then((ks) => ks.forEach((k) => caches.delete(k)));
            @endif
        });
    }
</script>
</body>
</html>
