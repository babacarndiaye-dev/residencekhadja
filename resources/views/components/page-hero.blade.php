@props([
    'eyebrow' => null,
    'title',
    'image' => null,
    'crumb' => null,
])

<section class="relative isolate overflow-hidden bg-nuit-900">
    @if ($image)
        <img src="{{ $image }}" alt="" width="1600" height="700" fetchpriority="high" decoding="async"
             class="absolute inset-0 -z-10 h-full w-full object-cover opacity-40">
    @endif
    <div class="absolute inset-0 -z-10 bg-nuit-900/55"></div>

    <x-container class="flex flex-col gap-4 py-20 sm:py-28">
        <nav class="flex items-center gap-2 text-xs text-white/60" aria-label="Fil d'Ariane">
            <a href="{{ route('home') }}" class="hover:text-white">Accueil</a>
            <span>/</span>
            <span class="text-white/80">{{ $crumb ?? $title }}</span>
        </nav>

        @if ($eyebrow)
            <span class="eyebrow text-terracotta-300">{{ $eyebrow }}</span>
        @endif

        <h1 class="max-w-3xl text-4xl leading-[1.08] text-white sm:text-5xl lg:text-6xl">{{ $title }}</h1>

        @if (trim($slot) !== '')
            <p class="max-w-2xl text-lg leading-relaxed text-white/75">{{ $slot }}</p>
        @endif
    </x-container>
</section>
