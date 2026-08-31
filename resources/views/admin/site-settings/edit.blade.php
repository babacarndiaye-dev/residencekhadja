@extends('admin.layout')
@section('title', 'Réglages du site')

@php
    $keys = array_keys($groups);
    $first = $keys[0] ?? null;
@endphp

@section('content')
<div x-data="{ tab: '{{ $first }}' }" class="flex flex-col gap-5">

    {{-- En-tête --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-display text-lg font-semibold text-nuit-900">Réglages du site</h1>
            <p class="text-sm text-nuit-500">
                Surcharge les fichiers <span class="font-mono text-xs">config/*.php</span> — s’applique aussitôt
                à la vitrine, au tunnel de réservation, aux e-mails et à l’app invité.
            </p>
        </div>
        <a href="{{ route('home') }}" target="_blank" rel="noopener"
           class="flex items-center gap-1.5 rounded-full border border-sable-300 px-3 py-1.5 text-xs font-semibold text-nuit-600 hover:border-terracotta-400 hover:text-terracotta-600">
            <x-icon name="open_in_new" :size="15" /> Voir la vitrine
        </a>
    </div>

    <form method="POST" action="{{ route('admin.site_settings.update') }}"
          class="grid items-start gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]">
        @csrf @method('PUT')

        {{-- Rail de navigation --}}
        <nav class="flex gap-1 overflow-x-auto rounded-2xl border border-sable-200 bg-white p-2 shadow-card lg:sticky lg:top-20 lg:max-h-[calc(100vh-7rem)] lg:flex-col lg:overflow-y-auto">
            @foreach ($groups as $slug => $group)
                <button type="button" x-on:click="tab = '{{ $slug }}'"
                        :class="tab === '{{ $slug }}' ? 'bg-nuit-900 text-white' : 'text-nuit-600 hover:bg-sable-100'"
                        class="group flex shrink-0 items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm font-semibold transition-colors">
                    <x-icon name="{{ $group['icon'] ?? 'tune' }}" :size="18" class="shrink-0" />
                    <span class="flex-1 whitespace-nowrap lg:whitespace-normal lg:leading-tight">{{ $group['label'] }}</span>
                    @if (($overrides[$slug] ?? 0) > 0)
                        <span title="{{ $overrides[$slug] }} réglage(s) personnalisé(s)" class="h-1.5 w-1.5 shrink-0 rounded-full bg-terracotta-500"></span>
                    @endif
                    <span :class="tab === '{{ $slug }}' ? 'bg-white/15 text-white/80' : 'bg-sable-100 text-nuit-400'"
                          class="hidden min-w-[1.25rem] rounded-full px-1 text-center text-[0.7rem] font-bold lg:inline">{{ count($group['fields']) }}</span>
                </button>
            @endforeach
        </nav>

        {{-- Panneau du groupe actif --}}
        <div class="flex min-h-[28rem] flex-col gap-4">
            @foreach ($groups as $slug => $group)
                <section x-show="tab === '{{ $slug }}'" x-cloak
                         class="max-w-3xl rounded-2xl border border-sable-200 bg-white p-5 shadow-card sm:p-7">
                    <header class="mb-6 flex items-start gap-3.5 border-b border-sable-200 pb-5">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-terracotta-50 text-terracotta-600">
                            <x-icon name="{{ $group['icon'] ?? 'tune' }}" :size="24" />
                        </span>
                        <div>
                            <h2 class="font-display text-base font-semibold text-nuit-900">{{ $group['label'] }}</h2>
                            @if (! empty($group['desc']))<p class="mt-1 text-sm leading-relaxed text-nuit-500">{{ $group['desc'] }}</p>@endif
                        </div>
                    </header>

                    <div class="grid gap-x-6 gap-y-5 sm:grid-cols-2">
                        @foreach ($group['fields'] as $field)
                            @php
                                $key = $field['key'];
                                $name = $nameOf($key);
                                $oldKey = 'f.'.str_replace('.', '~', $key);
                                $current = old($oldKey, $valueOf($field));
                                $type = $field['type'] ?? 'text';
                                $wide = $type === 'textarea'
                                    || \Illuminate\Support\Str::contains($key, ['intro', 'description', 'map_embed', 'pitch', 'hero_', 'cta_', 'tagline']);
                                $inputCls = 'mt-1.5 w-full rounded-lg border border-sable-300 bg-sable-50/40 px-3 py-2 text-sm font-normal text-nuit-900 transition-colors focus:border-terracotta-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-terracotta-100';
                            @endphp

                            <label @class(['flex flex-col text-sm font-semibold text-nuit-800', 'sm:col-span-2' => $wide])>
                                {{ $field['label'] }}
                                @if ($type === 'textarea')
                                    <textarea name="{{ $name }}" rows="3" class="{{ $inputCls }} resize-y">{{ $current }}</textarea>
                                @elseif ($type === 'boolean')
                                    <span class="mt-1.5 inline-flex items-center gap-2 font-normal text-nuit-700">
                                        <input type="checkbox" name="{{ $name }}" value="1" @checked($current)
                                               class="h-4 w-4 rounded border-nuit-300 text-terracotta-500 focus:ring-terracotta-400"> activé
                                    </span>
                                @else
                                    <input type="{{ $type }}" name="{{ $name }}" value="{{ $current }}"
                                           @if (! empty($field['step'])) step="{{ $field['step'] }}" @endif
                                           class="{{ $inputCls }}">
                                @endif
                                @error($oldKey)<span class="mt-1 text-xs font-normal text-red-600">{{ $message }}</span>@enderror
                                @if (! empty($field['help']))<span class="mt-1 text-xs font-normal text-nuit-400">{{ $field['help'] }}</span>@endif
                            </label>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <div class="sticky bottom-3 flex max-w-3xl items-center gap-3 rounded-2xl border border-sable-200 bg-white/95 p-3 shadow-luxe backdrop-blur">
                <button class="flex items-center gap-2 rounded-full bg-nuit-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-terracotta-500">
                    <x-icon name="save" :size="16" /> Enregistrer
                </button>
                <span class="text-xs text-nuit-400">Enregistre <b>tous</b> les onglets. Le point <span class="mx-0.5 inline-block h-1.5 w-1.5 rounded-full bg-terracotta-500 align-middle"></span> marque un réglage personnalisé.</span>
            </div>
        </div>
    </form>
</div>
@endsection
