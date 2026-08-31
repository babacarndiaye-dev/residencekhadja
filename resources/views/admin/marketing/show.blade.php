@extends('admin.layout')
@section('title', $campaign->name)

@section('content')
<div class="flex flex-col gap-6">
    <a href="{{ route('admin.marketing.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Campagnes</a>

    <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-lg font-semibold text-nuit-900">{{ $campaign->name }}</h2>
                <span class="rounded-full bg-sable-200 px-3 py-0.5 text-xs font-semibold text-nuit-600">{{ $campaign->statusLabel() }}</span>
            </div>
            <p class="mt-1 text-sm text-nuit-500">
                {{ $campaign->channelLabel() }} · {{ $campaign->segment->name ?? 'Tous les clients opt-in' }}
                @if ($campaign->promoCode) · code <span class="font-mono">{{ $campaign->promoCode->code }}</span>@endif
            </p>

            @if ($campaign->subject)<p class="mt-4 text-sm font-semibold text-nuit-800">Objet : {{ $campaign->subject }}</p>@endif
            <pre class="mt-2 whitespace-pre-wrap rounded-xl bg-sable-100 p-4 text-sm text-nuit-700">{{ $campaign->body }}</pre>

            @if ($sampleGuest)
                <p class="mt-3 text-xs font-semibold uppercase tracking-wider text-nuit-400">Aperçu pour {{ $sampleGuest->fullName() }}</p>
                <pre class="mt-1 whitespace-pre-wrap rounded-xl border border-sable-300 p-4 text-sm text-nuit-800">{{ \App\Services\CampaignDispatcher::render($campaign, $sampleGuest) }}</pre>
            @endif

            <div class="mt-5 flex flex-wrap gap-3">
                @if ($campaign->isEditable())
                    <form method="POST" action="{{ route('admin.marketing.rebuild', $campaign) }}">
                        @csrf
                        <button class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Recalculer les destinataires</button>
                    </form>
                    <form method="POST" action="{{ route('admin.marketing.send', $campaign) }}" onsubmit="return confirm('Envoyer la campagne à {{ $queued }} destinataire(s) ?')">
                        @csrf
                        <button class="rounded-full bg-terracotta-500 px-5 py-2 text-sm font-semibold text-white hover:bg-terracotta-600">Envoyer maintenant ({{ $queued }})</button>
                    </form>
                    <form method="POST" action="{{ route('admin.marketing.cancel', $campaign) }}">
                        @csrf
                        <button class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-500 hover:border-nuit-400">Annuler</button>
                    </form>
                @endif
            </div>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Statistiques</h3>
            <dl class="grid grid-cols-2 gap-3 text-center">
                <div class="rounded-xl bg-sable-100 p-3"><dt class="text-xs text-nuit-400">Destinataires</dt><dd class="font-display text-2xl font-semibold">{{ $campaign->recipients()->count() }}</dd></div>
                <div class="rounded-xl bg-sable-100 p-3"><dt class="text-xs text-nuit-400">En file</dt><dd class="font-display text-2xl font-semibold">{{ $queued }}</dd></div>
                <div class="rounded-xl bg-sable-100 p-3"><dt class="text-xs text-nuit-400">Envoyés</dt><dd class="font-display text-2xl font-semibold text-emerald-700">{{ $campaign->recipients()->where('status','sent')->count() }}</dd></div>
                <div class="rounded-xl bg-sable-100 p-3"><dt class="text-xs text-nuit-400">Sans adresse</dt><dd class="font-display text-2xl font-semibold text-terracotta-600">{{ $skipped }}</dd></div>
            </dl>
            @if ($campaign->sent_at)<p class="mt-3 text-xs text-nuit-400">Envoyée le {{ $campaign->sent_at->format('d/m/Y à H:i') }}</p>@endif
        </section>
    </div>

    <section class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Client</th><th class="px-4 py-3">Adresse</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3">Envoi</th></tr>
            </thead>
            <tbody>
                @foreach ($recipients as $r)
                    <tr class="border-t border-sable-200">
                        <td class="px-4 py-2">{{ $r->guest->fullName() }}</td>
                        <td class="px-4 py-2 text-xs text-nuit-500">{{ $r->address ?: '—' }}</td>
                        <td class="px-4 py-2"><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ ['sent' => 'bg-emerald-100 text-emerald-800', 'skipped' => 'bg-terracotta-100 text-terracotta-800', 'queued' => 'bg-sable-200 text-nuit-600'][$r->status] ?? 'bg-sable-200 text-nuit-600' }}">{{ $r->status }}</span>@if($r->reason)<span class="ml-1 text-xs text-nuit-400">{{ $r->reason }}</span>@endif</td>
                        <td class="px-4 py-2 text-xs text-nuit-400">{{ optional($r->sent_at)->format('d/m/y H:i') ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
    <div>{{ $recipients->links() }}</div>
</div>
@endsection
