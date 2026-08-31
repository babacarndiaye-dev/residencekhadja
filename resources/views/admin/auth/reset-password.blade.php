<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Nouveau mot de passe · PMS Khadija</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @include('partials.fonts')
    @vite(['resources/css/app.css'])
</head>
<body class="grid min-h-screen place-items-center bg-nuit-900 p-5 text-ink">
    <div class="w-full max-w-sm">
        <div class="mb-6 flex flex-col items-center gap-2 text-center">
            <img src="{{ \App\Support\Branding::logoMono() }}" alt="" width="48" height="48" class="h-12 w-12">
            <h1 class="font-display text-2xl font-semibold text-white">Nouveau mot de passe</h1>
        </div>

        <form method="POST" action="{{ route('admin.password.update') }}" class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-luxe">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            @if ($errors->any())
                <div class="rounded-lg border border-terracotta-300 bg-terracotta-50 px-3 py-2 text-sm text-terracotta-800">{{ $errors->first() }}</div>
            @endif

            <label class="flex flex-col gap-1 text-sm font-semibold text-nuit-800">
                E-mail
                <input type="email" name="email" value="{{ old('email', $email) }}" required
                       class="rounded-xl border border-nuit-200 px-4 py-3 text-sm focus:border-terracotta-500 focus:outline-none">
            </label>
            <label class="flex flex-col gap-1 text-sm font-semibold text-nuit-800">
                Nouveau mot de passe (8 caractères min.)
                <input type="password" name="password" required autocomplete="new-password"
                       class="rounded-xl border border-nuit-200 px-4 py-3 text-sm focus:border-terracotta-500 focus:outline-none">
            </label>
            <label class="flex flex-col gap-1 text-sm font-semibold text-nuit-800">
                Confirmer
                <input type="password" name="password_confirmation" required autocomplete="new-password"
                       class="rounded-xl border border-nuit-200 px-4 py-3 text-sm focus:border-terracotta-500 focus:outline-none">
            </label>
            <button class="rounded-full bg-terracotta-500 px-6 py-3 text-sm font-semibold text-white hover:bg-terracotta-600">Enregistrer</button>
        </form>
    </div>
</body>
</html>
