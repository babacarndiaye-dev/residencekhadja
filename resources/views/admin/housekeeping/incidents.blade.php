@extends('admin.layout')
@section('title', 'Incidents housekeeping')

@section('content')
<div class="flex flex-col gap-6">
    <a href="{{ route('admin.housekeeping.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Plan de ménage</a>

    @if (auth()->user()->hasRole('housekeeping', 'reception'))
    <form method="POST" action="{{ route('admin.housekeeping.incidents.store') }}" class="grid gap-3 rounded-2xl bg-white p-5 shadow-card sm:grid-cols-2">
        @csrf
        <h2 class="font-display text-lg font-semibold text-nuit-900 sm:col-span-2">Signaler un incident</h2>
        <label class="text-sm font-semibold text-nuit-800">Chambre
            <select name="room_id" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                <option value="">— zone commune —</option>
                @foreach ($rooms as $room)<option value="{{ $room->id }}">Ch. {{ $room->number }}</option>@endforeach
            </select>
        </label>
        <label class="text-sm font-semibold text-nuit-800">Catégorie
            <select name="category" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                @foreach ($categories as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
            </select>
        </label>
        <label class="text-sm font-semibold text-nuit-800 sm:col-span-2">Description
            <textarea name="description" rows="2" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal"></textarea>
        </label>
        <label class="flex items-center gap-2 text-sm text-nuit-600 sm:col-span-2">
            <input type="checkbox" name="raise_ticket" value="1" class="rounded border-nuit-300 text-terracotta-500">
            Ouvrir aussi un ticket de maintenance (automatique pour « Problème technique »)
        </label>
        <div class="sm:col-span-2">
            <button class="rounded-full bg-terracotta-500 px-6 py-2.5 text-sm font-semibold text-white hover:bg-terracotta-600">Enregistrer</button>
        </div>
    </form>
    @endif

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Chambre</th><th class="px-4 py-3">Catégorie</th><th class="px-4 py-3">Description</th><th class="px-4 py-3">Ticket</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody>
                @forelse ($incidents as $inc)
                    <tr class="border-t border-sable-200">
                        <td class="px-4 py-3 text-xs text-nuit-500">{{ $inc->created_at->format('d/m H:i') }}</td>
                        <td class="px-4 py-3">{{ $inc->room ? 'Ch. '.$inc->room->number : 'Commun' }}</td>
                        <td class="px-4 py-3">{{ $inc->categoryLabel() }}</td>
                        <td class="px-4 py-3 max-w-xs text-xs text-nuit-600">{{ \Illuminate\Support\Str::limit($inc->description, 90) }}</td>
                        <td class="px-4 py-3 text-xs">
                            @if ($inc->ticket)<a href="{{ route('admin.maintenance.ticket', $inc->ticket) }}" class="text-terracotta-600 hover:underline">{{ $inc->ticket->reference }}</a>@else — @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($inc->status === 'open')<span class="rounded-full bg-laiton-100 px-2.5 py-0.5 text-xs font-semibold text-laiton-600">Ouvert</span>
                            @else<span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Résolu</span>@endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($inc->status === 'open' && auth()->user()->hasRole('housekeeping', 'reception'))
                                <form method="POST" action="{{ route('admin.housekeeping.incidents.resolve', $inc) }}">
                                    @csrf
                                    <button class="rounded-full border border-nuit-200 px-3 py-1 text-xs font-semibold text-nuit-600 hover:border-terracotta-400">Clôturer</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-nuit-400">Aucun incident.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $incidents->links() }}</div>
</div>
@endsection
