@extends('admin.layout')
@section('title', 'Exercices comptables')

@php $isDirection = auth()->user()->hasRole('direction'); @endphp

@section('content')
<div class="mx-auto flex max-w-2xl flex-col gap-4">
    <a href="{{ route('admin.accounting.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Comptabilité</a>

    <p class="text-sm text-nuit-500">
        Clôturer un exercice regroupe les comptes de produits et de charges dans le compte de résultat
        ({{ config('accounting.result_account') }}), fige le résultat et <strong>verrouille toute écriture datée dans la période</strong>.
        Les comptes de bilan sont reportés automatiquement (grand livre cumulatif).
    </p>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr><th class="px-4 py-3">Exercice</th><th class="px-4 py-3">Période</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3 text-right">Résultat</th><th></th></tr>
            </thead>
            <tbody>
                @foreach ($years as $fy)
                    <tr class="border-t border-sable-200 align-middle">
                        <td class="px-4 py-3 font-semibold text-nuit-900">{{ $fy->label }}</td>
                        <td class="px-4 py-3 text-xs text-nuit-500">{{ $fy->starts_on->format('d/m/Y') }} → {{ $fy->ends_on->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $fy->isClosed() ? 'bg-nuit-100 text-nuit-700' : 'bg-emerald-100 text-emerald-800' }}">{{ $fy->statusLabel() }}</span>
                            @if ($fy->closedBy)<span class="block text-[11px] text-nuit-400">par {{ $fy->closedBy->name }} le {{ $fy->closed_at?->format('d/m/Y') }}</span>@endif
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $fy->result_amount !== null ? money($fy->result_amount) : '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($isDirection && $fy->isOpen())
                                <form method="POST" action="{{ route('admin.accounting.fiscal_years.close', $fy) }}" onsubmit="return confirm('Clôturer définitivement l’exercice {{ $fy->label }} ? Les écritures de la période seront verrouillées.')">
                                    @csrf
                                    <button class="rounded-lg bg-nuit-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-nuit-800">Clôturer</button>
                                </form>
                            @elseif ($isDirection && $fy->isClosed())
                                <form method="POST" action="{{ route('admin.accounting.fiscal_years.reopen', $fy) }}" onsubmit="return confirm('Ré-ouvrir l’exercice {{ $fy->label }} ? L’écriture de clôture sera supprimée.')">
                                    @csrf
                                    <button class="text-xs font-semibold text-terracotta-600 hover:underline">Ré-ouvrir</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @unless ($isDirection)
        <p class="text-xs text-nuit-400">La clôture est réservée à la direction.</p>
    @endunless
</div>
@endsection
