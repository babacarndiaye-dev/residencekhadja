<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Accès refusé · {{ config('hotel.name', 'Hôtel Résidence Khadija') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f8f9f7;
               color: #374249; font-family: Georgia, 'Times New Roman', serif; padding: 24px; }
        .box { max-width: 30rem; text-align: center; }
        h1 { font-size: 1.9rem; margin: 0 0 .5rem; }
        p { color: #77878f; line-height: 1.6; }
        .links { margin-top: 1.25rem; display: flex; gap: .75rem; justify-content: center; flex-wrap: wrap; }
        a { display: inline-block; padding: .7rem 1.4rem; border-radius: 999px; text-decoration: none;
            font-family: system-ui, sans-serif; font-weight: 600; font-size: .9rem; }
        a.primary { background: #de6443; color: #fff; }
        a.ghost { border: 1px solid #a2aeb4; color: #374249; }
    </style>
</head>
<body>
    <div class="box">
        <p style="letter-spacing:.2em;text-transform:uppercase;font-size:.75rem;color:#596d7a;font-family:system-ui,sans-serif;">Erreur 403</p>
        <h1>Accès refusé</h1>
        <p>Vous n’avez pas les droits nécessaires pour consulter cette page. Si vous pensez qu’il
           s’agit d’une erreur, contactez un administrateur.</p>
        <div class="links">
            <a class="primary" href="{{ url('/admin') }}">Tableau de bord</a>
            <a class="ghost" href="{{ url('/') }}">Site public</a>
        </div>
    </div>
</body>
</html>
