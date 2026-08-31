@extends('admin.layout')
@section('title', 'Segments')

@section('content')
<div class="flex flex-col gap-6" x-data="segmentBuilder()">

    <section class="rounded-2xl bg-white p-5 shadow-card">
        <h2 class="mb-4 font-display text-lg font-semibold text-nuit-900">Nouveau segment</h2>
        <form method="POST" action="{{ route('admin.crm.segments.store') }}" class="grid gap-4 lg:grid-cols-2">
            @csrf
            <label class="text-xs font-semibold text-nuit-500">Nom
                <input name="name" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            </label>
            <label class="text-xs font-semibold text-nuit-500">Description
                <input name="description" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            </label>

            <fieldset class="lg:col-span-2 grid gap-3 sm:grid-cols-2">
                <legend class="mb-1 text-xs font-semibold uppercase tracking-wider text-nuit-400">Règles</legend>

                <label class="flex items-center gap-2 rounded-lg border border-sable-300 px-3 py-2 text-sm">
                    <input type="checkbox" name="rule[opted_in]" value="1" x-on:change="preview">
                    Consentement marketing accordé
                </label>
                <label class="flex items-center gap-2 rounded-lg border border-sable-300 px-3 py-2 text-sm">
                    <input type="checkbox" name="rule[never_stayed]" value="1" x-on:change="preview">
                    Aucun séjour honoré
                </label>

                <label class="text-xs font-semibold text-nuit-500">Séjours ≥
                    <input type="number" min="1" name="rule[min_stays]" x-on:input.debounce="preview" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>
                <label class="text-xs font-semibold text-nuit-500">Dépenses cumulées ≥ (FCFA)
                    <input type="number" min="1" step="1000" name="rule[min_spend]" x-on:input.debounce="preview" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>
                <label class="text-xs font-semibold text-nuit-500">Sans séjour depuis ≥ (jours)
                    <input type="number" min="1" name="rule[inactive_days]" x-on:input.debounce="preview" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>
                <label class="text-xs font-semibold text-nuit-500">Pays
                    <input name="rule[country]" x-on:input.debounce="preview" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>
                <label class="text-xs font-semibold text-nuit-500">Palier fidélité
                    <select name="rule[tier]" x-on:change="preview" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                        <option value="">—</option>
                        @foreach ($tiers as $code => $name)<option value="{{ $code }}">{{ $name }}</option>@endforeach
                    </select>
                </label>
                <label class="text-xs font-semibold text-nuit-500">Étiquette
                    <input name="rule[has_tag]" x-on:input.debounce="preview" list="tagsList" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    <datalist id="tagsList">@foreach (config('crm.tag_suggestions') as $t)<option value="{{ $t }}">@endforeach</datalist>
                </label>
                <label class="text-xs font-semibold text-nuit-500">Anniversaire au mois
                    <input name="rule[birthday_month]" placeholder="1–12 ou « current »" x-on:input.debounce="preview" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>
            </fieldset>

            <div class="lg:col-span-2 flex items-center justify-between rounded-xl bg-sable-100 p-3">
                <p class="text-sm text-nuit-600">
                    Aperçu : <b class="text-nuit-900" x-text="count"></b> client(s)
                    <span class="ml-2 text-xs text-nuit-400" x-text="sample.join(' · ')"></span>
                </p>
                <button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white hover:bg-nuit-800">Enregistrer</button>
            </div>
        </form>
    </section>

    <section class="rounded-2xl bg-white p-5 shadow-card">
        <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Segments existants</h2>
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wider text-nuit-400">
                <tr><th class="py-2">Nom</th><th>Règles</th><th class="text-right">Clients</th><th class="text-right">Campagnes</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($segments as $s)
                    <tr class="border-t border-sable-100">
                        <td class="py-2 font-medium text-nuit-900">{{ $s->name }}<span class="block text-xs text-nuit-400">{{ $s->description }}</span></td>
                        <td class="py-2 text-xs text-nuit-500">{{ $s->rulesSummary() }}</td>
                        <td class="py-2 text-right">{{ $s->size() }}</td>
                        <td class="py-2 text-right">{{ $s->campaigns_count }}</td>
                        <td class="py-2 text-right">
                            @if (! $s->campaigns_count)
                                <form method="POST" action="{{ route('admin.crm.segments.destroy', $s) }}" onsubmit="return confirm('Supprimer ce segment ?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs font-semibold text-terracotta-600 hover:underline">Supprimer</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-nuit-400">Aucun segment.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>

<script>
    function segmentBuilder() {
        return {
            count: 0, sample: [],
            preview() {
                const form = this.$root.querySelector('form');
                const fd = new FormData(form);
                const body = new URLSearchParams();
                for (const [k, v] of fd.entries()) { if (k.startsWith('rule[') && v !== '') body.append(k, v); }
                body.append('_token', '{{ csrf_token() }}');
                fetch('{{ route('admin.crm.segments.preview') }}', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body,
                }).then(r => r.json()).then(d => { this.count = d.count; this.sample = d.sample || []; }).catch(() => {});
            },
        };
    }
</script>
@endsection
