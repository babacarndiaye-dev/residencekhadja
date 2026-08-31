@props([
    'href' => null,
    'variant' => 'primary',
    'size' => 'md',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 font-semibold tracking-wide '
          . 'transition-colors duration-150 focus-visible:outline-2 '
          . 'focus-visible:outline-offset-2 disabled:opacity-60 disabled:pointer-events-none';

    $variants = [
        'primary'  => 'bg-terracotta-500 text-white hover:bg-terracotta-600',
        'dark'     => 'bg-nuit-900 text-white hover:bg-nuit-800',
        'outline'  => 'border border-nuit-300 text-nuit-800 hover:border-terracotta-500 hover:text-terracotta-600 bg-transparent',
        'ghost'    => 'text-nuit-800 hover:text-terracotta-600',
        'light'    => 'bg-white text-nuit-900 border border-sable-300 hover:bg-sable-100',
    ];

    $sizes = [
        'sm' => 'text-xs px-4 py-2',
        'md' => 'text-sm px-6 py-3',
        'lg' => 'text-base px-8 py-4',
    ];

    $classes = trim("$base {$variants[$variant]} {$sizes[$size]}");
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
