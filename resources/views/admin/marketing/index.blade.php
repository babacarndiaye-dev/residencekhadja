@extends('admin.layout')
@section('title', 'Marketing')

@section('content')
<div class="flex flex-col gap-6" x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }">

    <div class="flex items-center justify-between">
        <h2 class="font-display text-lg font-semibold text-nuit-900">Campagnes</h2>
        <button x-on:click="open = !open" class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white hover:bg-nuit-800">Nouvelle campagne</button>
    </div>

    <section x-show="open" x-cloak class="rounded-2xl bg-white p-5 shadow-card">
        <form method="POST" action="{{ route('admin.marketing.store') }}" class="grid gap-4 lg:grid-cols-2"
              x-data="{ tpl(t) { this.$refs.subject.value = t.subject || ''; this.$refs.body.value = t.body; this.$refs.channel.value = t.channel; } }">
            @csrf
            <label class="text-xs font-semibold text-nuit-500">Nom
                <input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            </label>
            <label class="text-xs font-semibold text-nuit-500">Canal
                <select name="channel" x-ref="channel" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    @foreach ($channels as $k => $label)<option value="{{ $k }}" @selected(old('channel') === $k)>{{ $label }}</option>@endforeach
                </select>
            </label>
            <label class="text-xs font-semibold text-nuit-500">Segment (vide = tous les opt-in)
                <select name="segment_id" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    <option value="">Tous les clients opt-in</option>
                    @foreach ($segments as $s)<option value="{{ $s->id }}" @selected(old('segment_id') == $s->id)>{{ $s->name }} ({{ $s->size() }})</option>@endforeach
                </select>
            </label>
            <label class="text-xs font-semibold text-nuit-500">Code promo associé
                <select name="promo_code_id" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    <option value="">Aucun</option>
                    @foreach ($promos as $p)<option value="{{ $p->id }}" @selected(old('promo_code_id') == $p->id)>{{ $p->code }} — {{ $p->label }}</option>@endforeach
                </select>
            </label>
            <label class="text-xs font-semibold text-nuit-500 lg:col-span-2">Objet (e-mail)
                <input name="subject" x-ref="subject" value="{{ old('subject') }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            </label>
            <label class="text-xs font-semibold text-nuit-500 lg:col-span-2">Message — jetons : <code>{prenom}</code> <code>{nom}</code> <code>{code}</code>
                <textarea name="body" x-ref="body" rows="5" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">{{ old('body') }}</textarea>
            </label>
            <label class="text-xs font-semibold text-nuit-500">Programmer l'envoi (facultatif)
                <input type="datetime-local" name="scheduled_for" value="{{ old('scheduled_for') }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            </label>
            <div class="flex items-end gap-2 text-xs">
                @foreach ($templates as $t)
                    <button type="button" x-on:click='tpl(@json($t))' class="rounded-full border border-nuit-200 px-3 py-1.5 font-semibold text-nuit-600 hover:border-terracotta-400">{{ $t['name'] }}</button>
                @endforeach
            </div>
            <div class="lg:col-span-2">
                <button class="rounded-full bg-terracotta-500 px-5 py-2 text-sm font-semibold text-white hover:bg-terracotta-600">Créer & calculer les destinataires</button>
            </div>
        </form>
    </section>

    <section class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Campagne</th><th class="px-4 py-3">Canal</th><th class="px-4 py-3">Segment</th><th class="px-4 py-3">Destin.</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3">Envoi</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($campaigns as $c)
                    <tr class="border-t border-sable-200 hover:bg-sable-50">
                        <td class="px-4 py-3 font-medium text-nuit-900">{{ $c->name }}@if($c->promoCode)<span class="block font-mono text-xs text-nuit-400">{{ $c->promoCode->code }}</span>@endif</td>
                        <td class="px-4 py-3">{{ $c->channelLabel() }}</td>
                        <td class="px-4 py-3 text-xs text-nuit-500">{{ $c->segment->name ?? 'Tous opt-in' }}</td>
                        <td class="px-4 py-3">{{ $c->recipients_count }}</td>
                        <td class="px-4 py-3"><span class="rounded-full bg-sable-200 px-2 py-0.5 text-xs font-semibold text-nuit-600">{{ $c->statusLabel() }}</span></td>
                        <td class="px-4 py-3 text-xs text-nuit-400">{{ optional($c->sent_at)->format('d/m/y H:i') ?: (optional($c->scheduled_for)->format('d/m/y H:i') ?: '—') }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.marketing.show', $c) }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Ouvrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-nuit-400">Aucune campagne.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
@endsection
