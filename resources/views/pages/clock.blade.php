<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#202628">
    <title>Pointage · {{ config('hotel.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @include('partials.fonts')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js" defer></script>
    <style>[x-cloak]{display:none!important}#reader video{border-radius:1rem}</style>
</head>
<body class="min-h-screen bg-nuit-900 text-white" x-data="borne(@js($matricule))" x-init="init()">

    <div class="mx-auto flex min-h-screen max-w-md flex-col gap-5 p-5">

        <header class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <img src="{{ \App\Support\Branding::logoMono() }}" alt="" width="34" height="34" class="h-9 w-9">
                <div class="leading-tight">
                    <p class="font-display text-base font-semibold">Pointage</p>
                    <p class="text-xs text-white/50" x-text="clock"></p>
                </div>
            </div>
            <button type="button" x-on:click="toggleSound()"
                    class="flex h-11 w-11 items-center justify-center rounded-full border border-white/15 text-lg"
                    :aria-pressed="soundOn" title="Activer / couper le son">
                <span x-text="soundOn ? '🔊' : '🔇'"></span>
            </button>
        </header>

        {{-- Bandeau d'activation du son (Safari/iOS exige 1 contact écran) --}}
        <div x-show="soundVeil" x-cloak x-transition
             class="animate-pulse rounded-2xl border border-laiton-400/40 bg-laiton-500/15 px-4 py-3 text-center text-sm font-semibold text-laiton-100">
            🔊 Touchez l'écran une fois pour activer les annonces vocales
        </div>

        {{-- Scanner : méthode principale --}}
        <div class="relative overflow-hidden rounded-2xl bg-black">
            <div id="reader" class="aspect-square w-full"></div>

            <div x-show="!scanning && !result" x-cloak class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-6 text-center">
                <p class="text-sm text-white/70" x-text="scanMsg || 'Caméra en cours de démarrage…'"></p>
                <button type="button" x-on:click="startScan()" class="rounded-full bg-white/10 px-4 py-2 text-sm font-semibold">Réessayer la caméra</button>
            </div>

            {{-- Carte de confirmation après scan --}}
            <div x-show="result" x-cloak x-transition
                 class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-6 text-center transition-colors"
                 :class="result && result.ok && !result.loading ? (result.action === 'in' ? 'bg-emerald-700' : 'bg-terracotta-600') : 'bg-nuit-800'">
                <template x-if="result && result.loading">
                    <div class="flex flex-col items-center gap-3">
                        <svg class="h-10 w-10 animate-spin text-white/70" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity=".25" stroke-width="3"/><path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        <p class="text-sm text-white/80">Badge reconnu…</p>
                    </div>
                </template>
                <template x-if="result && !result.loading && result.ok">
                    <div class="flex flex-col items-center gap-2">
                        <img :src="result.photo" alt="" class="h-24 w-24 rounded-full border-4 border-white/70 object-cover">
                        <p class="font-display text-2xl font-semibold" x-text="result.name"></p>
                        <p class="text-[0.65rem] font-semibold uppercase tracking-[0.3em] text-white/70" x-text="result.action === 'in' ? 'Entrée' : 'Sortie'"></p>
                        <p class="text-sm text-white/90" x-text="result.message"></p>
                        <button type="button" x-on:click="dismiss()" class="mt-2 rounded-full bg-white/15 px-4 py-1.5 text-xs font-semibold">Continuer</button>
                    </div>
                </template>
                <template x-if="result && !result.loading && !result.ok">
                    <div class="flex flex-col items-center gap-2">
                        <span class="text-4xl">⚠️</span>
                        <p class="text-sm text-white/90" x-text="result.message"></p>
                        <button type="button" x-on:click="dismiss()" class="mt-2 rounded-full bg-white/15 px-4 py-1.5 text-xs font-semibold">Réessayer</button>
                    </div>
                </template>
            </div>
        </div>

        <p class="text-center text-sm text-white/60">
            Présentez votre badge devant la caméra.
        </p>

        {{-- Secours : matricule + code --}}
        <div class="mt-auto">
            <button type="button" x-on:click="showManual = !showManual"
                    class="w-full text-center text-xs font-semibold text-white/50 underline">
                Le scan ne fonctionne pas ? Saisir le matricule et le code
            </button>

            <form x-show="showManual" x-cloak method="POST" action="/pointage"
                  class="mt-3 flex flex-col gap-3 rounded-2xl bg-white p-5 text-ink shadow-luxe">
                @csrf
                @if ($errors->any())
                    <div class="rounded-lg border border-terracotta-300 bg-terracotta-50 px-3 py-2 text-sm text-terracotta-800">{{ $errors->first() }}</div>
                @endif
                <label class="flex flex-col gap-1 text-sm font-semibold text-nuit-800">
                    Matricule
                    <input type="text" name="matricule" value="{{ old('matricule', $matricule) }}" required autocomplete="off"
                           class="rounded-xl border border-nuit-200 px-4 py-3 text-lg tracking-wider focus:border-terracotta-500 focus:ring-2 focus:ring-terracotta-500/20 focus:outline-none">
                </label>
                <label class="flex flex-col gap-1 text-sm font-semibold text-nuit-800">
                    Code personnel
                    <input type="password" name="pin" inputmode="numeric" required autocomplete="off"
                           class="rounded-xl border border-nuit-200 px-4 py-3 text-lg tracking-[0.4em] focus:border-terracotta-500 focus:ring-2 focus:ring-terracotta-500/20 focus:outline-none">
                </label>
                <button type="submit" class="rounded-full bg-nuit-900 px-6 py-3.5 text-base font-semibold text-white hover:bg-terracotta-500">
                    Pointer entrée / sortie
                </button>
            </form>
        </div>
    </div>

    @if (session('status'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
             class="fixed inset-x-0 bottom-4 mx-auto max-w-md px-5">
            <div class="rounded-2xl border border-laiton-300 bg-laiton-50 p-4 text-center text-sm font-medium text-nuit-900">
                {{ session('status') }}
            </div>
        </div>
    @endif

    <script>
        function borne(prefill) {
            return {
                soundOn: true,
                scanning: false,
                scanMsg: '',
                showManual: false,
                result: null,
                clock: '',
                qr: null,
                busy: false,
                speechReady: false,
                soundVeil: false,
                resumeTimer: null,

                init() {
                    try { this.soundOn = localStorage.getItem('borne_sound') !== '0'; } catch (e) {}
                    this.tick(); setInterval(() => this.tick(), 15000);

                    // Caméra : démarrage automatique à l'ouverture.
                    this.$nextTick(() => this.startScan());

                    // Son : automatique là où le navigateur l'autorise (Android, ordinateur).
                    // Sur iOS/Safari la synthèse vocale exige un 1er contact écran — on
                    // affiche alors un bandeau et on débloque au premier toucher.
                    const iOS = /iP(hone|od|ad)/.test(navigator.userAgent)
                        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
                    this.unlockSpeech();
                    this.soundVeil = iOS && this.soundOn;

                    const activate = () => {
                        this.unlockSpeech();
                        if (this.soundVeil) { this.soundVeil = false; this.say('Annonces activées'); }
                    };
                    ['pointerdown', 'touchstart', 'click'].forEach(
                        (ev) => document.addEventListener(ev, activate, { once: true })
                    );
                },

                tick() {
                    this.clock = new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' })
                        + ' · ' + new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                },

                unlockSpeech() {
                    if (this.speechReady || !('speechSynthesis' in window)) return;
                    try {
                        const u = new SpeechSynthesisUtterance(' ');
                        u.lang = 'fr-FR'; u.volume = 0;
                        speechSynthesis.speak(u);
                        this.speechReady = true;
                    } catch (e) {}
                },

                toggleSound() {
                    this.soundOn = !this.soundOn;
                    try { localStorage.setItem('borne_sound', this.soundOn ? '1' : '0'); } catch (e) {}
                    this.unlockSpeech();
                    this.soundVeil = false;
                    if (this.soundOn) this.say('Son activé');
                },

                say(text) {
                    if (!this.soundOn || !text || !('speechSynthesis' in window)) return;
                    try {
                        speechSynthesis.cancel();
                        const u = new SpeechSynthesisUtterance(String(text));
                        u.lang = 'fr-FR'; u.rate = 0.97;
                        const v = speechSynthesis.getVoices().find(x => x.lang && x.lang.toLowerCase().startsWith('fr'));
                        if (v) u.voice = v;
                        speechSynthesis.speak(u);
                    } catch (e) {}
                },

                startScan() {
                    this.unlockSpeech();
                    if (typeof Html5Qrcode === 'undefined') { this.scanMsg = 'Scanner indisponible sur cet appareil.'; this.showManual = true; return; }
                    if (this.scanning) return;
                    this.scanMsg = '';
                    this.qr = this.qr || new Html5Qrcode('reader');
                    this.qr.start({ facingMode: 'environment' }, { fps: 10, qrbox: 240 },
                        (text) => this.onScan(text), () => {})
                        .then(() => { this.scanning = true; })
                        .catch(() => { this.scanMsg = "Impossible d'accéder à la caméra — utilisez la saisie manuelle."; this.showManual = true; });
                },

                dismiss() {
                    clearTimeout(this.resumeTimer);
                    this.result = null;
                    this.busy = false;
                    try { speechSynthesis.cancel(); } catch (e) {}
                    try { this.qr && this.qr.resume(); } catch (e) {}
                },

                onScan(text) {
                    if (this.busy) return;

                    // Le badge encode un chemin signé /pointage/scan/...?signature=...
                    // résolu sur l'origine réelle de la borne (peu importe l'IP / le port).
                    var path;
                    try {
                        var u = new URL(text, window.location.origin);
                        path = u.pathname + u.search;
                    } catch (e) { path = String(text); }

                    if (!/^\/pointage\/scan\//.test(path)) {
                        this.busy = true;
                        this.result = { ok: false, loading: false, message: "Badge non reconnu. Saisissez votre matricule." };
                        this.say('Badge non reconnu.');
                        this.resumeTimer = setTimeout(() => this.dismiss(), 5000);
                        return;
                    }

                    this.busy = true;
                    this.result = { ok: true, loading: true };
                    try { this.qr.pause(true); } catch (e) {}

                    // On récupère le résultat en AJAX pour rester sur la borne et
                    // ANNONCER le message à voix haute. Si le fetch échoue (certificat
                    // auto-signé), on bascule sur la navigation : clock-result annonce aussi.
                    fetch(path, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' })
                        .then((r) => r.json())
                        .then((data) => {
                            if (!data || typeof data.ok === 'undefined') throw new Error('bad-payload');
                            var spoken = ((data.speech ? data.speech + ' ' : '') + (data.message || '')).trim();
                            if (data.ok) {
                                this.result = {
                                    ok: true, loading: false,
                                    action: data.action, name: data.name,
                                    photo: data.photo, message: data.message,
                                };
                            } else {
                                this.result = { ok: false, loading: false, message: data.message || 'Pointage impossible.' };
                                spoken = data.message || 'Pointage impossible.';
                            }
                            this.say(spoken);
                            this.resumeTimer = setTimeout(() => this.dismiss(), 6500);
                        })
                        .catch(() => {
                            window.location.href = window.location.origin + path;
                        });
                },
            };
        }
    </script>
</body>
</html>
