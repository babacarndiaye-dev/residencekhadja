@props([
    'name',
    'size' => null,   // px
    'fill' => false,
    'weight' => null, // 100..700
])

@php
    $style = [];
    if ($size) { $style[] = "font-size:{$size}px"; }
    $settings = [];
    if ($fill) { $settings[] = "'FILL' 1"; }
    if ($weight) { $settings[] = "'wght' {$weight}"; }
    if ($settings) { $style[] = "font-variation-settings:".implode(',', $settings); }
@endphp

<span {{ $attributes->class('material-symbols-outlined') }}
      @if ($style) style="{{ implode(';', $style) }}" @endif
      aria-hidden="true">{{ $name }}</span>
