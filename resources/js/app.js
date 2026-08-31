import Alpine from 'alpinejs';

window.Alpine = Alpine;

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/*
 * Carrousel générique (hero plein écran, bande « Découvrir », témoignages).
 * Sans dépendance : translateX sur une piste .rk-track.
 *   x-data="rkCarousel({ count: 3, interval: 6000, perView: 1 })"
 */
Alpine.data('rkCarousel', (opts = {}) => ({
    count: opts.count || 1,
    perView: opts.perView || 1,
    interval: opts.interval || 0,
    labels: opts.labels || [],
    active: 0,
    timer: null,
    get pages() {
        return Math.max(1, this.count - this.perView + 1);
    },
    init() {
        if (this.interval && !reduceMotion && this.pages > 1) this.play();
    },
    play() {
        this.stop();
        this.timer = setInterval(() => this.next(), this.interval);
    },
    stop() {
        if (this.timer) clearInterval(this.timer);
        this.timer = null;
    },
    go(i) {
        this.active = (i + this.pages) % this.pages;
        if (this.interval && !reduceMotion) this.play();
    },
    next() {
        this.active = (this.active + 1) % this.pages;
    },
    prev() {
        this.active = (this.active - 1 + this.pages) % this.pages;
    },
    trackStyle() {
        return `transform: translateX(-${(100 / this.perView) * this.active}%)`;
    },
}));

Alpine.start();

/*
 * Apparition douce des sections au scroll (façon WOW.js).
 * Respecte prefers-reduced-motion (voir app.css) et se dégrade proprement
 * si IntersectionObserver n'est pas disponible.
 */
const reveals = () => document.querySelectorAll('.reveal:not(.is-visible)');

if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(
        (entries, obs) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    obs.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -10% 0px', threshold: 0.08 }
    );

    const observeAll = () => reveals().forEach((el) => io.observe(el));
    document.addEventListener('DOMContentLoaded', observeAll);
    observeAll();
} else {
    reveals().forEach((el) => el.classList.add('is-visible'));
}

/*
 * Compteurs animés (façon odometer) : <span data-count="250" data-suffix="+">.
 * Démarre au premier passage dans le viewport.
 */
function runCounter(el) {
    const target = parseFloat(el.dataset.count || '0');
    const suffix = el.dataset.suffix || '';
    const prefix = el.dataset.prefix || '';
    if (reduceMotion || !Number.isFinite(target)) {
        el.textContent = prefix + target + suffix;
        return;
    }
    const dur = 1600;
    const start = performance.now();
    const tick = (now) => {
        const p = Math.min(1, (now - start) / dur);
        const eased = 1 - Math.pow(1 - p, 3);
        el.textContent = prefix + Math.round(target * eased).toLocaleString('fr-FR') + suffix;
        if (p < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
}

function initCounters() {
    const counters = document.querySelectorAll('[data-count]');
    if (!counters.length) return;
    if ('IntersectionObserver' in window) {
        const cio = new IntersectionObserver(
            (entries, obs) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        runCounter(entry.target);
                        obs.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.4 }
        );
        counters.forEach((el) => cio.observe(el));
    } else {
        counters.forEach(runCounter);
    }
}
if (document.readyState !== 'loading') initCounters();
else document.addEventListener('DOMContentLoaded', initCounters);

/* Parallaxe très légère sur les formes décoratives [data-parallax]. */
function initParallax() {
    if (reduceMotion) return;
    const shapes = document.querySelectorAll('[data-parallax]');
    if (shapes.length) {
        let ticking = false;
        const update = () => {
            const y = window.scrollY;
            shapes.forEach((el) => {
                const speed = parseFloat(el.dataset.parallax || '0.1');
                el.style.setProperty('--py', `${(y * speed) % 200}px`);
            });
            ticking = false;
        };
        window.addEventListener(
            'scroll',
            () => {
                if (!ticking) {
                    requestAnimationFrame(update);
                    ticking = true;
                }
            },
            { passive: true }
        );
    }
}
if (document.readyState !== 'loading') initParallax();
else document.addEventListener('DOMContentLoaded', initParallax);

/* Année courante dans le pied de page */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-year]').forEach((el) => {
        el.textContent = new Date().getFullYear();
    });
});
