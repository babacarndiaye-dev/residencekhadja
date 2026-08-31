<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Maintenance · {{ config('hotel.name', 'Hôtel Résidence Khadija') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f8f9f7;
               color: #374249; font-family: Georgia, 'Times New Roman', serif; padding: 24px; }
        .box { max-width: 30rem; text-align: center; }
        h1 { font-size: 1.9rem; margin: 0 0 .5rem; }
        p { color: #77878f; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="box">
        <p style="letter-spacing:.2em;text-transform:uppercase;font-size:.75rem;color:#596d7a;font-family:system-ui,sans-serif;">Maintenance</p>
        <h1>Nous revenons très vite</h1>
        <p>Le site est momentanément indisponible pour une mise à jour. Merci de votre patience.</p>
        @isset($exception)<p style="font-size:.8rem;color:#a2aeb4;">{{ $exception->getMessage() }}</p>@endisset
    </div>
</body>
</html>
