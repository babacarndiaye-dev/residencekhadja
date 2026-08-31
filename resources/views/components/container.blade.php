@props(['size' => 'default'])

@php
    $max = match ($size) {
        'narrow' => 'max-w-3xl',
        'wide'   => 'max-w-[88rem]',
        default  => 'max-w-7xl',
    };
@endphp

<div {{ $attributes->class("mx-auto $max px-5 sm:px-6 lg:px-8") }}>
    {{ $slot }}
</div>
