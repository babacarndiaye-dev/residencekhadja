@php $hotel = config('hotel'); $nav = config('navigation.primary'); @endphp

<footer class="mt-24 bg-nuit-900 text-white/75">
    <div class="rule-teranga opacity-60"></div>

    <x-container class="grid gap-12 py-16 md:grid-cols-2 lg:grid-cols-4">
        <div class="flex flex-col gap-4">
            <div class="flex items-center gap-3">
                <img src="{{ \App\Support\Branding::logoMono() }}" alt="" width="40" height="40" class="h-10 w-10" loading="lazy">
                <span class="font-display text-lg font-semibold text-white">Hôtel Résidence Khadija</span>
            </div>
            <p class="text-sm leading-relaxed">{{ __('site.footer.tagline') }}</p>
            <div class="mt-2 flex gap-3">
                @foreach ($hotel['social'] as $name => $url)
                    <a href="{{ $url }}" rel="noopener" target="_blank"
                       class="flex h-9 w-9 items-center justify-center rounded-full border border-white/15 text-white/70 hover:border-terracotta-400 hover:text-white"
                       aria-label="{{ ucfirst($name) }}">
                        <span class="text-xs font-semibold">{{ strtoupper(substr($name, 0, 2)) }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="flex flex-col gap-3">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-white">{{ __('site.footer.explore') }}</h3>
            <ul class="flex flex-col gap-2 text-sm">
                @foreach ($nav as $item)
                    <li><a href="{{ route($item['route']) }}" class="hover:text-white">{{ __($item['label']) }}</a></li>
                @endforeach
                <li><a href="{{ route('booking.start') }}" class="hover:text-white">Réserver</a></li>
            </ul>
        </div>

        <div class="flex flex-col gap-3">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-white">{{ __('site.footer.contact') }}</h3>
            <address class="flex flex-col gap-2 text-sm not-italic">
                <span>{{ $hotel['contact']['address_line'] }}<br>{{ $hotel['contact']['address_area'] }}<br>{{ $hotel['contact']['address_country'] }}</span>
                <a href="tel:{{ $hotel['contact']['phone_href'] }}" class="hover:text-white">{{ $hotel['contact']['phone'] }}</a>
                <a href="mailto:{{ $hotel['contact']['email'] }}" class="hover:text-white">{{ $hotel['contact']['email'] }}</a>
                <a href="{{ $hotel['geo']['directions_url'] }}" target="_blank" rel="noopener" class="hover:text-white">{{ __('site.cta.directions') }}</a>
            </address>
        </div>

        <div class="flex flex-col gap-3">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-white">{{ $hotel['loyalty']['name'] }}</h3>
            <p class="text-sm leading-relaxed">{{ $hotel['loyalty']['pitch'] }}</p>
            <ul class="mt-1 flex flex-wrap gap-2 text-xs">
                @foreach ($hotel['loyalty']['levels'] as $level)
                    <li class="rounded-full border border-white/15 px-2.5 py-1">{{ $level }}</li>
                @endforeach
            </ul>
        </div>
    </x-container>

    <div class="border-t border-white/10">
        <x-container class="flex flex-col items-center justify-between gap-3 py-6 text-xs text-white/50 sm:flex-row">
            <p>&copy; <span data-year>{{ date('Y') }}</span> {{ $hotel['name'] }}. {{ __('site.footer.rights') }}</p>
            <div class="flex gap-5">
                <a href="{{ route('contact') }}" class="hover:text-white">{{ __('site.footer.legal') }}</a>
                <a href="{{ route('contact') }}" class="hover:text-white">{{ __('site.footer.privacy') }}</a>
                <a href="{{ route('sitemap') }}" class="hover:text-white">Plan du site</a>
            </div>
        </x-container>
    </div>
</footer>
