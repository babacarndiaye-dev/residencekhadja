@extends('admin.layout')
@section('title', $category->exists ? 'Éditer — '.$category->name : 'Nouvelle catégorie')

@php use Illuminate\Support\Facades\Storage; @endphp

@section('content')
<div class="mx-auto max-w-2xl">
    <a href="{{ route('admin.room_categories.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Catégories de chambre</a>

    <form method="POST" enctype="multipart/form-data"
          action="{{ $category->exists ? route('admin.room_categories.update', $category) : route('admin.room_categories.store') }}"
          class="mt-4 flex flex-col gap-4">
        @csrf
        @if ($category->exists) @method('PUT') @endif

        <div class="grid gap-4 rounded-2xl bg-white p-5 shadow-card sm:grid-cols-2">
            <label class="text-sm font-semibold text-nuit-800 sm:col-span-2">Nom
                <input type="text" name="name" value="{{ old('name', $category->name) }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
            </label>

            <label class="text-sm font-semibold text-nuit-800">Type
                <select name="category" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                    <option value="chambre" @selected(old('category', $category->category) === 'chambre')>Chambre</option>
                    <option value="suite" @selected(old('category', $category->category) === 'suite')>Suite</option>
                </select>
            </label>
            <label class="text-sm font-semibold text-nuit-800">Tarif « à partir de » / nuit (FCFA)
                <input type="number" name="price" value="{{ old('price', $category->price) }}" min="0" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
            </label>

            <label class="text-sm font-semibold text-nuit-800">Capacité (personnes)
                <input type="number" name="capacity" value="{{ old('capacity', $category->capacity) }}" min="1" max="12" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
            </label>
            <label class="text-sm font-semibold text-nuit-800">Surface (m²)
                <input type="number" name="size" value="{{ old('size', $category->size) }}" min="0" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
            </label>

            <label class="text-sm font-semibold text-nuit-800">Literie
                <input type="text" name="bed" value="{{ old('bed', $category->bed) }}" placeholder="Ex. 1 lit king" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
            </label>
            <label class="text-sm font-semibold text-nuit-800">Vue
                <input type="text" name="view" value="{{ old('view', $category->view) }}" placeholder="Ex. Jardin" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
            </label>

            <label class="text-sm font-semibold text-nuit-800 sm:col-span-2">Accroche courte
                <input type="text" name="short" value="{{ old('short', $category->short) }}" maxlength="180" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
            </label>
            <label class="text-sm font-semibold text-nuit-800 sm:col-span-2">Description
                <textarea name="description" rows="4" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">{{ old('description', $category->description) }}</textarea>
            </label>
            <label class="text-sm font-semibold text-nuit-800 sm:col-span-2">Équipements (séparés par des virgules)
                <input type="text" name="amenities" value="{{ old('amenities', implode(', ', $category->amenities ?? [])) }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
            </label>

            <div class="sm:col-span-2">
                <p class="text-sm font-semibold text-nuit-800">Photos</p>
                @if ($category->images)
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($category->images as $img)
                            <label class="relative">
                                <img src="{{ \Illuminate\Support\Str::startsWith($img, ['http', '/']) ? $img : Storage::disk('public')->url($img) }}" alt="" class="h-20 w-24 rounded-lg object-cover">
                                <span class="absolute right-1 top-1 flex items-center gap-1 rounded bg-white/90 px-1 text-[11px] text-red-600">
                                    <input type="checkbox" name="remove_images[]" value="{{ $img }}" class="rounded border-nuit-300"> retirer
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
                <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm text-nuit-600 file:mr-3 file:rounded-lg file:border-0 file:bg-nuit-900 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white hover:file:bg-nuit-800">
                <p class="mt-1 text-xs text-nuit-400">JPG / PNG / WebP, 5 Mo max, jusqu’à 8. La première photo sert de visuel principal sur la vitrine.</p>
            </div>

            <div class="flex flex-wrap items-center gap-4 sm:col-span-2">
                <label class="flex items-center gap-2 text-sm text-nuit-700"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true)) class="rounded border-nuit-300 text-terracotta-500"> Active (visible &amp; réservable)</label>
                <label class="flex items-center gap-2 text-sm text-nuit-700"><input type="checkbox" name="featured" value="1" @checked(old('featured', $category->featured)) class="rounded border-nuit-300 text-terracotta-500"> Mise en avant</label>
                <label class="flex items-center gap-2 text-sm text-nuit-700">Ordre <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="w-16 rounded-lg border border-nuit-200 px-2 py-1 text-sm"></label>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button class="rounded-xl bg-nuit-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-nuit-800">Enregistrer</button>
            <a href="{{ route('admin.room_categories.index') }}" class="text-sm text-nuit-500 hover:text-nuit-700">Annuler</a>
        </div>
    </form>
</div>
@endsection
