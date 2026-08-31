{{-- Présentation pure de l'écran d'accueil (style + markup), sans logique.
     Attend $hs = App\Support\Splash::view(). Inclus par :
       · partials/splash.blade.php  (vitrine)
       · admin/splash/preview.blade.php  (aperçu back-office) --}}
<style>
    #hrk-splash {
        --hs-from: {{ $hs['background_from'] }};
        --hs-to: {{ $hs['background_to'] }};
        --hs-ink: #f4f5f3;
        --hs-accent: #de6443;
        position: fixed;
        inset: 0;
        z-index: 2147483000;
        display: grid;
        place-items: center;
        padding: 6vmin;
        overflow: hidden;
        background:
            radial-gradient(120% 120% at 50% 18%, color-mix(in srgb, var(--hs-to) 55%, transparent) 0%, transparent 60%),
            linear-gradient(160deg, var(--hs-from) 0%, color-mix(in srgb, var(--hs-from) 65%, var(--hs-to)) 55%, var(--hs-to) 100%);
        color: var(--hs-ink);
        -webkit-font-smoothing: antialiased;
        animation: hs-failsafe 0.01s linear 8s forwards;
    }

    /* Halo lumineux lent — profondeur discrète, jamais tape-à-l'œil. */
    #hrk-splash .hs-glow {
        position: absolute;
        width: 75vmax;
        height: 75vmax;
        left: 50%;
        top: 46%;
        border-radius: 50%;
        transform: translate(-50%, -50%);
        background: radial-gradient(circle, color-mix(in srgb, var(--hs-accent) 26%, transparent) 0%, transparent 62%);
        filter: blur(40px);
        opacity: 0;
        animation: hs-glow-in 1.4s ease-out 0.15s forwards;
        pointer-events: none;
    }
    #hrk-splash[data-glow="0"] .hs-glow { display: none; }

    /* Lumière rasante qui traverse l'écran une fois. */
    #hrk-splash .hs-scan {
        position: absolute;
        inset: 0;
        background: linear-gradient(105deg, transparent 42%, color-mix(in srgb, #ffffff 14%, transparent) 50%, transparent 58%);
        transform: translateX(-100%);
        animation: hs-scan 2.6s cubic-bezier(.22, 1, .36, 1) 0.3s both;
        pointer-events: none;
    }

    #hrk-splash .hs-stage {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: clamp(0.9rem, 2.4vmin, 1.6rem);
    }

    #hrk-splash .hs-logo-wrap {
        position: relative;
        width: clamp(112px, 20vmin, 180px);
        aspect-ratio: 1;
        display: grid;
        place-items: center;
    }
    #hrk-splash .hs-logo {
        width: 100%;
        height: 100%;
        object-fit: contain;
        opacity: 0;
        will-change: transform, opacity, filter;
        animation: hs-logo-in 1s cubic-bezier(.16, 1, .3, 1) both;
    }

    /* Reflet « métal premium » qui glisse sur le logo. */
    #hrk-splash .hs-sheen {
        position: absolute;
        inset: -8% -14%;
        background: linear-gradient(100deg, transparent 38%, rgba(255, 255, 255, .55) 50%, transparent 62%);
        mix-blend-mode: screen;
        opacity: 0;
        transform: translateX(-130%);
        animation: hs-sheen 1.15s ease-in 0.95s both;
        pointer-events: none;
    }

    #hrk-splash .hs-welcome {
        font-family: 'Montserrat', 'Helvetica Neue', Arial, sans-serif;
        font-weight: 300;
        letter-spacing: 0.42em;
        text-indent: 0.42em;
        text-transform: uppercase;
        font-size: clamp(0.95rem, 2.5vmin, 1.35rem);
        opacity: 0;
        animation: hs-rise 0.75s ease-out 1.15s both;
    }
    #hrk-splash .hs-rule {
        width: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--hs-accent), transparent);
        animation: hs-rule 0.7s ease-out 1.45s both;
    }
    #hrk-splash .hs-name {
        font-family: 'Montserrat', 'Helvetica Neue', Arial, sans-serif;
        font-weight: 500;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        font-size: clamp(1.15rem, 3.6vmin, 2rem);
        line-height: 1.25;
        opacity: 0;
        animation: hs-rise 0.75s ease-out 1.5s both;
    }
    #hrk-splash .hs-sig {
        font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
        font-weight: 300;
        font-style: italic;
        letter-spacing: 0.02em;
        font-size: clamp(0.8rem, 1.9vmin, 0.98rem);
        color: color-mix(in srgb, var(--hs-ink) 72%, transparent);
        opacity: 0;
        animation: hs-fade 0.9s ease-out 2s both;
    }
    #hrk-splash .hs-skip {
        position: absolute;
        left: 50%;
        bottom: 5vmin;
        transform: translateX(-50%);
        font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
        font-size: 0.7rem;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        color: color-mix(in srgb, var(--hs-ink) 45%, transparent);
        opacity: 0;
        animation: hs-fade 0.6s ease-out 2.4s both;
    }

    /* ---- Variantes de chorégraphie -------------------------------- */
    #hrk-splash[data-animation="fade"] .hs-sheen,
    #hrk-splash[data-animation="zoom"] .hs-sheen,
    #hrk-splash[data-animation="minimal"] .hs-sheen,
    #hrk-splash[data-animation="minimal"] .hs-scan,
    #hrk-splash[data-animation="minimal"] .hs-rule,
    #hrk-splash[data-animation="minimal"] .hs-sig,
    #hrk-splash[data-animation="minimal"] .hs-skip { display: none; }

    #hrk-splash[data-animation="fade"] .hs-logo { animation: hs-fade 1s ease-out both; }
    #hrk-splash[data-animation="zoom"] .hs-logo { animation: hs-zoom 0.9s cubic-bezier(.16, 1, .3, 1) both; }
    #hrk-splash[data-animation="minimal"] .hs-logo { animation: hs-fade 0.7s ease-out both; }
    #hrk-splash[data-animation="minimal"] .hs-welcome { animation-delay: 0.5s; }
    #hrk-splash[data-animation="minimal"] .hs-name { animation-delay: 0.8s; }

    /* ---- Sortie cinématique ------------------------------------- */
    #hrk-splash.is-leaving {
        animation: hs-exit 0.62s cubic-bezier(.4, 0, .2, 1) forwards;
        pointer-events: none;
    }

    @keyframes hs-logo-in {
        0%   { opacity: 0; transform: scale(.95); filter: blur(9px); }
        60%  { opacity: 1; }
        100% { opacity: 1; transform: scale(1); filter: blur(0); }
    }
    @keyframes hs-zoom {
        0%   { opacity: 0; transform: scale(.9); }
        100% { opacity: 1; transform: scale(1); }
    }
    @keyframes hs-sheen {
        0%   { opacity: 0; transform: translateX(-130%); }
        12%  { opacity: 1; }
        100% { opacity: 0; transform: translateX(130%); }
    }
    @keyframes hs-scan {
        0%   { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    @keyframes hs-glow-in { to { opacity: 1; } }
    @keyframes hs-rise {
        0%   { opacity: 0; transform: translateY(14px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    @keyframes hs-rule { to { width: clamp(120px, 24vmin, 240px); } }
    @keyframes hs-fade { to { opacity: 1; } }
    @keyframes hs-exit {
        0%   { opacity: 1; transform: scale(1); }
        100% { opacity: 0; transform: scale(1.055); visibility: hidden; }
    }
    @keyframes hs-failsafe { to { opacity: 0; visibility: hidden; pointer-events: none; } }

    /* ---- Mobile : on allège (perf / batterie) ------------------- */
    @media (max-width: 640px) {
        #hrk-splash .hs-glow { animation-duration: 0.8s; filter: blur(28px); }
        #hrk-splash .hs-scan { display: none; }
        #hrk-splash .hs-logo { animation-name: hs-zoom; }
    }

    /* ---- Accessibilité : mouvement réduit --------------------- */
    @media (prefers-reduced-motion: reduce) {
        #hrk-splash .hs-glow,
        #hrk-splash .hs-scan,
        #hrk-splash .hs-sheen { display: none !important; }
        #hrk-splash .hs-logo,
        #hrk-splash .hs-welcome,
        #hrk-splash .hs-rule,
        #hrk-splash .hs-name,
        #hrk-splash .hs-sig,
        #hrk-splash .hs-skip {
            animation: none !important;
            opacity: 1 !important;
            transform: none !important;
            filter: none !important;
            width: auto;
        }
        #hrk-splash.is-leaving { animation: none !important; opacity: 0; transition: opacity .2s linear; }
    }
</style>

<div id="hrk-splash" role="presentation" aria-hidden="true"
     data-animation="{{ $hs['animation'] }}"
     data-glow="{{ $hs['glow'] ? '1' : '0' }}"
     data-duration="{{ $hs['duration_ms'] }}">
    <div class="hs-glow"></div>
    <div class="hs-scan"></div>

    <div class="hs-stage">
        <div class="hs-logo-wrap">
            <img class="hs-logo" src="{{ $hs['logo_url'] }}" alt="" width="180" height="180" decoding="async" fetchpriority="high">
            <span class="hs-sheen"></span>
        </div>

        @if (filled($hs['welcome_text']))
            <div class="hs-welcome">{{ $hs['welcome_text'] }}</div>
        @endif
        <div class="hs-rule"></div>
        <div class="hs-name">{{ $hs['hotel_name'] }}</div>
        @if (filled($hs['signature']))
            <div class="hs-sig">{{ $hs['signature'] }}</div>
        @endif
    </div>

    <div class="hs-skip">Entrer</div>
</div>
