@props(['offer'])

<article class="group flex flex-col overflow-hidden rounded-2xl border border-sable-200 bg-white">
    <div class="relative aspect-[16/10] overflow-hidden">
        <img
            src="{{ $offer['image'] }}"
            alt="{{ $offer['name'] }}"
            width="800" height="500" loading="lazy" decoding="async"
            class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
        >
        <span class="absolute right-4 top-4 rounded-full bg-terracotta-500 px-3 py-1 text-xs font-bold uppercase tracking-wider text-white">
            {{ $offer['badge'] }}
        </span>
    </div>

    <div class="flex flex-1 flex-col gap-4 p-6">
        <div class="flex flex-col gap-1">
            <span class="eyebrow">{{ $offer['audience'] }}</span>
            <h3 class="text-2xl text-nuit-900">{{ $offer['name'] }}</h3>
        </div>
        <p class="text-sm leading-relaxed text-ink-soft">{{ $offer['summary'] }}</p>

        <ul class="flex flex-col gap-2 text-sm text-nuit-700">
            @foreach ($offer['includes'] as $line)
                <li class="flex gap-2">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-terracotta-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.9a1 1 0 1 1 1.4-1.4l3.1 3.1 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ $line }}</span>
                </li>
            @endforeach
        </ul>

        <p class="text-xs text-nuit-400">{{ $offer['terms'] }}</p>

        <div class="mt-auto pt-2">
            <x-button :href="route('booking.start', ['promo' => \Illuminate\Support\Str::of($offer['name'])->slug()])" variant="dark" size="sm" class="w-full">
                Profiter de l'offre
            </x-button>
        </div>
    </div>
</article>
