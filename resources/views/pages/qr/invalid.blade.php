<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>QR indisponible · {{ config('hotel.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @include('partials.fonts')
    @vite(['resources/css/app.css'])
</head>
<body class="grid min-h-screen place-items-center bg-sable-50 p-6 text-ink">
    <div class="max-w-sm text-center">
        <img src="{{ \App\Support\Branding::logo() }}" alt="" width="56" height="56" class="mx-auto h-14 w-14">
        <h1 class="mt-5 font-display text-2xl font-semibold text-nuit-900">
            @if ($reason === 'inactif')
                Ce QR code n’est plus actif
            @else
                QR code introuvable
            @endif
        </h1>
        <p class="mt-2 text-sm text-ink-soft">
            @if ($reason === 'inactif')
                Cette table ou cet espace n’accepte plus de commande pour le moment.
                Demandez de l’aide à un membre de l’équipe.
            @else
                Le lien scanné ne correspond à aucune table. Vérifiez le QR code ou
                appelez un serveur.
            @endif
        </p>
        <a href="{{ route('home') }}" class="mt-6 inline-block rounded-full bg-nuit-900 px-5 py-2.5 text-sm font-semibold text-white">
            Retour à l’accueil
        </a>
    </div>
</body>
</html>
