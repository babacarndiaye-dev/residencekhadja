@extends('admin.layout')
@section('title', 'Grille tarifaire canaux')

@section('content')
<div class="flex flex-col gap-6">
    <a href="{{ route('admin.distribution.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Distribution</a>

    <p class="text-sm text-nuit-500">
        Majoration <b>0 %</b> = parité stricte avec le tarif direct. Une valeur positive gonfle le
        prix envoyé au canal (pour absorber sa commission).
    </p>

    @foreach ($channels as $channel)
        <form method="POST" action="{{ route('admin.distribution.rates.update', $channel) }}" class="rounded-2xl bg-white p-5 shadow-card">
            @csrf @method('PUT')
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-display text-lg font-semibold text-nuit-900">{{ $channel->name }}</h2>
                <span class="text-xs text-nuit-400">commission {{ number_format($channel->commission_rate * 100, 1) }} %</span>
            </div>
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wider text-nuit-400">
                    <tr><th class="py-2">Plan tarifaire</th><th class="py-2">Multiplicateur</th><th class="py-2">Majoration canal</th><th class="py-2">Actif</th></tr>
                </thead>
                <tbody>
                    @foreach ($plans as $plan)
                        @php $crp = $map[$channel->id][$plan->id] ?? null; @endphp
                        <tr class="border-t border-sable-100">
                            <td class="py-2">{{ $plan->name }}</td>
                            <td class="py-2 text-nuit-500">× {{ rtrim(rtrim(number_format($plan->multiplier, 2), '0'), '.') }}</td>
                            <td class="py-2">
                                <input type="number" step="0.01" min="-0.5" max="1" name="plan[{{ $plan->id }}][markup]"
                                       value="{{ $crp->markup_rate ?? 0 }}" class="w-24 rounded-lg border border-nuit-200 px-2 py-1 text-sm">
                            </td>
                            <td class="py-2"><input type="checkbox" name="plan[{{ $plan->id }}][active]" value="1" @checked($crp?->is_active ?? true)></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <button class="mt-3 rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Enregistrer {{ $channel->name }}</button>
        </form>
    @endforeach
</div>
@endsection
