@extends('admin.layout')
@section('title', 'Journal de synchronisation')

@section('content')
<div class="flex flex-col gap-5">
    <a href="{{ route('admin.distribution.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Distribution</a>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Horodatage</th><th class="px-4 py-3">Canal</th><th class="px-4 py-3">Action</th><th class="px-4 py-3">Période</th><th class="px-4 py-3 text-right">Enreg.</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3">Message</th></tr>
            </thead>
            <tbody>
                @forelse ($logs as $l)
                    <tr class="border-t border-sable-200">
                        <td class="px-4 py-2 text-xs text-nuit-500">{{ $l->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-2">{{ $l->channel->name }}</td>
                        <td class="px-4 py-2">{{ $l->actionLabel() }}</td>
                        <td class="px-4 py-2 text-xs text-nuit-500">{{ $l->range_start ? $l->range_start->format('d/m') : '' }}@if($l->range_end) → {{ $l->range_end->format('d/m') }}@endif</td>
                        <td class="px-4 py-2 text-right">{{ $l->records }}</td>
                        <td class="px-4 py-2"><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $l->status === 'ok' ? 'bg-emerald-100 text-emerald-800' : 'bg-terracotta-100 text-terracotta-800' }}">{{ $l->status }}</span></td>
                        <td class="px-4 py-2 text-xs text-nuit-500">{{ $l->message }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-nuit-400">Journal vide.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $logs->links() }}</div>
</div>
@endsection
