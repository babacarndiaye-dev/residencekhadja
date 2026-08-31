@extends('admin.layout')
@section('title', 'Réservation sur place')

@section('content')
<div class="mx-auto flex max-w-2xl flex-col gap-5">
    <a href="{{ route('admin.reservations.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Réservations</a>

    <form method="POST" action="{{ route('admin.reservations.store') }}" class="flex flex-col gap-5"
          x-data="{
              cats: {{ Illuminate\Support\Js::from($categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'price' => (int) $c->price, 'left' => $availability[$c->id] ?? null])) }},
              lines: {{ Illuminate\Support\Js::from(old('lines', [['room_category_id' => (string) ($categories->first()->id ?? ''), 'rooms_count' => 1]])) }},
              get rooms() { return this.lines.reduce((s, l) => s + (parseInt(l.rooms_count) || 0), 0); }
          }">
        @csrf

        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Client</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="text-xs font-semibold text-nuit-500">Prénom *
                    <input name="first_name" value="{{ old('first_name') }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>
                <label class="text-xs font-semibold text-nuit-500">Nom *
                    <input name="last_name" value="{{ old('last_name') }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>
                <label class="text-xs font-semibold text-nuit-500">Téléphone
                    <input name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>
                <label class="text-xs font-semibold text-nuit-500">E-mail
                    <input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>
                <label class="text-xs font-semibold text-nuit-500 sm:col-span-2">Pays
                    <input name="country" value="{{ old('country', 'Sénégal') }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>
            </div>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Séjour</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="text-xs font-semibold text-nuit-500">Arrivée *
                    <input type="date" name="check_in" value="{{ old('check_in', $checkIn->toDateString()) }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>
                <label class="text-xs font-semibold text-nuit-500">Départ *
                    <input type="date" name="check_out" value="{{ old('check_out', $checkIn->copy()->addDay()->toDateString()) }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>
                <label class="text-xs font-semibold text-nuit-500 sm:col-span-2">Plan tarifaire
                    <select name="rate_plan_id" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                        @foreach ($ratePlans as $p)
                            <option value="{{ $p->id }}" @selected(old('rate_plan_id') == $p->id)>{{ $p->name }} (×{{ rtrim(rtrim(number_format($p->multiplier, 2), '0'), '.') }})</option>
                        @endforeach
                    </select>
                </label>

                <div class="sm:col-span-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-nuit-500">Chambres * <span x-text="'(' + rooms + ' au total)'" class="text-nuit-400"></span></span>
                        <button type="button" x-on:click="lines.push({ room_category_id: String(cats[0]?.id ?? ''), rooms_count: 1 })"
                                class="rounded-full border border-nuit-200 px-3 py-1 text-xs font-semibold text-nuit-700 hover:border-terracotta-400">+ Catégorie</button>
                    </div>
                    <template x-for="(l, i) in lines" :key="i">
                        <div class="mt-2 flex items-center gap-2">
                            <select :name="`lines[${i}][room_category_id]`" x-model="l.room_category_id" required
                                    class="flex-1 rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                                <template x-for="c in cats" :key="c.id">
                                    <option :value="c.id" :disabled="c.left === 0"
                                            x-text="c.name + ' — ' + new Intl.NumberFormat('fr-FR').format(c.price) + ' FCFA/nuit' + (c.left !== null ? ' · ' + c.left + ' dispo.' : '')"></option>
                                </template>
                            </select>
                            <input type="number" :name="`lines[${i}][rooms_count]`" x-model.number="l.rooms_count" min="1" max="10" required
                                   class="w-20 rounded-lg border border-nuit-200 px-2 py-2 text-sm">
                            <button type="button" x-on:click="lines.splice(i, 1)" x-show="lines.length > 1" class="text-xs text-red-600">✕</button>
                        </div>
                    </template>
                </div>

                <label class="text-xs font-semibold text-nuit-500">Adultes
                    <input type="number" name="adults" value="{{ old('adults', 1) }}" min="1" max="30" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>
                <label class="text-xs font-semibold text-nuit-500">Enfants
                    <input type="number" name="children" value="{{ old('children', 0) }}" min="0" max="20" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>
                <label class="text-xs font-semibold text-nuit-500 sm:col-span-2">Remarque
                    <textarea name="notes" rows="2" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">{{ old('notes') }}</textarea>
                </label>
            </div>

            <label class="mt-3 flex items-center gap-2 rounded-xl bg-sable-100 p-3 text-sm text-nuit-800">
                <input type="checkbox" name="check_in_now" value="1" @checked(old('check_in_now')) class="rounded border-nuit-300 text-terracotta-500">
                Installer le client immédiatement (check-in + attribution d’une chambre propre)
            </label>
        </section>

        <div>
            <button class="rounded-full bg-nuit-900 px-6 py-3 text-sm font-semibold text-white hover:bg-terracotta-500">Créer la réservation</button>
        </div>
    </form>
</div>
@endsection
