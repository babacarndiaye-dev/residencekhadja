@extends('admin.layout')
@section('title', $ticket->exists ? 'Ticket '.$ticket->reference : 'Nouveau ticket')

@php
    $priorities = config('maintenance.ticket_priorities');
    $statuses = config('maintenance.ticket_statuses');
@endphp

@section('content')
<div class="mx-auto max-w-2xl">
    <a href="{{ route('admin.maintenance.tickets') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Tous les tickets</a>

    @if ($ticket->exists)
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <h2 class="font-display text-2xl font-semibold text-nuit-900">{{ $ticket->reference }}</h2>
            <x-admin.badge :status="$ticket->status" :label="$ticket->statusLabel()" />
            <x-admin.badge :status="$ticket->priority" :label="$ticket->priorityLabel()" />
            <span class="text-sm text-nuit-500">{{ $ticket->type === 'preventive' ? 'Préventif' : 'Correctif' }}
                @if ($ticket->plan) · plan « {{ $ticket->plan->name }} » @endif
                @if ($ticket->due_on) · échéance {{ $ticket->due_on->format('d/m/Y') }} @endif
            </span>
        </div>
    @endif

    <form method="POST"
          action="{{ $ticket->exists ? route('admin.maintenance.ticket.update', $ticket) : route('admin.maintenance.ticket.store') }}"
          class="mt-4 flex flex-col gap-4 rounded-2xl bg-white p-5 shadow-card">
        @csrf
        @if ($ticket->exists) @method('PUT') @endif

        <label class="text-sm font-semibold text-nuit-800">Titre
            <input type="text" name="title" value="{{ old('title', $ticket->title) }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
        </label>
        <label class="text-sm font-semibold text-nuit-800">Description
            <textarea name="description" rows="3" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">{{ old('description', $ticket->description) }}</textarea>
        </label>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="text-sm font-semibold text-nuit-800">Équipement
                <select name="equipment_id" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                    <option value="">—</option>
                    @foreach ($equipment as $e)<option value="{{ $e->id }}" @selected(old('equipment_id', $ticket->equipment_id) == $e->id)>{{ $e->name }}</option>@endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-nuit-800">Chambre
                <select name="room_id" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                    <option value="">—</option>
                    @foreach ($rooms as $r)<option value="{{ $r->id }}" @selected(old('room_id', $ticket->room_id) == $r->id)>Ch. {{ $r->number }}</option>@endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-nuit-800">Priorité
                <select name="priority" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                    @foreach ($priorities as $k => $label)<option value="{{ $k }}" @selected(old('priority', $ticket->priority) === $k)>{{ $label }}</option>@endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-nuit-800">Technicien
                <select name="assigned_to" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                    <option value="">— non affecté —</option>
                    @foreach ($technicians as $u)<option value="{{ $u->id }}" @selected(old('assigned_to', $ticket->assigned_to) == $u->id)>{{ $u->name }}</option>@endforeach
                </select>
            </label>
        </div>

        @if ($ticket->exists)
            <div class="grid gap-4 border-t border-sable-200 pt-4 sm:grid-cols-3">
                <label class="text-sm font-semibold text-nuit-800">Statut
                    <select name="status" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                        @foreach ($statuses as $k => $label)<option value="{{ $k }}" @selected(old('status', $ticket->status) === $k)>{{ $label }}</option>@endforeach
                    </select>
                </label>
                <label class="text-sm font-semibold text-nuit-800">Main d’œuvre (FCFA)
                    <input type="number" name="labor_cost" value="{{ old('labor_cost', $ticket->labor_cost) }}" min="0" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                </label>
                <label class="text-sm font-semibold text-nuit-800">Pièces (FCFA)
                    <input type="number" name="parts_cost" value="{{ old('parts_cost', $ticket->parts_cost) }}" min="0" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                </label>
                <label class="text-sm font-semibold text-nuit-800 sm:col-span-3">Résolution
                    <textarea name="resolution" rows="2" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">{{ old('resolution', $ticket->resolution) }}</textarea>
                </label>
            </div>
        @endif

        <div>
            <button class="rounded-full bg-terracotta-500 px-6 py-2.5 text-sm font-semibold text-white hover:bg-terracotta-600">
                {{ $ticket->exists ? 'Enregistrer' : 'Créer le ticket' }}
            </button>
        </div>
    </form>

    @if ($ticket->exists && $ticket->reporter)
        <p class="mt-3 text-xs text-nuit-400">Signalé par {{ $ticket->reporter->name }} le {{ $ticket->created_at->format('d/m/Y H:i') }}. Coût total : {{ money($ticket->totalCost()) }}.</p>
    @endif
</div>
@endsection
