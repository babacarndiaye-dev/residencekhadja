@extends('admin.layout')
@section('title', 'Chambres')

@php $canEdit = auth()->user()->hasRole('reception', 'housekeeping'); @endphp

@section('content')
<div class="flex flex-col gap-5">

    <div class="flex flex-wrap gap-2">
        @foreach ($statuses as $key => $label)
            <span class="flex items-center gap-2 rounded-lg bg-white px-3 py-1.5 text-sm shadow-card">
                <x-admin.badge :status="$key" :label="$label" /><strong class="text-nuit-900">{{ $counts[$key] ?? 0 }}</strong>
            </span>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.rooms.index') }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-nuit-900 text-white' => ! $activeFloor, 'bg-white text-nuit-700 shadow-card' => $activeFloor])>Tous les étages</a>
        @foreach ($floors as $f)
            <a href="{{ route('admin.rooms.index', ['etage' => $f]) }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-nuit-900 text-white' => $activeFloor == $f, 'bg-white text-nuit-700 shadow-card' => $activeFloor != $f])>Étage {{ $f }}</a>
        @endforeach
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($rooms as $room)
            <div class="flex flex-col gap-2 rounded-2xl bg-white p-4 shadow-card">
                <a href="{{ route('admin.rooms.show', $room) }}" class="flex items-start justify-between rounded-lg -m-1 p-1 hover:bg-sable-50">
                    <div>
                        <p class="font-display text-lg font-semibold text-nuit-900">Ch. {{ $room->number }}</p>
                        <p class="text-xs text-nuit-400">{{ $room->category->name }} · étage {{ $room->floor }}</p>
                    </div>
                    <x-admin.badge :status="$room->status" />
                </a>
                @if ($canEdit)
                    <form method="POST" action="{{ route('admin.rooms.status', $room) }}" class="flex gap-2">
                        @csrf @method('POST')
                        <select name="status" class="flex-1 rounded-lg border border-nuit-200 px-2 py-1.5 text-xs">
                            @foreach ($statuses as $key => $label)
                                <option value="{{ $key }}" @selected($room->status === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="rounded-lg bg-nuit-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-nuit-800">OK</button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
