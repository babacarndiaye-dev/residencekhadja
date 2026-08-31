<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Bulletin {{ $slip->employee->matricule }} — {{ $slip->run->period }}</title>
    @vite(['resources/css/app.css'])
    <style>@media print { .no-print { display: none !important; } body { background: #fff; } }</style>
</head>
<body class="bg-sable-100 p-6 text-ink">
<div class="mx-auto max-w-2xl">
    <div class="no-print mb-4 flex justify-between">
        <a href="{{ route('admin.hr.payroll.show', $slip->run) }}" class="text-sm text-nuit-600 hover:text-terracotta-600">← Retour</a>
        <button onclick="window.print()" class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Imprimer / PDF</button>
    </div>

    <div class="rounded-2xl bg-white p-8 shadow-card">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ \App\Support\Branding::logo() }}" alt="" width="44" height="44" class="h-11 w-11">
                <div>
                    <p class="font-display text-lg font-semibold text-nuit-900">{{ config('hotel.name') }}</p>
                    <p class="text-xs text-nuit-500">{{ config('hotel.contact.address_line') }} · {{ config('hotel.city') }}</p>
                </div>
            </div>
            <div class="text-right text-sm">
                <p class="font-display text-lg font-semibold text-nuit-900">BULLETIN DE PAIE</p>
                <p class="text-nuit-600">{{ $slip->run->periodLabel() }}</p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-xs uppercase tracking-wider text-nuit-400">Salarié</p>
                <p class="font-medium text-nuit-900">{{ $slip->employee->civility }} {{ $slip->employee->fullName() }}</p>
                <p class="text-nuit-600">Mat. {{ $slip->employee->matricule }}</p>
                <p class="text-nuit-600">{{ $slip->employee->position->title ?? '' }} — {{ $slip->employee->department->name ?? '' }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs uppercase tracking-wider text-nuit-400">Contrat</p>
                <p class="text-nuit-600">{{ $slip->contract?->typeLabel() ?? '—' }}</p>
                <p class="text-nuit-600">Jours travaillés : {{ $slip->worked_days }}</p>
                <p class="text-nuit-600">Heures supp. : {{ $slip->overtime_hours }} h</p>
            </div>
        </div>

        <table class="mt-6 w-full text-sm">
            <thead class="border-b border-nuit-200 text-left text-xs uppercase tracking-wider text-nuit-400">
                <tr><th class="py-2">Rubrique</th><th class="py-2 text-right">Base</th><th class="py-2 text-right">Taux</th><th class="py-2 text-right">Gain</th><th class="py-2 text-right">Retenue</th></tr>
            </thead>
            <tbody class="text-nuit-800">
                @foreach ($slip->lines as $l)
                    <tr class="border-b border-sable-100">
                        <td class="py-1.5">{{ $l->label }}</td>
                        <td class="py-1.5 text-right text-xs">{{ $l->base ? money($l->base) : '' }}</td>
                        <td class="py-1.5 text-right text-xs">{{ $l->rate ? rtrim(rtrim(number_format($l->rate, 3, ',', ' '), '0'), ',') : '' }}</td>
                        <td class="py-1.5 text-right">{{ $l->kind === 'earning' ? money($l->amount) : '' }}</td>
                        <td class="py-1.5 text-right">{{ $l->kind === 'deduction' ? money($l->amount) : '' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="font-semibold text-nuit-900"><td class="py-2" colspan="3">Totaux</td><td class="py-2 text-right">{{ money($slip->gross) }}</td><td class="py-2 text-right">{{ money($slip->total_deductions) }}</td></tr>
                <tr class="border-t-2 border-nuit-900 text-lg font-semibold text-nuit-900"><td class="py-2" colspan="4">NET À PAYER</td><td class="py-2 text-right">{{ money($slip->net) }}</td></tr>
            </tfoot>
        </table>

        <p class="mt-8 text-center text-xs text-nuit-400">
            Base imposable : {{ money($slip->taxable_gross) }} · Absences : {{ $slip->absence_days }} j ·
            Document non contractuel généré le {{ now()->format('d/m/Y') }}.
        </p>
    </div>
</div>
</body>
</html>
