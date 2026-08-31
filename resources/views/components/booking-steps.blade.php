@props(['current' => 'search', 'tone' => 'plain']) {{-- plain = sur fond clair · light = sur fond sombre --}}

@php
    $steps = [
        'search'  => ['n' => 1, 'label' => 'Dates',          'route' => 'booking.start'],
        'room'    => ['n' => 2, 'label' => 'Chambre',        'route' => 'booking.rooms'],
        'extras'  => ['n' => 3, 'label' => 'Options',         'route' => 'booking.extras'],
        'summary' => ['n' => 4, 'label' => 'Récapitulatif',   'route' => 'booking.summary'],
        'done'    => ['n' => 5, 'label' => 'Confirmation',    'route' => null],
    ];
    $currentN = $steps[$current]['n'] ?? 1;
    $dark = $tone === 'light';
@endphp

<ol {{ $attributes->class('flex flex-wrap items-center gap-x-2 gap-y-3 text-sm') }} aria-label="Étapes de la réservation">
    @foreach ($steps as $key => $step)
        @php
            $state = $step['n'] < $currentN ? 'done' : ($step['n'] === $currentN ? 'current' : 'todo');
            $dot = match ($state) {
                'done'    => 'bg-terracotta-500 text-white border-terracotta-500',
                'current' => $dark ? 'bg-white text-terracotta-600 border-white' : 'bg-white text-terracotta-600 border-terracotta-500',
                default   => $dark ? 'bg-white/10 text-white/50 border-white/25' : 'bg-white text-nuit-300 border-nuit-200',
            };
            $text = $dark
                ? ($state === 'todo' ? 'text-white/45' : 'text-white')
                : ($state === 'todo' ? 'text-nuit-300' : 'text-nuit-800');
            $bar = $dark ? 'bg-white/25' : 'bg-nuit-200';
            $clickable = $state === 'done' && $step['route'];
        @endphp
        <li class="flex items-center gap-2">
            @if ($clickable)
                <a href="{{ route($step['route']) }}" class="flex items-center gap-2 group">
            @else
                <span class="flex items-center gap-2">
            @endif
                <span class="flex h-7 w-7 items-center justify-center rounded-full border text-xs font-bold {{ $dot }}">
                    @if ($state === 'done')
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.9a1 1 0 1 1 1.4-1.4l3.1 3.1 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                    @else
                        {{ $step['n'] }}
                    @endif
                </span>
                <span class="{{ $text }} {{ $clickable ? 'group-hover:text-terracotta-300' : '' }} font-medium">{{ $step['label'] }}</span>
            @if ($clickable)</a>@else</span>@endif

            @unless ($loop->last)
                <span class="mx-1 hidden h-px w-6 {{ $bar }} sm:inline-block"></span>
            @endunless
        </li>
    @endforeach
</ol>
