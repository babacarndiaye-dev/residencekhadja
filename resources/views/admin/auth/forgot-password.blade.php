<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Mot de passe oublié · PMS Khadija</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @include('partials.fonts')
    @vite(['resources/css/app.css'])
</head>
<body class="grid min-h-screen place-items-center bg-nuit-900 p-5 text-ink">
    <div class="w-full max-w-sm">
        <div class="mb-6 flex flex-col items-center gap-2 text-center">
            <img src="{{ \App\Support\Branding::logoMono() }}" alt="" width="48" height="48" class="h-12 w-12">
            <h1 class="font-display text-2xl font-semibold text-white">Mot de passe oublié</h1>
            <p class="text-sm text-white/60">Recevez un lien de réinitialisation par e-mail.</p>
        </div>

        <form method="POST" action="{{ route('admin.password.email') }}" class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-luxe">
            @csrf
            @if (session('status'))
                <div class="rounded-lg border border-laiton-200 bg-laiton-50 px-3 py-2 text-sm text-nuit-800">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-lg border border-terracotta-300 bg-terracotta-50 px-3 py-2 text-sm text-terracotta-800">{{ $errors->first() }}</div>
            @endif

            <label class="flex flex-col gap-1 text-sm font-semibold text-nuit-800">
                E-mail professionnel
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="rounded-xl border border-nuit-200 px-4 py-3 text-sm focus:border-terracotta-500 focus:outline-none">
            </label>
            <button class="rounded-full bg-terracotta-500 px-6 py-3 text-sm font-semibold text-white hover:bg-terracotta-600">Envoyer le lien</button>
        </form>

        <p class="mt-4 text-center text-xs text-white/40">
            <a href="{{ route('admin.login') }}" class="hover:text-white/70">← Retour à la connexion</a>
        </p>
    </div>
</body>
</html>
