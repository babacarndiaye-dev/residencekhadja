@props([
    'eyebrow' => null,
    'title' => null,
    'align' => 'left',
    'tone' => 'dark',
])

@php
    $alignCls = $align === 'center' ? 'text-center mx-auto items-center' : 'text-left items-start';
    $titleCls = $tone === 'light' ? 'text-white' : 'text-nuit-900';
    $introCls = $tone === 'light' ? 'text-white/75' : 'text-ink-soft';
@endphp

<div class="flex flex-col gap-4 {{ $alignCls }} max-w-2xl">
    @if ($eyebrow)
        <span class="eyebrow flex items-center gap-3">
            <span class="inline-block h-px w-8 bg-laiton-400"></span>{{ $eyebrow }}
        </span>
    @endif

    @if ($title)
        <h2 class="text-3xl sm:text-4xl lg:text-[2.75rem] leading-[1.1] {{ $titleCls }}">{{ $title }}</h2>
    @endif

    @if (trim($slot) !== '')
        <p class="text-base sm:text-lg leading-relaxed {{ $introCls }}">{{ $slot }}</p>
    @endif
</div>
