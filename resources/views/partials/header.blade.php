@php $nav = config('navigation.primary'); $hotel = config('hotel'); @endphp

<header x-data="{ open: false, scrolled: false }"
        x-on:scroll.window="scrolled = window.scrollY > 30"
        x-on:keydown.escape.window="open = false"
        class="sticky top-0 z-50">

    {{-- Barre utilitaire --}}
    <div class="hidden bg-nuit-900 text-white/80 lg:block">
        <x-container class="flex items-center justify-between py-2 text-xs">
            <p class="tracking-wide">{{ $hotel['baseline'] }} — {{ $hotel['city'] }}, {{ $hotel['country'] }}</p>
            <div class="flex items-center gap-5">
                <a href="tel:{{ $hotel['contact']['phone_href'] }}" class="hover:text-white">{{ $hotel['contact']['phone'] }}</a>
                <a href="https://wa.me/{{ $hotel['contact']['whatsapp_href'] }}" class="hover:text-white">WhatsApp</a>
                <a href="{{ route('booking.start') }}" class="hover:text-white">{{ $hotel['loyalty']['name'] }}</a>
            </div>
        </x-container>
    </div>

    {{-- Barre principale --}}
    <div :class="scrolled ? 'bg-white border-b border-sable-200' : 'bg-white border-b border-transparent'"
         class="border-b border-sable-200 transition-colors">
        <x-container class="flex items-center justify-between gap-4 py-3.5">
            <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="{{ $hotel['name'] }} — accueil">
                <img src="{{ \App\Support\Branding::logo() }}" alt="" width="44" height="44" class="h-11 w-11" fetchpriority="high">
                <span class="flex flex-col leading-tight">
                    <span class="font-display text-lg font-semibold text-nuit-900">Résidence Khadija</span>
                    <span class="text-[0.65rem] uppercase tracking-[0.2em] text-terracotta-600">Hôtel · Thiès</span>
                </span>
            </a>

            <nav class="hidden items-center gap-7 xl:flex" aria-label="Navigation principale">
                @foreach ($nav as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a href="{{ route($item['route']) }}"
                       @if($active) aria-current="page" @endif
                       class="text-sm font-medium transition-colors {{ $active ? 'text-terracotta-600' : 'text-nuit-700 hover:text-terracotta-600' }}">
                        {{ __($item['label']) }}
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                <x-button :href="route('booking.start')" variant="primary" size="sm" class="hidden sm:inline-flex">
                    Réserver
                </x-button>
                <button type="button" x-on:click="open = !open"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-nuit-200 text-nuit-800 xl:hidden"
                        :aria-expanded="open" aria-label="Ouvrir le menu">
                    <svg x-show="!open" class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 5h14M3 10h14M3 15h14" stroke-linecap="round"/></svg>
                    <svg x-show="open" x-cloak class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 5 10 10M15 5 5 15" stroke-linecap="round"/></svg>
                </button>
            </div>
        </x-container>
    </div>

    {{-- Menu mobile --}}
    <div x-show="open" x-cloak x-transition.opacity
         class="fixed inset-0 top-0 z-40 bg-nuit-900/40 xl:hidden" x-on:click="open = false"></div>
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-y-4 opacity-0"
         class="absolute inset-x-0 z-40 origin-top border-b border-sable-200 bg-white xl:hidden">
        <x-container class="flex flex-col gap-1 py-4">
            @foreach ($nav as $item)
                <a href="{{ route($item['route']) }}"
                   class="rounded-lg px-3 py-3 text-base font-medium text-nuit-800 hover:bg-sable-100 {{ request()->routeIs($item['route']) ? 'text-terracotta-600' : '' }}">
                    {{ __($item['label']) }}
                </a>
            @endforeach
            <div class="mt-2 flex flex-col gap-2 border-t border-sable-200 pt-3">
                <x-button :href="route('booking.start')" variant="primary" size="md">Réserver un séjour</x-button>
                <div class="flex gap-2">
                    <x-button :href="'tel:'.$hotel['contact']['phone_href']" variant="outline" size="sm" class="flex-1">Appeler</x-button>
                    <x-button :href="'https://wa.me/'.$hotel['contact']['whatsapp_href']" variant="outline" size="sm" class="flex-1">WhatsApp</x-button>
                </div>
            </div>
        </x-container>
    </div>
</header>
