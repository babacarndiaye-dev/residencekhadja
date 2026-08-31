<!doctype html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'Back-office') · PMS Khadija</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @include('partials.fonts')
    @include('partials.material-symbols')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-surface="admin" class="min-h-screen bg-sable-100 text-ink antialiased">
@php
    use Illuminate\Support\Str;

    $u = auth()->user();

    // Menu regroupé : « item » = lien direct, « group » = sous-menu repliable.
    $sections = [
        ['item' => ['route' => 'admin.dashboard', 'label' => 'Tableau de bord', 'icon' => 'dashboard', 'roles' => ['admin','direction','reception','housekeeping','restaurant']]],

        ['group' => 'Réception', 'icon' => 'concierge', 'items' => [
            ['route' => 'admin.reservations.index',  'label' => 'Réservations',       'roles' => ['admin','direction','reception']],
            ['route' => 'admin.rooms.index',         'label' => 'Plan des chambres',  'roles' => ['admin','direction','reception','housekeeping']],
            ['route' => 'admin.rooms.manage',        'label' => 'Gestion des chambres', 'roles' => ['admin','direction','reception']],
            ['route' => 'admin.guest_requests.index','label' => 'Demandes clients',   'roles' => ['admin','direction','reception','housekeeping','maintenance']],
            ['route' => 'admin.messages.index',      'label' => 'Messages',           'roles' => ['admin','direction','reception']],
        ]],

        ['group' => 'Restauration', 'icon' => 'restaurant', 'items' => [
            ['route' => 'admin.pos.index',     'label' => 'Caisse',           'roles' => ['admin','restaurant','reception']],
            ['route' => 'admin.pos.floor',     'label' => 'Plan de salle',    'roles' => ['admin','restaurant','reception']],
            ['route' => 'admin.pos.room_service', 'label' => 'Room service',  'roles' => ['admin','restaurant','reception']],
            ['route' => 'admin.pos.breakfast', 'label' => 'Petit-déjeuner',   'roles' => ['admin','restaurant','reception']],
            ['route' => 'admin.pos.reports',   'label' => 'Rapports caisse',  'roles' => ['admin','restaurant','reception','direction']],
            ['route' => 'admin.kds.index',     'label' => 'Cuisine (KDS)',    'roles' => ['admin','restaurant','reception']],
            ['route' => 'admin.orders.index',  'label' => 'Commandes',        'roles' => ['admin','restaurant','reception']],
            ['route' => 'admin.service.index', 'label' => 'Service en salle', 'roles' => ['admin','restaurant','reception']],
            ['route' => 'admin.menu.index',    'label' => 'Carte',            'roles' => ['admin','direction','restaurant']],
            ['route' => 'admin.qr.index',      'label' => 'QR codes',         'roles' => ['admin','direction','restaurant']],
            ['route' => 'admin.pos.pin.edit',  'label' => 'PIN caisse (manager)', 'roles' => ['admin','direction']],
        ]],

        ['group' => 'Étages & maintenance', 'icon' => 'cleaning_services', 'items' => [
            ['route' => 'admin.housekeeping.index',  'label' => 'Ménage',         'roles' => ['admin','direction','reception','housekeeping']],
            ['route' => 'admin.housekeeping.mobile', 'label' => 'Ménage terrain', 'roles' => ['admin','direction','reception','housekeeping']],
            ['route' => 'admin.maintenance.tickets', 'label' => 'Maintenance',    'roles' => ['admin','direction','reception','maintenance']],
            ['route' => 'admin.pool.index',          'label' => 'Piscine',        'roles' => ['admin','direction','reception']],
        ]],

        ['group' => 'Économat', 'icon' => 'inventory_2', 'items' => [
            ['route' => 'admin.stock.index',     'label' => 'Stocks', 'roles' => ['admin','direction','reception','stock']],
            ['route' => 'admin.purchases.index', 'label' => 'Achats', 'roles' => ['admin','direction','reception','stock']],
        ]],

        ['group' => 'Finance & comptabilité', 'icon' => 'payments', 'items' => [
            ['route' => 'admin.payments.index',    'label' => 'Paiements',    'roles' => ['admin','direction','finance','reception']],
            ['route' => 'admin.finance.dashboard', 'label' => 'Trésorerie',  'roles' => ['admin','direction','finance']],
            ['route' => 'admin.finance.cash',      'label' => 'Caisses',     'roles' => ['admin','finance','reception']],
            ['route' => 'admin.accounting.index',  'label' => 'Comptabilité', 'roles' => ['admin','direction','finance']],
        ]],

        ['group' => 'Clients & fidélité', 'icon' => 'groups', 'items' => [
            ['route' => 'admin.guests.index',    'label' => 'Clients',    'roles' => ['admin','direction','reception','marketing']],
            ['route' => 'admin.crm.dashboard',   'label' => 'CRM 360°',   'roles' => ['admin','direction','reception','marketing']],
            ['route' => 'admin.crm.loyalty',     'label' => 'Fidélité',   'roles' => ['admin','direction','reception','marketing']],
            ['route' => 'admin.satisfaction.index', 'label' => 'Satisfaction', 'roles' => ['admin','direction','reception','marketing']],
            ['route' => 'admin.marketing.index', 'label' => 'Marketing',  'roles' => ['admin','direction','marketing']],
        ]],

        ['group' => 'Commercial & événements', 'icon' => 'handshake', 'items' => [
            ['route' => 'admin.events.pipeline', 'label' => 'Pipeline commercial',  'roles' => ['admin','direction','commercial']],
            ['route' => 'admin.events.index',    'label' => 'Événements',          'roles' => ['admin','direction','commercial']],
            ['route' => 'admin.salles.index',    'label' => 'Salles & séminaires', 'roles' => ['admin','direction','commercial','reception']],
        ]],

        ['group' => 'Distribution & canaux', 'icon' => 'hub', 'items' => [
            ['route' => 'admin.distribution.index', 'label' => 'Channel Manager', 'roles' => ['admin','direction','reception']],
        ]],

        ['group' => 'RH & paie', 'icon' => 'badge', 'items' => [
            ['route' => 'admin.hr.dashboard',     'label' => 'Effectif',  'roles' => ['admin','direction','rh']],
            ['route' => 'admin.hr.schedule',      'label' => 'Planning',  'roles' => ['admin','direction','rh']],
            ['route' => 'admin.hr.attendance',    'label' => 'Pointage',  'roles' => ['admin','direction','rh']],
            ['route' => 'admin.hr.payroll.index', 'label' => 'Paie',      'roles' => ['admin','direction','rh']],
        ]],

        ['group' => 'Pilotage & réglages', 'icon' => 'insights', 'items' => [
            ['route' => 'admin.bi.dashboard',       'label' => 'Décisionnel (BI)',  'roles' => ['admin','direction','finance']],
            ['route' => 'admin.bi.assistant',       'label' => 'KHADJA AI',         'roles' => ['admin','direction','finance']],
            ['route' => 'pilotage.home',            'label' => 'Pilotage (mobile)', 'roles' => ['admin','direction']],
            ['route' => 'admin.settings.index',     'label' => 'Paramètres',        'roles' => ['admin','direction']],
            ['route' => 'admin.site_settings.edit', 'label' => 'Réglages du site',  'roles' => ['admin','direction']],
            ['route' => 'admin.splash.edit',        'label' => 'Écran d’accueil',   'roles' => ['admin','direction']],
            ['route' => 'admin.audit.index',        'label' => 'Journal d’audit',   'roles' => ['admin']],
        ]],
    ];

    // Résolution du lien actif : UN SEUL à la fois, le plus spécifique.
    // Pour chaque lien on calcule une « base » = son nom de route sans un « .index » /
    // « .dashboard » final (tant qu'il reste au moins deux segments). Un lien correspond
    // si la route courante est cette base ou en descend ; le lien à la base la plus longue
    // l'emporte. À défaut, on retombe sur la section (préfixe avant le dernier segment).
    $current = request()->route()?->getName() ?? '';
    $navScore = function (string $route) use ($current): int {
        $base = Str::endsWith($route, ['.index', '.dashboard']) && Str::contains(Str::beforeLast($route, '.'), '.')
            ? Str::beforeLast($route, '.')
            : $route;
        if ($current === $base || Str::startsWith($current, $base.'.')) {
            return 100 + Str::substrCount($base, '.');
        }
        $section = Str::beforeLast($route, '.');
        if (Str::contains($section, '.') && Str::startsWith($current, $section.'.')) {
            return Str::substrCount($section, '.');
        }
        return 0;
    };
    $navRoutes = collect($sections)->flatMap(fn ($s) => isset($s['item'])
        ? [$s['item']['route']]
        : array_column($s['items'], 'route'));
    $navBest = $navRoutes->sortByDesc($navScore)->first();
    $activeRoute = ($navBest && $navScore($navBest) > 0) ? $navBest : null;

    $isActive = fn (string $route) => $route === $activeRoute;

    // Groupe contenant le lien actif → ouvert d'office (accordéon : un seul groupe ouvert).
    $activeGroupSlug = null;
    foreach ($sections as $s) {
        if (isset($s['group']) && collect($s['items'])->contains('route', $activeRoute)) {
            $activeGroupSlug = Str::slug($s['group']);
            break;
        }
    }

    // Notifications in-app (cloche du bandeau).
    $notifUnread = $u->unreadNotifications()->count();
    $notifItems = $notifUnread ? $u->unreadNotifications()->latest()->limit(8)->get() : collect();
