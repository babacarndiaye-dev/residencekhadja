@extends('admin.layout')
@section('title', $item->exists ? 'Éditer — '.$item->name : 'Nouvel article')

@php
    $groups = $item->exists
        ? $item->optionGroups->map(fn ($g) => [
            'name' => $g->name, 'type' => $g->type, 'required' => (bool) $g->required,
            'options' => $g->options->map(fn ($o) => ['name' => $o->name, 'price_delta' => $o->price_delta])->values(),
        ])->values()
        : [];
    $recipe = $item->exists
        ? $item->recipe->map(fn ($r) => ['stock_item_id' => (string) $r->stock_item_id, 'quantity' => (float) $r->quantity, 'warehouse_id' => (string) ($r->warehouse_id ?? '')])->values()
        : [];
@endphp

@section('content')
<div class="mx-auto max-w-2xl">
    <a href="{{ route('admin.menu.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Retour à la carte</a>

    <form method="POST" action="{{ $item->exists ? route('admin.menu.item.update', $item) : route('admin.menu.item.store') }}"
          enctype="multipart/form-data"
          x-data="{
              groups: {{ Illuminate\Support\Js::from($groups) }},
              recipe: {{ Illuminate\Support\Js::from($recipe) }},
              stockItems: {{ Illuminate\Support\Js::from($stockItems->map(fn ($s) => ['id' => (string) $s->id, 'name' => $s->name, 'unit' => $s->unit, 'cost' => (int) $s->avg_cost])) }},
              warehouses: {{ Illuminate\Support\Js::from($warehouses->map(fn ($w) => ['id' => (string) $w->id, 'name' => $w->name])) }},
              get foodCost() { return this.recipe.reduce((s, r) => { const it = this.stockItems.find(x => x.id === String(r.stock_item_id)); return s + (it ? it.cost * (parseFloat(r.quantity) || 0) : 0); }, 0); },
          }"
          class="mt-4 flex flex-col gap-4">
        @csrf
        @if ($item->exists) @method('PUT') @endif

        <div class="grid gap-4 rounded-2xl bg-white p-5 shadow-card sm:grid-cols-2">
            <label class="text-sm font-semibold text-nuit-800 sm:col-span-2">Nom
                <input type="text" name="name" value="{{ old('name', $item->name) }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
            </label>
            <label class="text-sm font-semibold text-nuit-800">Catégorie
                <select name="menu_category_id" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                    @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(old('menu_category_id', $item->menu_category_id) == $c->id)>{{ $c->name }}</option>@endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-nuit-800">Prix (FCFA)
                <input type="number" name="price" value="{{ old('price', $item->price) }}" min="0" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
            </label>
            <label class="text-sm font-semibold text-nuit-800 sm:col-span-2">Description
                <textarea name="description" rows="2" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">{{ old('description', $item->description) }}</textarea>
            </label>
            <label class="text-sm font-semibold text-nuit-800 sm:col-span-2">Storytelling
                <textarea name="story" rows="2" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">{{ old('story', $item->story) }}</textarea>
            </label>
            <div class="sm:col-span-2">
                <p class="text-sm font-semibold text-nuit-800">Photo</p>
                <div class="mt-1 flex items-start gap-3">
                    @if ($item->imageUrl())
                        <img src="{{ $item->imageUrl() }}" alt="" class="h-20 w-20 shrink-0 rounded-lg object-cover">
                    @endif
                    <div class="flex-1">
                        <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-nuit-600 file:mr-3 file:rounded-lg file:border-0 file:bg-nuit-900 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white hover:file:bg-nuit-800">
                        <p class="mt-1 text-xs text-nuit-400">JPG / PNG / WebP, 4 Mo max. Un fichier téléversé remplace l’URL ci-dessous.</p>
                        <input type="text" name="image" value="{{ old('image', $item->image) }}" placeholder="ou coller une URL d’image" class="mt-2 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                    </div>
                </div>
            </div>
            <label class="text-sm font-semibold text-nuit-800">Allergènes (séparés par des virgules)
                <input type="text" name="allergens" value="{{ old('allergens', implode(', ', $item->allergens ?? [])) }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
            </label>
            <label class="text-sm font-semibold text-nuit-800">Tags (virgules)
                <input type="text" name="tags" value="{{ old('tags', implode(', ', $item->tags ?? [])) }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
            </label>
            <div class="flex items-center gap-4 sm:col-span-2">
                <label class="flex items-center gap-2 text-sm text-nuit-700"><input type="checkbox" name="is_available" value="1" @checked(old('is_available', $item->is_available ?? true)) class="rounded border-nuit-300 text-terracotta-500"> Disponible</label>
                <label class="flex items-center gap-2 text-sm text-nuit-700"><input type="checkbox" name="is_signature" value="1" @checked(old('is_signature', $item->is_signature)) class="rounded border-nuit-300 text-terracotta-500"> Signature</label>
                <label class="flex items-center gap-2 text-sm text-nuit-700">Ordre <input type="number" name="sort_order" value="{{ old('sort_order', $item->sort_order ?? 0) }}" class="w-16 rounded-lg border border-nuit-200 px-2 py-1 text-sm"></label>
            </div>
        </div>

        {{-- Groupes d'options --}}
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <div class="flex items-center justify-between">
                <h3 class="font-display text-lg font-semibold text-nuit-900">Options</h3>
                <button type="button" x-on:click="groups.push({ name: '', type: 'single', required: false, options: [{ name: '', price_delta: 0 }] })"
                        class="rounded-full border border-nuit-200 px-3 py-1.5 text-xs font-semibold text-nuit-700">+ Groupe</button>
            </div>

            <template x-for="(g, gi) in groups" :key="gi">
                <div class="mt-3 rounded-xl border border-sable-200 p-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <input type="text" :name="`groups[${gi}][name]`" x-model="g.name" placeholder="Nom du groupe (ex: Cuisson)" class="flex-1 rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">
                        <select :name="`groups[${gi}][type]`" x-model="g.type" class="rounded-lg border border-nuit-200 px-2 py-1.5 text-sm">
                            <option value="single">Choix unique</option>
                            <option value="multi">Choix multiple</option>
                        </select>
                        <label class="flex items-center gap-1 text-xs text-nuit-600"><input type="checkbox" :name="`groups[${gi}][required]`" value="1" x-model="g.required" class="rounded border-nuit-300 text-terracotta-500"> Requis</label>
                        <button type="button" x-on:click="groups.splice(gi, 1)" class="text-xs text-red-600">Retirer</button>
                    </div>
                    <template x-for="(o, oi) in g.options" :key="oi">
                        <div class="mt-2 flex items-center gap-2 pl-3">
                            <input type="text" :name="`groups[${gi}][options][${oi}][name]`" x-model="o.name" placeholder="Choix" class="flex-1 rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">
                            <input type="number" :name="`groups[${gi}][options][${oi}][price_delta]`" x-model="o.price_delta" placeholder="+ FCFA" class="w-24 rounded-lg border border-nuit-200 px-2 py-1.5 text-sm">
                            <button type="button" x-on:click="g.options.splice(oi, 1)" class="text-xs text-red-600">✕</button>
                        </div>
                    </template>
                    <button type="button" x-on:click="g.options.push({ name: '', price_delta: 0 })" class="mt-2 text-xs font-semibold text-terracotta-600">+ Choix</button>
                </div>
            </template>
            <p x-show="!groups.length" class="mt-2 text-xs text-nuit-400">Aucune option — l’article est ajouté au panier directement.</p>
        </div>

        {{-- Recette / nomenclature de stock --}}
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <div class="flex items-center justify-between">
                <h3 class="font-display text-lg font-semibold text-nuit-900">Recette (stock)</h3>
                <button type="button" x-on:click="recipe.push({ stock_item_id: '', quantity: 1, warehouse_id: '' })"
                        class="rounded-full border border-nuit-200 px-3 py-1 text-xs font-semibold text-nuit-700">+ Ingrédient</button>
            </div>
            <p class="mt-1 text-xs text-nuit-400">Consommé à chaque vente → décrément automatique du stock et calcul de la marge.</p>

            <template x-for="(r, ri) in recipe" :key="ri">
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <select :name="`recipe[${ri}][stock_item_id]`" x-model="r.stock_item_id" required class="flex-1 rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">
                        <option value="">Article de stock…</option>
                        <template x-for="s in stockItems" :key="s.id"><option :value="s.id" x-text="s.name + ' (' + s.unit + ')'"></option></template>
                    </select>
                    <input type="number" step="0.001" min="0" :name="`recipe[${ri}][quantity]`" x-model.number="r.quantity" placeholder="Qté" class="w-24 rounded-lg border border-nuit-200 px-2 py-1.5 text-right text-sm">
                    <select :name="`recipe[${ri}][warehouse_id]`" x-model="r.warehouse_id" class="w-40 rounded-lg border border-nuit-200 px-2 py-1.5 text-sm">
                        <option value="">Magasin auto</option>
                        <template x-for="w in warehouses" :key="w.id"><option :value="w.id" x-text="w.name"></option></template>
                    </select>
                    <button type="button" x-on:click="recipe.splice(ri, 1)" class="text-xs text-red-600">✕</button>
                </div>
            </template>
            <p x-show="!recipe.length" class="mt-2 text-xs text-nuit-400">Pas de recette — la vente ne touche pas le stock.</p>
            <p x-show="recipe.length" class="mt-3 text-sm text-nuit-600">
                Coût matière estimé : <strong x-text="new Intl.NumberFormat('fr-FR').format(Math.round(foodCost)) + ' FCFA'"></strong>
            </p>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded-full bg-terracotta-500 px-6 py-2.5 text-sm font-semibold text-white hover:bg-terracotta-600">Enregistrer</button>
            @if ($item->exists)
                <button form="del-item" class="rounded-full border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50">Supprimer</button>
            @endif
        </div>
    </form>

    @if ($item->exists)
        <form method="POST" action="{{ route('admin.menu.item.destroy', $item) }}" id="del-item" onsubmit="return confirm('Supprimer cet article ?')">@csrf @method('DELETE')</form>
    @endif
</div>
@endsection
