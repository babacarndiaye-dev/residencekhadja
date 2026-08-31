@php $routeName = $routeName ?? 'admin.bi.dashboard'; @endphp
<div class="flex flex-wrap items-center gap-2">
    @foreach (['today' => 'Aujourd’hui', '7d' => '7 jours', '30d' => '30 jours', '90d' => '90 jours', 'mtd' => 'Mois en cours'] as $k => $label)
        <a href="{{ route($routeName, array_filter(['key' => $key ?? null, 'period' => $k])) }}"
           @class([
               'rounded-full px-3 py-1.5 text-xs font-semibold',
               'bg-nuit-900 text-white' => ($period ?? '30d') === $k,
               'border border-nuit-200 text-nuit-700 hover:border-terracotta-400' => ($period ?? '30d') !== $k,
           ])>{{ $label }}</a>
    @endforeach
    <form method="GET" class="flex items-center gap-1 text-xs">
        <input type="date" name="from" value="{{ $from->toDateString() }}" class="rounded-lg border border-nuit-200 px-2 py-1">
        <input type="date" name="to" value="{{ $to->toDateString() }}" class="rounded-lg border border-nuit-200 px-2 py-1">
        <button class="rounded-full border border-nuit-200 px-3 py-1.5 font-semibold text-nuit-700">OK</button>
    </form>
</div>
