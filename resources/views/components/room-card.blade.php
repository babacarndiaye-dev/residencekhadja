@props(['room'])

<article class="group flex flex-col overflow-hidden rounded-2xl border border-sable-200 bg-white transition-colors hover:border-nuit-300">
    <a href="{{ route('rooms.show', $room['slug']) }}" class="relative block aspect-[4/3] overflow-hidden">
        <img
            src="{{ $room['images'][0] }}"
            alt="{{ $room['name'] }}"
            width="800" height="600" loading="lazy" decoding="async"
            class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
        >
        <span class="absolute left-4 top-4 rounded-full bg-white/95 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-nuit-800">
            {{ config('rooms.categories')[$room['category']] ?? $room['category'] }}
        </span>
    </a>

    <div class="flex flex-1 flex-col gap-4 p-6">
        <div class="flex flex-col gap-2">
            <h3 class="text-2xl text-nuit-900">
                <a href="{{ route('rooms.show', $room['slug']) }}" class="hover:text-terracotta-600">{{ $room['name'] }}</a>
            </h3>
            <p class="text-sm leading-relaxed text-ink-soft">{{ $room['short'] }}</p>
        </div>

        <ul class="flex flex-wrap gap-x-5 gap-y-1 text-xs text-nuit-500">
            <li>{{ $room['size'] }} m²</li>
            <li>{{ $room['capacity'] }} pers. max</li>
            <li>{{ $room['view'] }}</li>
        </ul>

        <div class="mt-auto flex items-end justify-between border-t border-sable-200 pt-4">
            <div>
                <span class="block text-[0.7rem] uppercase tracking-wider text-nuit-400">À partir de</span>
                <span class="text-lg font-semibold text-nuit-900">{{ money($room['price']) }}</span>
                <span class="text-xs text-nuit-400">/ nuit</span>
            </div>
            <x-button :href="route('booking.start', ['room' => $room['slug']])" variant="outline" size="sm">
                Réserver
            </x-button>
        </div>
    </div>
</article>
