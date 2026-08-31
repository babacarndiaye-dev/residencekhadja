@extends('admin.layout')
@section('title', 'Carte')

@section('content')
<div class="flex flex-col gap-6">

    <div class="flex items-center justify-between">
        <p class="text-sm text-nuit-500">{{ $categories->count() }} catégories · {{ $categories->sum(fn($c) => $c->items->count()) }} articles</p>
        <a href="{{ route('admin.menu.item.create') }}" class="rounded-full bg-terracotta-500 px-4 py-2 text-sm font-semibold text-white hover:bg-terracotta-600">+ Nouvel article</a>
    </div>

    {{-- Nouvelle catégorie --}}
    <details class="rounded-2xl bg-white p-4 shadow-card">
        <summary class="cursor-pointer text-sm font-semibold text-nuit-900">+ Ajouter une catégorie</summary>
        <form method="POST" action="{{ route('admin.menu.category.store') }}" class="mt-3 grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
            @csrf
            <input type="text" name="name" placeholder="Nom" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <input type="text" name="description" placeholder="Description" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-1.5 text-xs text-nuit-600"><input type="checkbox" name="room_service" value="1" class="rounded border-nuit-300 text-terracotta-500"> Room service</label>
                <label class="flex items-center gap-1.5 text-xs text-nuit-600"><input type="checkbox" name="needs_kitchen" value="1" checked class="rounded border-nuit-300 text-terracotta-500"> Cuisine</label>
                <label class="flex items-center gap-1.5 text-xs text-nuit-600"><input type="checkbox" name="is_active" value="1" checked class="rounded border-nuit-300 text-terracotta-500"> Active</label>
                <button class="rounded-full bg-nuit-900 px-4 py-2 text-xs font-semibold text-white">Créer</button>
            </div>
        </form>
    </details>

    @foreach ($categories as $cat)
        <section class="rounded-2xl bg-white p-4 shadow-card">
            <form method="POST" action="{{ route('admin.menu.category.update', $cat) }}" class="flex flex-wrap items-center gap-3 border-b border-sable-200 pb-3">
                @csrf @method('PUT')
                <input type="text" name="name" value="{{ $cat->name }}" class="rounded-lg border border-nuit-200 px-3 py-1.5 text-sm font-semibold">
                <input type="text" name="description" value="{{ $cat->description }}" placeholder="Description" class="flex-1 rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">
                <input type="number" name="sort_order" value="{{ $cat->sort_order }}" class="w-16 rounded-lg border border-nuit-200 px-2 py-1.5 text-sm" title="Ordre">
                <label class="flex items-center gap-1.5 text-xs text-nuit-600"><input type="checkbox" name="room_service" value="1" @checked($cat->room_service) class="rounded border-nuit-300 text-terracotta-500"> RS</label>
                <label class="flex items-center gap-1.5 text-xs text-nuit-600" title="Envoyer en cuisine (KDS)"><input type="checkbox" name="needs_kitchen" value="1" @checked($cat->needs_kitchen) class="rounded border-nuit-300 text-terracotta-500"> Cuisine</label>
                <label class="flex items-center gap-1.5 text-xs text-nuit-600"><input type="checkbox" name="is_active" value="1" @checked($cat->is_active) class="rounded border-nuit-300 text-terracotta-500"> Active</label>
                <details class="text-xs">
                    <summary class="cursor-pointer text-nuit-500">Lieux ({{ $cat->venues->count() }})</summary>
                    <div class="mt-1 flex flex-col gap-1">
                        @foreach ($venues as $v)
                            <label class="flex items-center gap-1.5 text-nuit-600"><input type="checkbox" name="venues[]" value="{{ $v->id }}" @checked($cat->venues->contains($v->id)) class="rounded border-nuit-300 text-terracotta-500"> {{ $v->name }}</label>
                        @endforeach
                    </div>
                </details>
                <button class="rounded-full bg-nuit-900 px-3 py-1.5 text-xs font-semibold text-white">OK</button>
                <button form="del-cat-{{ $cat->id }}" class="text-xs text-red-600">Supprimer</button>
            </form>
            <form method="POST" action="{{ route('admin.menu.category.destroy', $cat) }}" id="del-cat-{{ $cat->id }}" onsubmit="return confirm('Supprimer cette catégorie et ses articles ?')">@csrf @method('DELETE')</form>

            <ul class="mt-3 flex flex-col divide-y divide-sable-100">
                @forelse ($cat->items as $item)
                    <li class="flex items-center gap-3 py-2 text-sm">
                        <span class="flex-1">
                            <span class="font-medium text-nuit-900">{{ $item->name }}</span>
                            @if ($item->is_signature)<span class="text-xs text-terracotta-600"> · signature</span>@endif
                            @unless ($item->is_available)<span class="text-xs text-red-600"> · indisponible</span>@endunless
                        </span>
                        @if ($item->recipe->isNotEmpty())
                            @php $mp = $item->marginPercent(); @endphp
                            <span class="hidden text-xs text-nuit-400 sm:inline" title="Coût matière / marge">coût {{ money($item->foodCost()) }} · marge <span class="{{ $mp !== null && $mp < 30 ? 'text-terracotta-600' : 'text-emerald-700' }}">{{ $mp === null ? '—' : $mp.' %' }}</span></span>
                        @endif
                        <span class="text-nuit-600">{{ money($item->price) }}</span>
                        <form method="POST" action="{{ route('admin.menu.item.toggle', $item) }}">@csrf
                            <button class="rounded-full border border-nuit-200 px-2.5 py-1 text-xs text-nuit-600 hover:border-terracotta-400">{{ $item->is_available ? 'Masquer' : 'Afficher' }}</button>
                        </form>
                        <a href="{{ route('admin.menu.item.edit', $item) }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Éditer</a>
                    </li>
                @empty
                    <li class="py-3 text-xs text-nuit-400">Aucun article.</li>
                @endforelse
            </ul>
        </section>
    @endforeach
</div>
@endsection
