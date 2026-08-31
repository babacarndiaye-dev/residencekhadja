@extends('admin.layout')
@section('title', $lead->company ?: $lead->contact_name)

@section('content')
<div class="flex flex-col gap-6">
    <a href="{{ route('admin.events.pipeline') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Pipeline</a>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="font-display text-2xl font-semibold text-nuit-900">{{ $lead->company ?: $lead->contact_name }}</h2>
            <p class="text-sm text-nuit-500">{{ $lead->reference }} · {{ $lead->typeLabel() }}
                <span class="ml-2 rounded-full px-2 py-0.5 text-xs font-semibold text-white" style="background: {{ $stages[$lead->status]['color'] }}">{{ $lead->statusLabel() }}</span>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach ($transitions as $to)
                <form method="POST" action="{{ route('admin.events.leads.advance', $lead) }}" class="inline"
                      @if($to === 'perdu') onsubmit="return (this.lost_reason.value = prompt('Motif de la perte ?') || '') !== ''" @endif>
                    @csrf
                    <input type="hidden" name="status" value="{{ $to }}">
                    <input type="hidden" name="lost_reason" value="">
                    <button class="rounded-full border border-nuit-200 px-3 py-1.5 text-xs font-semibold text-nuit-700 hover:border-terracotta-400">→ {{ $stages[$to]['label'] }}</button>
                </form>
            @endforeach
            <a href="{{ route('admin.events.quotes.create', ['lead' => $lead->id]) }}" class="rounded-full bg-terracotta-500 px-4 py-1.5 text-xs font-semibold text-white hover:bg-terracotta-600">+ Devis</a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_1.3fr]">
        <div class="flex flex-col gap-6">
            <form method="POST" action="{{ route('admin.events.leads.update', $lead) }}" class="grid gap-3 rounded-2xl bg-white p-5 shadow-card sm:grid-cols-2">
                @csrf @method('PUT')
                <label class="text-xs font-semibold text-nuit-500 sm:col-span-2">Société
                    <input name="company" value="{{ $lead->company }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>
                <label class="text-xs font-semibold text-nuit-500">Contact
                    <input name="contact_name" value="{{ $lead->contact_name }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>
                <label class="text-xs font-semibold text-nuit-500">Type
                    <select name="event_type" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                        @foreach (config('events.event_types') as $k => $l)<option value="{{ $k }}" @selected($lead->event_type === $k)>{{ $l }}</option>@endforeach
                    </select>
                </label>
                <label class="text-xs font-semibold text-nuit-500">E-mail
                    <input name="contact_email" value="{{ $lead->contact_email }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>
                <label class="text-xs font-semibold text-nuit-500">Téléphone
                    <input name="contact_phone" value="{{ $lead->contact_phone }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>
                <label class="text-xs font-semibold text-nuit-500">Début</label>
                <label class="text-xs font-semibold text-nuit-500">Fin</label>
                <input type="date" name="expected_start" value="{{ optional($lead->expected_start)->toDateString() }}" class="w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <input type="date" name="expected_end" value="{{ optional($lead->expected_end)->toDateString() }}" class="w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <label class="text-xs font-semibold text-nuit-500">Pax
                    <input type="number" name="pax" value="{{ $lead->pax }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>
                <label class="text-xs font-semibold text-nuit-500">Budget estimé
                    <input type="number" name="estimated_value" step="1000" value="{{ $lead->estimated_value }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </label>
                <label class="text-xs font-semibold text-nuit-500">Source
                    <select name="source" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                        <option value="">—</option>
                        @foreach (config('events.lead_sources') as $k => $l)<option value="{{ $k }}" @selected($lead->source === $k)>{{ $l }}</option>@endforeach
                    </select>
                </label>
                <label class="text-xs font-semibold text-nuit-500 sm:col-span-2">Notes
                    <textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">{{ $lead->notes }}</textarea>
                </label>
                <div class="sm:col-span-2"><button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Enregistrer</button></div>
            </form>

            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Devis</h3>
                @forelse ($lead->quotes as $q)
                    <div class="flex items-center justify-between border-b border-sable-100 py-2 text-sm">
                        <a href="{{ route('admin.events.quotes.show', $q) }}" class="font-mono text-xs text-terracotta-600 hover:underline">{{ $q->reference }}</a>
                        <span>{{ money($q->total) }}</span>
                        <span class="rounded-full bg-sable-200 px-2 py-0.5 text-xs">{{ $q->statusLabel() }}</span>
                    </div>
                @empty
                    <p class="text-sm text-nuit-400">Aucun devis.</p>
                @endforelse
            </section>

            @if ($lead->events->isNotEmpty())
                <section class="rounded-2xl bg-white p-5 shadow-card">
                    <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Événements</h3>
                    @foreach ($lead->events as $e)
                        <a href="{{ route('admin.events.show', $e) }}" class="flex items-center justify-between border-b border-sable-100 py-2 text-sm hover:text-terracotta-600">
                            <span class="font-mono text-xs">{{ $e->reference }}</span>
                            <span>{{ $e->starts_at->format('d/m/Y') }}</span>
                            <span class="rounded-full bg-sable-200 px-2 py-0.5 text-xs">{{ $e->statusLabel() }}</span>
                        </a>
                    @endforeach
                </section>
            @endif
        </div>

        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Activités & suivi</h3>
            <form method="POST" action="{{ route('admin.events.leads.activity', $lead) }}" class="grid gap-2 sm:grid-cols-[auto_1fr]">
                @csrf
                <select name="type" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    @foreach ($activityTypes as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                </select>
                <div class="flex flex-col gap-2">
                    <input name="subject" placeholder="Objet" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    <textarea name="body" rows="2" placeholder="Détail" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm"></textarea>
                    <label class="text-xs text-nuit-500">Échéance (tâche) <input type="datetime-local" name="due_at" class="ml-1 rounded-lg border border-nuit-200 px-2 py-1 text-sm"></label>
                    <button class="self-start rounded-full bg-nuit-900 px-4 py-1.5 text-xs font-semibold text-white">Ajouter</button>
                </div>
            </form>

            <ol class="mt-4 flex flex-col gap-3 border-l border-sable-300 pl-4">
                @forelse ($lead->activities as $a)
                    <li class="relative">
                        <span class="absolute -left-[1.35rem] top-1 h-2.5 w-2.5 rounded-full {{ $a->type === 'task' && ! $a->done ? 'bg-terracotta-500' : 'bg-nuit-300' }}"></span>
                        <p class="text-sm font-semibold text-nuit-800">{{ $a->subject }}
                            <span class="ml-1 rounded-full bg-sable-100 px-2 py-0.5 text-xs text-nuit-500">{{ $a->typeLabel() }}</span>
                            @if ($a->type === 'task' && ! $a->done)
                                <form method="POST" action="{{ route('admin.events.leads.activity.done', [$lead, $a]) }}" class="inline">
                                    @csrf <button class="ml-1 text-xs font-semibold text-emerald-700 hover:underline">✓ fait</button>
                                </form>
                            @endif
                        </p>
                        @if ($a->body)<p class="text-xs text-nuit-600">{{ $a->body }}</p>@endif
                        <p class="text-xs text-nuit-400">{{ $a->occurred_at->format('d/m/Y H:i') }}@if($a->user) · {{ $a->user->name }}@endif @if($a->due_at) · échéance {{ $a->due_at->format('d/m H:i') }}@endif</p>
                    </li>
                @empty
                    <li class="text-sm text-nuit-400">Aucune activité.</li>
                @endforelse
            </ol>
        </section>
    </div>
</div>
@endsection
