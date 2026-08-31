@extends('admin.layout')
@section('title', 'Journal d’audit')

@section('content')
<div class="overflow-x-auto rounded-2xl bg-white shadow-card">
    <table class="w-full text-sm">
        <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
            <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Utilisateur</th><th class="px-4 py-3">Action</th><th class="px-4 py-3">Cible</th><th class="px-4 py-3">Détails</th><th class="px-4 py-3">IP</th></tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr class="border-t border-sable-200">
                    <td class="px-4 py-2.5 text-xs text-nuit-500">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                    <td class="px-4 py-2.5">{{ $log->user->name ?? 'Système' }}</td>
                    <td class="px-4 py-2.5 font-mono text-xs">{{ $log->action }}</td>
                    <td class="px-4 py-2.5 text-xs text-nuit-500">{{ class_basename($log->auditable_type ?? '') }} {{ $log->auditable_id }}</td>
                    <td class="px-4 py-2.5 text-xs text-nuit-500">{{ $log->meta ? json_encode($log->meta, JSON_UNESCAPED_UNICODE) : '' }}</td>
                    <td class="px-4 py-2.5 text-xs text-nuit-400">{{ $log->ip }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-nuit-400">Journal vide.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
