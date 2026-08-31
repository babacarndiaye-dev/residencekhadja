@extends('admin.layout')
@section('title', 'Calendrier ARI')

@section('content')
<div class="flex flex-col gap-5">
    <a href="{{ route('admin.distribution.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Distribution</a>

    <div class="flex flex-wrap items-center gap-2">
        @foreach ($categories as $cat)
            <a href="{{ route('admin.distribution.calendar', ['category' => $cat->slug, 'start' => $start->toDateString()]) }}"
               @class([
                   'rounded-full px-3 py-1.5 text-xs font-semibold',
                   'bg-nuit-900 text-white' => $cat->id === $category->id,
                   'border border-nuit-200 text-nuit-700' => $cat->id !== $category->id,
               ])>{{ $cat->name }}</a>
        @endforeach
        <span class="ml-auto flex gap-1 text-xs">
            <a href="{{ route('admin.distribution.calendar', ['category' => $category->slug, 'start' => $start->copy()->subDays(30)->toDateString()]) }}" class="rounded-lg border border-nuit-200 px-2 py-1">←</a>
            <a href="{{ route('admin.distribution.calendar', ['category' => $category->slug, 'start' => $start->copy()->addDays(30)->toDateString()]) }}" class="rounded-lg border border-nuit-200 px-2 py-1">→</a>
        </span>
    </div>

    {{-- Édition groupée --}}
    <form method="POST" action="{{ route('admin.distribution.calendar.update') }}" class="grid gap-3 rounded-2xl bg-white p-5 shadow-card sm:grid-cols-6">
        @csrf
        <input type="hidden" name="room_category_id" value="{{ $category->id }}">
        <label class="text-xs font-semibold text-nuit-500">Du<input type="date" name="from" value="{{ $start->toDateString() }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-2 py-1.5 text-sm"></label>
        <label class="text-xs font-semibold text-nuit-500">Au<input type="date" name="to" value="{{ $start->copy()->addDays(6)->toDateString() }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-2 py-1.5 text-sm"></label>
        <label class="text-xs font-semibold text-nuit-500">Action
            <select name="action" class="mt-1 w-full rounded-lg border border-nuit-200 px-2 py-1.5 text-sm">
                <option value="close">Stopper la vente</option>
                <option value="open">Rouvrir la vente</option>
                <option value="restrict">Restreindre…</option>
                <option value="clear">Tout réinitialiser</option>
            </select>
        </label>
        <label class="text-xs font-semibold text-nuit-500">Ch. ouvertes<input type="number" name="rooms_open" min="0" class="mt-1 w-full rounded-lg border border-nuit-200 px-2 py-1.5 text-sm"></label>
        <label class="text-xs font-semibold text-nuit-500">Séjour min<input type="number" name="min_stay" min="1" class="mt-1 w-full rounded-lg border border-nuit-200 px-2 py-1.5 text-sm"></label>
        <div class="flex flex-col gap-1 text-xs">
            <label><input type="checkbox" name="cta" value="1"> Fermé arrivée</label>
            <label><input type="checkbox" name="ctd" value="1"> Fermé départ</label>
        </div>
        <div class="sm:col-span-6"><button class="rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Appliquer</button></div>
    </form>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3 text-right">Ch. dispo</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3">Restrictions</th></tr>
            </thead>
            <tbody>
                @foreach ($grid as $cell)
                    @php $r = $cell['row']; @endphp
                    <tr class="border-t border-sable-200 {{ $cell['date']->isWeekend() ? 'bg-sable-50' : '' }}">
                        <td class="px-4 py-2">{{ $cell['date']->translatedFormat('D d/m') }}</td>
                        <td class="px-4 py-2 text-right font-semibold {{ $cell['open'] === 0 ? 'text-terracotta-600' : '' }}">{{ $cell['open'] }}</td>
                        <td class="px-4 py-2">
                            @if ($r?->stop_sell)
                                <span class="rounded-full bg-terracotta-100 px-2 py-0.5 text-xs font-semibold text-terracotta-800">stop-sell</span>
                            @else
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">ouvert</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-xs text-nuit-500">
                            @if ($r)
                                @if ($r->rooms_open !== null) plafond {{ $r->rooms_open }} · @endif
                                @if ($r->min_stay > 1) min {{ $r->min_stay }}n · @endif
                                @if ($r->max_stay) max {{ $r->max_stay }}n · @endif
                                @if ($r->cta) CTA · @endif
                                @if ($r->ctd) CTD @endif
                            @else — @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
