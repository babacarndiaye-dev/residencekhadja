{{-- Charte typographique HRK — Montserrat (titres) + Poppins (texte).
     Auto-hébergée dans public/fonts/ : rendu identique sur la vitrine, le PMS /admin,
     la caisse POS, la PWA invité /app et la borne /pointage — et fonctionne hors-ligne.
     À inclure dans le <head> de chaque layout / page autonome. --}}
<link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/poppins-400.woff2') }}" crossorigin>
<link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/montserrat-500.woff2') }}" crossorigin>
<link rel="stylesheet" href="{{ asset('fonts/hrk-fonts.css') }}">
