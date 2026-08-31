@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'hint' => null,
    'options' => null,
])

@php $id = $attributes->get('id', $name); @endphp

<div class="flex flex-col gap-1.5">
    <label for="{{ $id }}" class="text-sm font-semibold text-nuit-800">
        {{ $label }}@if ($required)<span class="text-terracotta-600"> *</span>@endif
    </label>

    @if ($hint)
        <p class="text-xs text-nuit-400">{{ $hint }}</p>
    @endif

    @php
        // min-w-0 + max-w-full : les champs (dont type date/time sur WebKit) ne
        // débordent pas d'un parent flex/grid. text-base sur mobile = pas de zoom iOS.
        $control = 'block w-full min-w-0 max-w-full rounded-xl border bg-white px-4 py-3 text-base text-nuit-900 '
                 . 'placeholder:text-nuit-300 focus:border-terracotta-500 focus:ring-2 focus:ring-terracotta-500/20 '
                 . 'focus:outline-none transition sm:text-sm '
                 . (in_array($type, ['date', 'time', 'datetime-local', 'month', 'week'], true) ? 'appearance-none ' : '')
                 . ($errors->has($name) ? 'border-terracotta-500' : 'border-nuit-200');
    @endphp

    @if ($type === 'select' && $options)
        <select name="{{ $name }}" id="{{ $id }}" @if($required) required @endif {{ $attributes->class($control) }}>
            {{ $slot }}
        </select>
    @elseif ($type === 'textarea')
        <textarea name="{{ $name }}" id="{{ $id }}" rows="4" @if($required) required @endif {{ $attributes->class($control) }}>{{ old($name, $value) }}</textarea>
    @else
        <input
            type="{{ $type }}" name="{{ $name }}" id="{{ $id }}"
            value="{{ old($name, $value) }}"
            @if($required) required @endif
            {{ $attributes->class($control) }}
        >
    @endif

    @error($name)
        <p class="text-xs font-medium text-terracotta-700">{{ $message }}</p>
    @enderror
</div>
