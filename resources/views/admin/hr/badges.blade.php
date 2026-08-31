<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Cartes professionnelles · Pointage</title>
    @vite(['resources/css/app.css'])
    @include('admin.hr.partials.badge-style')
</head>
<body>
    <div class="toolbar no-print">
        <a href="{{ route('admin.hr.dashboard') }}">&larr; RH</a>
        <form method="GET" style="display:flex;gap:8px;align-items:center;">
            <select name="department" onchange="this.form.submit()">
                <option value="">Tous les services</option>
                @foreach ($departments as $d)
                    <option value="{{ $d->id }}" @selected($activeDepartment == $d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </form>
        <button onclick="window.print()">Imprimer les cartes</button>
    </div>

    <p class="hint no-print">
        Format carte standard <strong>85,6 × 54 mm (CR80)</strong>, comme une carte bancaire.
        À l'impression : échelle <strong>100 %</strong>, sans « ajuster à la page » — 10 cartes par feuille A4.
        Le QR encode l'URL de pointage signée de l'agent.
    </p>

    <div class="sheet">
        @foreach ($employees as $e)
            @include('admin.hr.partials.badge-card', ['e' => $e])
        @endforeach
    </div>

    @if ($employees->isEmpty())
        <p class="hint">Aucun agent actif pour ce filtre.</p>
    @endif
</body>
</html>
