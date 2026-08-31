@extends('admin.layout')
@section('title', 'Contrôle petit-déjeuner')

@section('content')
<div class="mx-auto flex max-w-2xl flex-col gap-5"
     x-data="{
         q: '', results: [], picked: null, busy: false,
         async search() {
             if (this.q.trim().length < 2) { this.results = []; return; }
             this.busy = true;
             const r = await fetch('{{ route('admin.pos.guests') }}?q=' + encodeURIComponent(this.q));
             this.results = (await r.json()).results;
             this.busy = false;
         },
         pick(row) { this.picked = row; this.results = []; this.q = row.room + ' · ' + row.guest; },
     }">

    <div>
        <h1 class="font-display text-lg font-semibold text-nuit-900">Contrôle du petit-déjeuner</h1>
        <p class="text-sm text-nuit-500">{{ $today->translatedFormat('l d F Y') }} — un passage par chambre et par jour.</p>
    </div>

    <div class="rounded-2xl bg-white p-5 shadow-card">
        <label class="text-xs font-semibold text-nuit-500">Chambre / nom / référence
            <input x-model="q" x-on:input.debounce.300ms="search()" placeholder="Ex. 204, Ndiaye, HRK-…"
                   class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2.5 text-sm">
        </label>

        <div x-show="results.length" class="mt-2 flex flex-col gap-1 rounded-lg border border-sable-200 p-1">
            <template x-for="row in results" :key="row.id">
                <button type="button" x-on:click="pick(row)" class="flex items-center justify-between rounded px-3 py-2 text-left text-sm hover:bg-sable-50">
                    <span><b class="text-nuit-900">Ch. <span x-text="row.room"></span></b> · <span x-text="row.guest"></span></span>
                    <span class="text-xs" :class="row.breakfast.included ? 'text-emerald-700' : 'text-nuit-400'" x-text="row.breakfast.included ? 'PDJ inclus' : 'PDJ non inclus'"></span>
                </button>
            </template>
        </div>

        <template x-if="picked">
            <form method="POST" action="{{ route('admin.pos.breakfast.store') }}" class="mt-4 border-t border-sable-200 pt-4">
                @csrf
                <input type="hidden" name="reservation_id" :value="picked.id">

                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <span class="rounded-full bg-sable-100 px-2.5 py-1 font-semibold">Ch. <span x-text="picked.room"></span> · <span x-text="picked.guest"></span></span>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="picked.breakfast.included ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800'"
                          x-text="picked.breakfast.included ? 'Petit-déjeuner inclus' : 'Non inclus → imputé au folio'"></span>
                    <span x-show="picked.breakfast.checked_today" class="rounded-full bg-nuit-100 px-2.5 py-1 text-xs font-semibold text-nuit-600">Déjà pointé aujourd'hui</span>
                </div>

                <label class="mt-3 block text-xs font-semibold text-nuit-500">Nombre de couverts
                    <input type="number" name="guests" value="1" min="1" max="20" required class="mt-1 w-28 rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>

                <button class="mt-4 rounded-full bg-nuit-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-terracotta-500"
                        x-text="picked.breakfast.checked_today ? 'Mettre à jour le passage' : 'Enregistrer le passage'"></button>
            </form>
        </template>
    </div>

    <section>
        <h2 class="mb-2 font-display text-sm font-semibold uppercase tracking-wider text-nuit-500">Passages du jour ({{ $recent->count() }})</h2>
        <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
            <table class="w-full text-sm">
                <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                    <tr><th class="px-4 py-3">Chambre</th><th class="px-4 py-3">Client</th><th class="px-4 py-3">Couverts</th><th class="px-4 py-3">Facturation</th></tr>
                </thead>
                <tbody>
                    @forelse ($recent as $c)
                        <tr class="border-t border-sable-200">
                            <td class="px-4 py-2.5">{{ $c->reservation->room->number ?? '—' }}</td>
                            <td class="px-4 py-2.5">{{ optional($c->reservation->guest)->fullName() }}</td>
                            <td class="px-4 py-2.5">{{ $c->guests }}</td>
                            <td class="px-4 py-2.5">
                                @if ($c->included)
                                    <span class="text-emerald-700">Inclus</span>
                                @else
                                    <span class="text-amber-700">Folio {{ $c->charge ? '· '.money($c->charge->amount) : '' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-nuit-400">Aucun passage enregistré aujourd'hui.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
