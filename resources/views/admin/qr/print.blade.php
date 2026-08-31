<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>QR codes à imprimer</title>
    @vite(['resources/css/app.css'])
    <style>@media print { .no-print { display: none !important; } }</style>
</head>
<body class="bg-white p-6 text-ink">
    <div class="no-print mb-4 flex justify-between">
        <a href="{{ route('admin.qr.index') }}" class="text-sm text-nuit-600">← Retour</a>
        <button onclick="window.print()" class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Imprimer</button>
    </div>

    <div class="grid grid-cols-2 gap-6 sm:grid-cols-3">
        @foreach ($locations as $loc)
            <div class="flex break-inside-avoid flex-col items-center rounded-2xl border border-sable-300 p-5 text-center">
                <img src="{{ \App\Support\Branding::logo() }}" alt="" width="36" height="36" class="h-9 w-9">
                <p class="mt-2 font-display text-base font-semibold text-nuit-900">{{ config('hotel.short_name') }}</p>
                <img src="{{ route('admin.qr.svg', $loc) }}" alt="QR {{ $loc->label }}" width="200" height="200" class="my-3 h-48 w-48">
                <p class="text-sm font-semibold text-nuit-900">{{ $loc->label }}</p>
                <p class="text-xs text-nuit-400">{{ $loc->venue?->name }}</p>
                <p class="mt-2 text-xs text-nuit-500">Scannez pour voir la carte & commander</p>
            </div>
        @endforeach
    </div>
</body>
</html>
