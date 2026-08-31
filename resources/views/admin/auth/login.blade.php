<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Connexion · PMS Khadija</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @include('partials.fonts')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-nuit-900 p-5 text-ink">
    <div class="w-full max-w-sm">
        <div class="mb-6 flex flex-col items-center gap-2 text-center">
            <img src="{{ \App\Support\Branding::logoMono() }}" alt="" width="48" height="48" class="h-12 w-12">
            <h1 class="font-display text-2xl font-semibold text-white">PMS Khadija</h1>
            <p class="text-sm text-white/60">Back-office — accès réservé au personnel</p>
        </div>

        <form method="POST" action="{{ route('admin.login.attempt') }}" class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-luxe">
            @csrf

            @if ($errors->any())
                <div class="rounded-lg border border-terracotta-300 bg-terracotta-50 px-3 py-2 text-sm text-terracotta-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <label class="flex flex-col gap-1 text-sm font-semibold text-nuit-800">
                E-mail
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="rounded-xl border border-nuit-200 px-4 py-3 text-sm focus:border-terracotta-500 focus:ring-2 focus:ring-terracotta-500/20 focus:outline-none">
            </label>

            <label class="flex flex-col gap-1 text-sm font-semibold text-nuit-800">
                Mot de passe
                <input type="password" name="password" required
                       class="rounded-xl border border-nuit-200 px-4 py-3 text-sm focus:border-terracotta-500 focus:ring-2 focus:ring-terracotta-500/20 focus:outline-none">
            </label>

            <label class="flex items-center gap-2 text-sm text-nuit-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-nuit-300 text-terracotta-500 focus:ring-terracotta-500">
                Se souvenir de moi
            </label>

            <button type="submit" class="rounded-full bg-terracotta-500 px-6 py-3 text-sm font-semibold text-white transition hover:bg-terracotta-600">
                Se connecter
            </button>

            <a href="{{ route('admin.password.request') }}" class="text-center text-xs font-semibold text-nuit-400 hover:text-terracotta-600">
                Mot de passe oublié ?
            </a>
        </form>

        <p class="mt-4 text-center text-xs text-white/40">
            Comptes de démonstration — mot de passe <code>khadija</code><br>
            direction@… · reception@… · housekeeping@… · admin@residence-khadija.sn
        </p>
    </div>
</body>
</html>
