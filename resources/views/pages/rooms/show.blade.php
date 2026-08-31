@extends('layouts.app')

@section('title', $room['name'])
@section('description', \Illuminate\Support\Str::limit(strip_tags($room['description']), 150))
@section('og_image', $room['images'][0])

@section('content')

    <section class="bg-nuit-900">
        <x-container class="py-6">
            <nav class="flex items-center gap-2 text-xs text-white/60" aria-label="Fil d'Ariane">
                <a href="{{ route('home') }}" class="hover:text-white">Accueil</a><span>/</span>
                <a href="{{ route('rooms.index') }}" class="hover:text-white">Chambres & Suites</a><span>/</span>
                <span class="text-white/85">{{ $room['name'] }}</span>
            </nav>
        </x-container>
    </section>

    <section class="grid gap-1 sm:grid-cols-2">
        <img src="{{ $room['images'][0] }}" alt="{{ $room['name'] }}" width="960" height="720"
             fetchpriority="high" decoding="async" class="h-full max-h-[520px] w-full object-cover">
        <img src="{{ $room['images'][1] ?? $room['images'][0] }}" alt="{{ $room['name'] }} — détail"
             width="960" height="720" loading="lazy" decoding="async" class="h-full max-h-[520px] w-full object-cover">
    </section>

    <section class="py-16 sm:py-20">
        <x-container class="grid gap-12 lg:grid-cols-[1.4fr_1fr] lg:items-start">
            <div class="flex flex-col gap-6">
                <div class="flex flex-col gap-3">
                    <span class="eyebrow">{{ config('rooms.categories')[$room['category']] ?? $room['category'] }}</span>
                    <h1 class="text-4xl text-nuit-900 sm:text-5xl">{{ $room['name'] }}</h1>
                    <p class="text-lg leading-relaxed text-ink-soft">{{ $room['short'] }}</p>
                </div>

                <dl class="grid grid-cols-2 gap-4 rounded-2xl bg-sable-100 p-6 sm:grid-cols-4">
                    @foreach ([
                        'Surface' => $room['size'] . ' m²',
                        'Capacité' => $room['capacity'] . ' pers.',
                        'Couchage' => $room['bed'],
                        'Vue' => $room['view'],
                    ] as $k => $v)
                        <div>
                            <dt class="text-xs uppercase tracking-wider text-nuit-400">{{ $k }}</dt>
                            <dd class="mt-1 text-sm font-medium text-nuit-900">{{ $v }}</dd>
                        </div>
                    @endforeach
                </dl>

                <p class="leading-relaxed text-ink-soft">{{ $room['description'] }}</p>

                <div>
                    <h2 class="text-xl text-nuit-900">Équipements</h2>
                    <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                        @foreach ($room['amenities'] as $a)
                            <li class="flex items-center gap-2 text-sm text-nuit-700">
                                <svg class="h-4 w-4 shrink-0 text-terracotta-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.9a1 1 0 1 1 1.4-1.4l3.1 3.1 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                                {{ $a }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <aside class="lg:sticky lg:top-28">
                <div class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-luxe">
                    <div>
                        <span class="block text-xs uppercase tracking-wider text-nuit-400">À partir de</span>
                        <span class="font-display text-3xl font-semibold text-nuit-900">{{ money($room['price']) }}</span>
                        <span class="text-sm text-nuit-400"> / nuit · taxes en sus</span>
                    </div>
                    <p class="text-xs text-nuit-500">Annulation gratuite jusqu'à 48 h avant l'arrivée sur le tarif flexible.</p>
                    <x-button :href="route('booking.start', ['room' => $room['slug']])" variant="primary" size="lg" class="w-full">
                        Vérifier les disponibilités
                    </x-button>
                    <div class="flex gap-2">
                        <x-button :href="'tel:'.config('hotel.contact.phone_href')" variant="outline" size="sm" class="flex-1">Appeler</x-button>
                        <x-button :href="'https://wa.me/'.config('hotel.contact.whatsapp_href')" variant="outline" size="sm" class="flex-1">WhatsApp</x-button>
                    </div>
                </div>
            </aside>
        </x-container>
    </section>

    <section class="bg-sable-100 py-16 sm:py-20">
        <x-container>
            <x-section-heading eyebrow="À découvrir aussi" title="D'autres hébergements" class="reveal" />
            <div class="mt-10 grid gap-6 md:grid-cols-2">
                @foreach ($others as $room)
                    <div class="reveal"><x-room-card :room="$room" /></div>
                @endforeach
            </div>
        </x-container>
    </section>

@endsection
