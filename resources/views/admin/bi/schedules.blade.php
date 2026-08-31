@extends('admin.layout')
@section('title', 'Rapports planifiés')

@section('content')
<div class="flex flex-col gap-6">
    <a href="{{ route('admin.bi.reports') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Rapports</a>

    <section class="rounded-2xl bg-white p-5 shadow-card">
        <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Nouvelle planification</h2>
        <form method="POST" action="{{ route('admin.bi.schedules.store') }}" class="grid gap-3 sm:grid-cols-4">
            @csrf
            <label class="text-xs font-semibold text-nuit-500 sm:col-span-2">Rapport
                <select name="report_key" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    @foreach ($reports as $key => $def)<option value="{{ $key }}">{{ $def['label'] }}</option>@endforeach
                </select>
            </label>
            <label class="text-xs font-semibold text-nuit-500">Fréquence
                <select name="frequency" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    @foreach ($frequencies as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                </select>
            </label>
            <label class="text-xs font-semibold text-nuit-500">Fenêtre (jours)
                <input type="number" name="range_days" min="1" max="366" value="7" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            </label>
            <label class="text-xs font-semibold text-nuit-500 sm:col-span-3">Destinataires (e-mails séparés par des virgules)
                <input name="recipients" placeholder="direction@residence-khadija.sn, finance@…" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            </label>
            <div class="flex items-end"><button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Planifier</button></div>
        </form>
        <p class="mt-2 text-xs text-nuit-400">Exécution par <code>php artisan bi:run-schedules</code> (programmé quotidiennement à 07:00). L'envoi e-mail sera branché en production.</p>
    </section>

    <section class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Rapport</th><th class="px-4 py-3">Fréquence</th><th class="px-4 py-3">Fenêtre</th><th class="px-4 py-3">Destinataires</th><th class="px-4 py-3">Dernière exéc.</th><th class="px-4 py-3">État</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($schedules as $s)
                    <tr class="border-t border-sable-200">
                        <td class="px-4 py-3">{{ $s->reportLabel() }}</td>
                        <td class="px-4 py-3">{{ \App\Models\ReportSchedule::FREQUENCIES[$s->frequency] }}</td>
                        <td class="px-4 py-3">{{ $s->range_days }} j</td>
                        <td class="px-4 py-3 text-xs text-nuit-500">{{ implode(', ', $s->recipients ?? []) ?: '—' }}</td>
                        <td class="px-4 py-3 text-xs text-nuit-400">{{ optional($s->last_run_at)->format('d/m/y H:i') ?: 'jamais' }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $s->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-sable-200 text-nuit-500' }}">{{ $s->is_active ? 'actif' : 'suspendu' }}</span></td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.bi.schedules.toggle', $s) }}" class="inline">@csrf<button class="text-xs font-semibold text-nuit-600 hover:underline">{{ $s->is_active ? 'Suspendre' : 'Activer' }}</button></form>
                            <form method="POST" action="{{ route('admin.bi.schedules.destroy', $s) }}" class="inline" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')<button class="ml-2 text-xs font-semibold text-terracotta-600 hover:underline">Suppr.</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-nuit-400">Aucune planification.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="rounded-2xl bg-white p-5 shadow-card">
        <h2 class="mb-3 font-display text-lg font-semibold text-nuit-900">Dernières exécutions</h2>
        <table class="w-full text-sm">
            <tbody>
                @forelse ($runs as $r)
                    <tr class="border-b border-sable-100">
                        <td class="py-1.5">{{ $r->reportLabel() }}</td>
                        <td class="py-1.5 text-xs text-nuit-500">{{ $r->period_start->format('d/m') }} → {{ $r->period_end->format('d/m') }}</td>
                        <td class="py-1.5 text-right">{{ $r->row_count }} ligne(s)</td>
                        <td class="py-1.5 text-right text-xs text-nuit-400">{{ $r->created_at->format('d/m/y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td class="py-4 text-center text-nuit-400">Aucune exécution.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
@endsection
