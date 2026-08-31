@extends('admin.layout')
@section('title', 'Clients')

@section('content')
<div class="flex flex-col gap-5">
    <form method="GET" class="flex flex-wrap gap-3 rounded-2xl bg-white p-4 shadow-card">
        <input type="text" name="q" value="{{ $term }}" placeholder="Nom, e-mail, société"
               class="min-w-52 flex-1 rounded-lg border border-nuit-200 px-3 py-2 text-sm">
        <select name="filter" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <option value="">Tous</option>
            <option value="members" @selected($filter === 'members')>Membres fidélité</option>
            <option value="opted_in" @selected($filter === 'opted_in')>Consentement marketing</option>
        </select>
        <button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white hover:bg-nuit-800">Filtrer</button>
        <a href="{{ route('admin.crm.dashboard') }}" class="rounded-full border border-nuit-200 px-4 py-2 text-sm font-semibold text-nuit-700 hover:border-terracotta-400">Tableau CRM</a>
    </form>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr>
                    <th class="px-4 py-3">Nom</th>
                    <th class="px-4 py-3">Contact</th>
                    <th class="px-4 py-3">Pays</th>
                    <th class="px-4 py-3">Séjours</th>
                    <th class="px-4 py-3">Fidélité</th>
                    <th class="px-4 py-3">Marketing</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($guests as $g)
                    <tr class="border-t border-sable-200 hover:bg-sable-50">
                        <td class="px-4 py-3 font-medium text-nuit-900">{{ $g->fullName() }}@if($g->company)<span class="block text-xs text-nuit-400">{{ $g->company }}</span>@endif</td>
                        <td class="px-4 py-3 text-xs text-nuit-600">{{ $g->email }}<br>{{ $g->phone }}</td>
                        <td class="px-4 py-3">{{ $g->country }}</td>
                        <td class="px-4 py-3">{{ $g->reservations_count }}</td>
                        <td class="px-4 py-3">
                            @if ($g->loyaltyAccount)
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold text-white"
                                      style="background: {{ $g->loyaltyAccount->tier->color ?? '#596d7a' }}">
                                    {{ $g->loyaltyAccount->tier->name ?? 'Membre' }}
                                </span>
                                <span class="ml-1 text-xs text-nuit-400">{{ number_format($g->loyaltyAccount->points_balance, 0, ',', ' ') }} pts</span>
                            @else
                                <span class="text-xs text-nuit-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs {{ $g->marketing_opt_in ? 'text-emerald-700' : 'text-nuit-300' }}">{{ $g->marketing_opt_in ? 'opt-in' : 'non' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.guests.show', $g) }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Fiche 360°</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-nuit-400">Aucun client.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $guests->links() }}</div>
</div>
@endsection
