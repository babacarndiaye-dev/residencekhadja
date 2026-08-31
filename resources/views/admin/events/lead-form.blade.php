@extends('admin.layout')
@section('title', 'Nouvelle affaire')

@section('content')
<div class="mx-auto flex max-w-2xl flex-col gap-5">
    <a href="{{ route('admin.events.pipeline') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Pipeline</a>

    <form method="POST" action="{{ route('admin.events.leads.store') }}" class="grid gap-4 rounded-2xl bg-white p-6 shadow-card sm:grid-cols-2">
        @csrf
        <label class="text-xs font-semibold text-nuit-500 sm:col-span-2">Société / organisation
            <input name="company" value="{{ old('company') }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <label class="text-xs font-semibold text-nuit-500">Contact *
            <input name="contact_name" value="{{ old('contact_name') }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <label class="text-xs font-semibold text-nuit-500">Type d'événement *
            <select name="event_type" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                @foreach ($types as $k => $label)<option value="{{ $k }}" @selected(old('event_type') === $k)>{{ $label }}</option>@endforeach
            </select>
        </label>
        <label class="text-xs font-semibold text-nuit-500">E-mail
            <input type="email" name="contact_email" value="{{ old('contact_email') }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <label class="text-xs font-semibold text-nuit-500">Téléphone
            <input name="contact_phone" value="{{ old('contact_phone') }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <label class="text-xs font-semibold text-nuit-500">Date début souhaitée
            <input type="date" name="expected_start" value="{{ old('expected_start') }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <label class="text-xs font-semibold text-nuit-500">Date fin
            <input type="date" name="expected_end" value="{{ old('expected_end') }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <label class="text-xs font-semibold text-nuit-500">Participants (pax)
            <input type="number" name="pax" min="1" value="{{ old('pax') }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <label class="text-xs font-semibold text-nuit-500">Budget estimé (FCFA)
            <input type="number" name="estimated_value" min="0" step="1000" value="{{ old('estimated_value', 0) }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        </label>
        <label class="text-xs font-semibold text-nuit-500">Source
            <select name="source" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="">—</option>
                @foreach ($sources as $k => $label)<option value="{{ $k }}" @selected(old('source') === $k)>{{ $label }}</option>@endforeach
            </select>
        </label>
        <label class="text-xs font-semibold text-nuit-500">Commercial référent
            <select name="owner_id" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="">Moi</option>
                @foreach ($owners as $o)<option value="{{ $o->id }}" @selected(old('owner_id') == $o->id)>{{ $o->name }}</option>@endforeach
            </select>
        </label>
        <label class="text-xs font-semibold text-nuit-500 sm:col-span-2">Notes
            <textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">{{ old('notes') }}</textarea>
        </label>
        <div class="sm:col-span-2">
            <button class="rounded-full bg-nuit-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-nuit-800">Créer l'affaire</button>
        </div>
    </form>
</div>
@endsection
