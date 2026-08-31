{{-- Dismiss de l'écran d'accueil. $persist = true → une seule fois par
     session de navigation (vitrine) ; false → rejoué à chaque chargement (aperçu). --}}
<script>
    (function () {
        var el = document.getElementById('hrk-splash');
        if (!el) return;

        var persist = @json($persist ?? true);
        var KEY = 'hrk:splash';

        if (persist) {
            try {
                if (sessionStorage.getItem(KEY)) { el.parentNode && el.parentNode.removeChild(el); return; }
                sessionStorage.setItem(KEY, '1');
            } catch (e) { /* navigation privée : on joue l'intro sans mémoriser */ }
        }

        var root = document.documentElement;
        var prevOverflow = root.style.overflow;
        root.style.overflow = 'hidden';

        var finished = false;
        function done() {
            if (finished) return;
            finished = true;
            root.style.overflow = prevOverflow;
            if (el.parentNode) el.parentNode.removeChild(el);
        }
        function leave() {
            if (el.classList.contains('is-leaving')) return;
            el.classList.add('is-leaving');
            el.addEventListener('animationend', function (e) { if (e.target === el) done(); });
            setTimeout(done, 1000);
        }

        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce) { setTimeout(done, 350); return; }

        var wait = parseInt(el.getAttribute('data-duration'), 10);
        if (isNaN(wait)) wait = 2600;

        var timer = setTimeout(leave, wait);
        el.addEventListener('click', function () { clearTimeout(timer); leave(); });
        window.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' || e.key === 'Enter' || e.key === ' ') { clearTimeout(timer); leave(); }
        }, { once: true });
    })();
</script>
