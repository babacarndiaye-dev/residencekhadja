<!doctype html>
@php
    $h = config('hotel');
    $titles = [
        'travail' => 'ATTESTATION DE TRAVAIL',
        'salaire' => 'ATTESTATION DE SALAIRE',
        'fin_contrat' => 'CERTIFICAT DE FIN DE CONTRAT',
    ];
    $name = e($employee->civility.' '.$employee->fullName());
    $poste = trim(($employee->position->title ?? '').($employee->department ? ' — '.$employee->department->name : ''), ' —');
    $start = $contract?->start_date;
    $end = $employee->termination_date ?? $contract?->end_date;

    $verb = $type === 'fin_contrat' ? 'a été' : 'est';
    $intro = "<strong>{$name}</strong>, matricule <strong>".e($employee->matricule).'</strong>, ';
    if ($employee->national_id) {
        $intro .= 'titulaire de la pièce d’identité n° '.e($employee->national_id).', ';
    }
    if ($employee->birth_date) {
        $intro .= 'né(e) le '.e($employee->birth_date->translatedFormat('d F Y')).', ';
    }
    $intro .= "{$verb} employé(e) au sein de notre établissement";
    if ($poste) {
        $intro .= ' en qualité de <strong>'.e($poste).'</strong>';
    }
    if ($contract) {
        $intro .= ' sous contrat '.e($contract->typeLabel());
    }
    if ($start) {
        $intro .= ' depuis le <strong>'.e($start->translatedFormat('d F Y')).'</strong>';
    }
    if ($type === 'fin_contrat' && $end) {
        $intro .= ' jusqu’au <strong>'.e($end->translatedFormat('d F Y')).'</strong>';
    }
    $intro .= '.';
@endphp
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $titles[$type] }} — {{ $employee->fullName() }}</title>
    @vite(['resources/css/app.css'])
    <style>@media print { .no-print { display: none !important; } body { background: #fff; } }</style>
</head>
<body class="bg-sable-100 p-6 text-ink">
<div class="mx-auto max-w-2xl">
    <div class="no-print mb-4 flex justify-between">
        <a href="{{ route('admin.hr.employees.show', $employee) }}" class="text-sm text-nuit-600 hover:text-terracotta-600">← Retour à la fiche</a>
        <button onclick="window.print()" class="rounded bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Imprimer / PDF</button>
    </div>

    <div class="rounded-2xl bg-white p-10 shadow-card leading-relaxed">
        <div class="flex items-center gap-3 border-b border-sable-200 pb-4">
            <img src="{{ \App\Support\Branding::logo() }}" alt="" width="44" height="44" class="h-11 w-11">
            <div class="text-sm">
                <p class="font-display text-lg font-semibold text-nuit-900">{{ $h['name'] }}</p>
                <p class="text-nuit-500">{{ $h['contact']['address_line'] ?? '' }} · {{ $h['city'] ?? '' }}</p>
                <p class="text-nuit-500">{{ $h['contact']['phone'] ?? '' }} · {{ $h['contact']['email'] ?? '' }}</p>
            </div>
        </div>

        <h1 class="mt-8 text-center font-display text-xl font-semibold tracking-wide text-nuit-900">{{ $titles[$type] }}</h1>

        <div class="mt-8 space-y-4 text-sm text-nuit-800">
            <p>Je soussigné(e), la Direction des Ressources Humaines de l’{{ $h['name'] }}, atteste que :</p>
            <p class="pl-4">{!! $intro !!}</p>

            @if ($type === 'salaire' && $contract)
                <p>
                    {{ $employee->civility }} {{ $employee->last_name }} perçoit une rémunération de base mensuelle brute de
                    <strong>{{ number_format($contract->base_salary, 0, ',', ' ') }} FCFA</strong>@if ($lastPayslip), pour un net à payer de <strong>{{ number_format($lastPayslip->net, 0, ',', ' ') }} FCFA</strong> au titre de {{ $lastPayslip->run?->periodLabel() }}@endif.
                </p>
            @endif

            @if ($type === 'fin_contrat')
                @php
                    $fin = 'Le contrat a pris fin';
                    if ($employee->terminationLabel()) {
                        $fin .= ' pour motif : <strong>'.e(mb_strtolower($employee->terminationLabel())).'</strong>';
                    }
                    $fin .= '. '.e($employee->civility.' '.$employee->last_name).' est libre de tout engagement envers notre établissement.';
                @endphp
                <p>{!! $fin !!}</p>
            @endif

            <p>Cette attestation est délivrée à l’intéressé(e) pour servir et valoir ce que de droit.</p>
        </div>

        <div class="mt-12 flex justify-between text-sm">
            <p class="text-nuit-500">Fait à {{ $h['city'] ?? 'Thiès' }}, le {{ now()->translatedFormat('d F Y') }}</p>
            <div class="text-center">
                <p class="text-nuit-500">La Direction des Ressources Humaines</p>
                <div class="mt-14 border-t border-nuit-300 pt-1 text-xs text-nuit-400">Signature et cachet</div>
            </div>
        </div>
    </div>
</div>
@if (request('print'))<script>window.addEventListener('load', function () { window.print(); });</script>@endif
</body>
</html>
