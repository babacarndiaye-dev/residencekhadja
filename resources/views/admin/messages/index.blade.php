@extends('admin.layout')
@section('title', 'Messages')

@section('content')
<div class="flex flex-col gap-4">

    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.messages.index') }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-nuit-900 text-white' => ! $status, 'bg-white text-nuit-700 shadow-card' => $status])>
            Tous
        </a>
        @foreach (\App\Models\ContactMessage::STATUSES as $key => $label)
            <a href="{{ route('admin.messages.index', ['status' => $key]) }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-nuit-900 text-white' => $status === $key, 'bg-white text-nuit-700 shadow-card' => $status !== $key])>
                {{ $label }}@if ($key === 'new' && $newCount) · {{ $newCount }}@endif
            </a>
        @endforeach
    </div>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr>
                    <th class="px-4 py-3">Reçu</th>
                    <th class="px-4 py-3">Expéditeur</th>
                    <th class="px-4 py-3">Objet</th>
                    <th class="px-4 py-3">Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($messages as $m)
                    <tr @class(['border-t border-sable-200 align-top', 'font-medium' => $m->status === 'new'])>
                        <td class="px-4 py-3 text-xs text-nuit-400">{{ $m->created_at->format('d/m/Y') }}<span class="block">{{ $m->created_at->format('H:i') }}</span></td>
                        <td class="px-4 py-3">
                            <span class="text-nuit-900">{{ $m->name }}</span>
                            <span class="block text-xs text-nuit-400">{{ $m->email }}</span>
                        </td>
                        <td class="px-4 py-3 text-nuit-700">{{ $m->subject }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold
                                {{ ['new' => 'bg-laiton-100 text-laiton-800', 'read' => 'bg-nuit-100 text-nuit-700', 'handled' => 'bg-emerald-100 text-emerald-800'][$m->status] }}">
                                {{ $m->statusLabel() }}
                            </span>
                            @if ($m->handler)<span class="block text-xs text-nuit-400">{{ $m->handler->name }}</span>@endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.messages.show', $m) }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Ouvrir</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-nuit-400">Aucun message.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $messages->links() }}</div>
</div>
@endsection
