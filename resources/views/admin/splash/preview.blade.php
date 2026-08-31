<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Aperçu — Écran d'accueil</title>
    @include('partials.fonts')
    <style>
        html, body { margin: 0; height: 100%; background: #1b2023; }
    </style>
</head>
<body>
    @include('partials.splash-visual', ['hs' => $s])
    @include('partials.splash-script', ['persist' => false])
</body>
</html>
