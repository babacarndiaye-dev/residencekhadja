@php
    /** @var callable $isActive  @var array $sections  @var \App\Models\User $u */
    $linkClass = fn (bool $active) => $active
        ? 'bg-terracotta-500 text-white'
        : 'text-nuit-700 hover:bg-sable-100';
@endphp

@foreach ($sections as $section)
    @php
        if (isset($section['item'])) {
            $it = $section['item'];
            $show = $u->hasRole(...$it['roles']);
        } else {
            $visible = array_values(array_filter($section['items'], fn ($x) => $u->hasRole(...$x['roles'])));
            $show = count($visible) > 0;
        }
    @endphp
    @continue(! $show)

    @if (isset($section['item']))
        <a href="{{ route($it['route']) }}"
           @class(['flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-medium', $linkClass($isActive($it['route']))])>
            @isset($it['icon'])<x-icon name="{{ $it['icon'] }}" size="20" class="shrink-0" />@endisset
            <span>{{ $it['label'] }}</span>
        </a>
    @else
        @php $slug = Str::slug($section['group']); @endphp
        {{-- Accordéon : un seul groupe ouvert à la fois (état partagé « navGroup »). --}}
        <div class="select-none">
            <button type="button"
                    x-on:click="navGroup = (navGroup === '{{ $slug }}' ? null : '{{ $slug }}'); try { localStorage.setItem('nav:group', navGroup || '') } catch (e) {}"
                    :aria-expanded="navGroup === '{{ $slug }}'"
                    class="mt-2 flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-nuit-500 hover:bg-sable-100"
                    :class="navGroup === '{{ $slug }}' && 'text-terracotta-700'">
                <x-icon name="{{ $section['icon'] ?? 'folder_open' }}" size="20" class="shrink-0" />
                <span class="flex-1">{{ $section['group'] }}</span>
                <x-icon name="chevron_right" size="16" class="shrink-0 transition-transform" ::class="navGroup === '{{ $slug }}' && 'rotate-90'" />
            </button>
            <div x-show="navGroup === '{{ $slug }}'" x-cloak x-transition class="mt-0.5 flex flex-col gap-0.5 pl-3">
                @foreach ($visible as $it)
                    <a href="{{ route($it['route']) }}"
                       @class(['block rounded-lg px-3 py-2.5 text-sm font-medium', $linkClass($isActive($it['route']))])>
                        {{ $it['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif
@endforeach