@endphp

<div x-data="{
        open: false,
        navGroup: @js($activeGroupSlug),
        initNavGroup() {
            if (this.navGroup) return;
            try { const s = localStorage.getItem('nav:group'); if (s) this.navGroup = s; } catch (e) {}
        },
     }" x-init="initNavGroup()" class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside class="hidden w-60 shrink-0 flex-col border-r border-sable-300 bg-white lg:flex">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 border-b border-sable-200 px-5 py-4">
            <img src="{{ \App\Support\Branding::logo() }}" alt="" width="32" height="32" class="h-8 w-8">
            <span class="font-display text-base font-semibold text-nuit-900">PMS Khadija</span>
        </a>
        <nav class="flex flex-1 flex-col gap-0.5 overflow-y-auto p-3">
            @include('admin.partials.nav', ['sections' => $sections, 'isActive' => $isActive, 'u' => $u])
        </nav>
        <div class="border-t border-sable-200 p-3 text-xs text-nuit-500">
            <p class="font-semibold text-nuit-800">{{ $u->name }}</p>
            <p>{{ $u->roleLabel() }}</p>
            <a href="{{ route('home') }}" class="mt-2 inline-block hover:text-terracotta-600">← Voir le site</a>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        {{-- Topbar --}}
        <header class="flex items-center justify-between gap-4 border-b border-sable-300 bg-white px-4 py-3 sm:px-6">
            <div class="flex items-center gap-3">
                <button x-on:click="open = !open" class="text-nuit-800 lg:hidden" aria-label="Menu">
                    <x-icon name="menu" size="24" />
                </button>
                <h1 class="font-display text-lg font-semibold text-nuit-900">@yield('title', 'Back-office')</h1>
            </div>

            <div class="flex items-center gap-3">
                <div x-data="{ bell: false }" class="relative">
                    <button x-on:click="bell = !bell" class="relative rounded-lg p-1.5 text-nuit-600 hover:bg-sable-100" aria-label="Notifications">
                        <x-icon name="notifications" size="20" />
                        @if ($notifUnread)
                            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-terracotta-500 px-1 text-[10px] font-bold text-white">{{ $notifUnread > 9 ? '9+' : $notifUnread }}</span>
                        @endif
                    </button>

                    <div x-show="bell" x-cloak x-transition x-on:click.outside="bell = false"
                         class="absolute right-0 z-20 mt-2 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-sable-200 bg-white shadow-luxe">
                        <div class="flex items-center justify-between border-b border-sable-200 px-4 py-2.5">
                            <span class="text-sm font-semibold text-nuit-900">Notifications</span>
                            @if ($notifUnread)
                                <form method="POST" action="{{ route('admin.notifications.read_all') }}">
                                    @csrf
                                    <button class="text-xs font-medium text-terracotta-600 hover:underline">Tout marquer lu</button>
                                </form>
                            @endif
                        </div>
                        <div class="max-h-96 overflow-y-auto">
                            @forelse ($notifItems as $n)
                                <form method="POST" action="{{ route('admin.notifications.read', $n->id) }}">
                                    @csrf
                                    <button class="flex w-full items-start gap-2.5 border-b border-sable-100 px-4 py-3 text-left hover:bg-sable-50">
                                        <span class="text-base leading-none">{{ $n->data['icon'] ?? '🔔' }}</span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-medium text-nuit-900">{{ $n->data['title'] ?? 'Notification' }}</span>
                                            @if (! empty($n->data['body']))<span class="block truncate text-xs text-nuit-500">{{ $n->data['body'] }}</span>@endif
                                            <span class="mt-0.5 block text-[11px] text-nuit-400">{{ $n->created_at->diffForHumans() }}</span>
                                        </span>
                                    </button>
                                </form>
                            @empty
                                <p class="px-4 py-8 text-center text-sm text-nuit-400">Rien de nouveau.</p>
                            @endforelse
                        </div>
                        <a href="{{ route('admin.notifications.index') }}" class="block border-t border-sable-200 px-4 py-2.5 text-center text-xs font-medium text-nuit-600 hover:bg-sable-50">Tout voir</a>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="rounded-lg border border-nuit-200 px-3 py-1.5 text-sm text-nuit-700 hover:border-terracotta-400 hover:text-terracotta-600">
                        Déconnexion
                    </button>
                </form>
            </div>
        </header>

        {{-- Mobile nav --}}
        <div x-show="open" x-cloak class="max-h-[75vh] overflow-y-auto border-b border-sable-300 bg-white p-3 lg:hidden">
            @include('admin.partials.nav', ['sections' => $sections, 'isActive' => $isActive, 'u' => $u])
        </div>

        <main class="flex-1 p-4 sm:p-6">
            @if (session('status'))
                <div class="mb-4 rounded-xl border border-laiton-200 bg-laiton-50 px-4 py-3 text-sm text-nuit-800">
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-terracotta-300 bg-terracotta-50 px-4 py-3 text-sm text-terracotta-800">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<style>[x-cloak]{display:none!important}</style>
</body>
</html>
