@extends('admin.layout')
@section('title', 'Commercial — Pipeline')

@section('content')
<div class="flex flex-col gap-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
            <div class="rounded-2xl bg-white p-4 shadow-card">
                <p class="text-xs uppercase tracking-wider text-nuit-400">Prévision pondérée</p>
                <p class="mt-1 font-display text-2xl font-semibold text-nuit-900">{{ money($forecast) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-4 shadow-card">
                <p class="text-xs uppercase tracking-wider text-nuit-400">Gagné (cumul)</p>
                <p class="mt-1 font-display text-2xl font-semibold text-emerald-700">{{ money($wonValue) }}</p>
            </div>
            <div class="rounded-2xl bg-white p-4 shadow-card">
                <p class="text-xs uppercase tracking-wider text-nuit-400">Tâches à faire</p>
                <p class="mt-1 font-display text-2xl font-semibold {{ $openTasks->count() ? 'text-terracotta-600' : 'text-nuit-900' }}">{{ $openTasks->count() }}</p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.events.leads.create') }}" class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white hover:bg-nuit-800">Nouvelle affaire</a>
            <a href="{{ route('admin.events.index') }}" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Calendrier événements</a>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
        @foreach ($stages as $key => $meta)
            <div class="flex flex-col gap-3 rounded-2xl bg-white p-3 shadow-card">
                <p class="flex items-center justify-between text-sm font-semibold text-nuit-800">
                    <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full" style="background: {{ $meta['color'] }}"></span>{{ $meta['label'] }}</span>
                    <span class="text-xs text-nuit-400">{{ $board[$key]->count() }}</span>
                </p>
                @forelse ($board[$key] as $lead)
                    <a href="{{ route('admin.events.leads.show', $lead) }}" class="rounded-xl border border-sable-200 p-3 text-sm hover:border-terracotta-300">
                        <p class="font-medium text-nuit-900">{{ $lead->company ?: $lead->contact_name }}</p>
                        <p class="text-xs text-nuit-500">{{ $lead->typeLabel() }}@if($lead->pax) · {{ $lead->pax }} pax @endif</p>
                        <p class="mt-1 text-xs font-semibold text-nuit-700">{{ money($lead->estimated_value) }}</p>
                        @if ($lead->expected_start)<p class="text-xs text-nuit-400">{{ $lead->expected_start->format('d/m/Y') }}</p>@endif
                    </a>
                @empty
                    <p class="rounded-lg border border-dashed border-sable-300 p-3 text-center text-xs text-nuit-300">—</p>
                @endforelse
            </div>
        @endforeach
    </div>

    @if ($openTasks->isNotEmpty())
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Tâches ouvertes</h2>
            <ul class="flex flex-col gap-1.5 text-sm">
                @foreach ($openTasks as $t)
                    <li class="flex justify-between">
                        <a href="{{ route('admin.events.leads.show', $t->lead) }}" class="hover:text-terracotta-600">{{ $t->subject }} — {{ $t->lead->company ?: $t->lead->contact_name }}</a>
                        <span class="text-xs {{ $t->due_at && $t->due_at->isPast() ? 'text-terracotta-600' : 'text-nuit-400' }}">{{ optional($t->due_at)->format('d/m H:i') ?: '—' }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
@endsection
