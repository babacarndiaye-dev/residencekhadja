<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Carte · {{ $e->fullName() }}</title>
    @vite(['resources/css/app.css'])
    @include('admin.hr.partials.badge-style')
</head>
<body>
    <div class="toolbar no-print">
        <a href="{{ route('admin.hr.employees.show', $e) }}">&larr; Fiche de {{ $e->fullName() }}</a>
        <span style="font-size:13px;color:#77878f;">{{ $e->matricule }} · {{ $e->department->name ?? '—' }}</span>
        <button onclick="window.print()">Imprimer la carte</button>
    </div>

    <p class="hint no-print">
        Carte de {{ $e->fullName() }} — format standard <strong>85,6 × 54 mm (CR80)</strong>.
        À l'impression : échelle <strong>100 %</strong>, sans « ajuster à la page ».
    </p>

    <div class="sheet sheet--one">
        @include('admin.hr.partials.badge-card', ['e' => $e])
    </div>

    @if (request()->boolean('print'))
        <script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
    @endif
</body>
</html>
