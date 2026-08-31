<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#202628">
    <title>Pointage · {{ $employee->fullName() }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @include('partials.fonts')
    @vite(['resources/css/app.css'])
</head>
@php $tone = ($ok ?? false) ? (($action ?? '') === 'in' ? 'bg-emerald-700' : 'bg-terracotta-600') : 'bg-nuit-800'; @endphp
<body class="grid min-h-screen place-items-center {{ $tone }} p-6 text-center text-white">
    <div class="flex max-w-sm flex-col items-center gap-4">
        @if ($ok ?? false)
            <img src="{{ $photo }}" alt="" class="h-32 w-32 rounded-full border-4 border-white/70 object-cover">
            <p class="font-display text-3xl font-semibold">{{ $name }}</p>
            <p class="text-white/90">{{ $message }}</p>
            <p class="text-xs uppercase tracking-[0.3em] text-white/70">{{ $action === 'in' ? 'Entrée' : 'Sortie' }}</p>
        @else
            <span class="text-5xl">⚠️</span>
            <p class="font-display text-2xl font-semibold">{{ $employee->fullName() }}</p>
            <p class="text-white/90">{{ $message }}</p>
        @endif

        <a href="/pointage" class="mt-4 rounded-full bg-white/15 px-6 py-3 text-sm font-semibold">Retour à la borne</a>
    </div>

    @php $announce = trim(trim($speech ?? '').' '.trim($message ?? '')) ?: 'Pointage enregistré'; @endphp
    <script>
        (function () {
            var sound = true;
            try { sound = localStorage.getItem('borne_sound') !== '0'; } catch (e) {}

            function announce() {
                if (!sound || !('speechSynthesis' in window)) return;
                try {
                    speechSynthesis.cancel();
                    var u = new SpeechSynthesisUtterance(@json($announce));
                    u.lang = 'fr-FR'; u.rate = 0.97;
                    var v = (speechSynthesis.getVoices() || []).find(function (x) { return x.lang && x.lang.toLowerCase().indexOf('fr') === 0; });
                    if (v) u.voice = v;
                    speechSynthesis.speak(u);
                } catch (e) {}
            }

            announce();
            // Les voix arrivent parfois de façon asynchrone : on retente une fois.
            if ('speechSynthesis' in window) speechSynthesis.onvoiceschanged = announce;
            // iOS : la synthèse est bloquée sans geste — un toucher relance l'annonce.
            document.addEventListener('pointerdown', announce, { once: true });

            setTimeout(function () { window.location.href = '/pointage'; }, 7000);
        })();
    </script>
</body>
</html>
