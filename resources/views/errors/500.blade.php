<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Une erreur est survenue · {{ config('hotel.name', 'Hôtel Résidence Khadija') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f8f9f7;
               color: #374249; font-family: Georgia, 'Times New Roman', serif; padding: 24px; }
        .box { max-width: 30rem; text-align: center; }
        h1 { font-size: 1.9rem; margin: 0 0 .5rem; }
        p { color: #77878f; line-height: 1.6; }
        a { display: inline-block; margin-top: 1.25rem; padding: .7rem 1.4rem; border-radius: 999px;
            background: #de6443; color: #fff; text-decoration: none; font-family: system-ui, sans-serif; font-weight: 600; }
    </style>
</head>
<body>
    <div class="box">
        <p style="letter-spacing:.2em;text-transform:uppercase;font-size:.75rem;color:#596d7a;font-family:system-ui,sans-serif;">Erreur 500</p>
        <h1>Un incident technique est survenu</h1>
        <p>Nos équipes ont été notifiées. Merci de réessayer dans un instant — ou de contacter
           la réception au {{ config('hotel.contact.phone', '') }}.</p>
        <a href="{{ url('/') }}">Retour à l'accueil</a>
    </div>
</body>
</html>
