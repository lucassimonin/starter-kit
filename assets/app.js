/* =========================================================
   Front — header au scroll, menu mobile, ancres, reveal
   (comportements portés depuis la maquette)
   ========================================================= */

document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide?.createIcons) window.lucide.createIcons();

    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => [...r.querySelectorAll(s)];

    const year = $('#year');
    if (year) year.textContent = String(new Date().getFullYear());

    /* ---- Header : transparent sur hero, opaque au scroll ---- */
    const header = $('#siteHeader');
    const logoImg = $('#logoImg');
    const navLinks = $$('.nav-link');
    const navCta = $('.nav-cta');
    const transparent = header?.dataset.transparent === '1';

    function onScroll() {
        const scrolled = !transparent || window.scrollY > 48;
        header?.classList.toggle('nav-scrolled', scrolled);
        logoImg?.classList.toggle('brightness-0', !scrolled);
        logoImg?.classList.toggle('invert', !scrolled);
        navLinks.forEach((el) => {
            el.classList.toggle('text-sand/90', !scrolled);
            el.classList.toggle('hover:text-white', !scrolled);
            el.classList.toggle('text-ink/80', scrolled);
            el.classList.toggle('hover:text-forest', scrolled);
        });
        if (navCta) {
            navCta.className = scrolled
                ? 'nav-cta rounded-journal border border-forest/20 bg-forest px-4 py-2 text-xs font-semibold uppercase tracking-wider text-sand transition hover:bg-earth'
                : 'nav-cta rounded-journal border border-sand/30 bg-sand/10 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-sand backdrop-blur-sm transition hover:bg-sand hover:text-forest';
        }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    /* ---- Menu mobile ---- */
    const mobileBtn = $('#mobileMenuBtn');
    const mobileMenu = $('#mobileMenu');
    mobileBtn?.addEventListener('click', () => {
        const open = mobileBtn.getAttribute('aria-expanded') === 'true';
        mobileBtn.setAttribute('aria-expanded', String(!open));
        mobileMenu?.classList.toggle('hidden', open);
    });

    /* ---- Défilement doux vers les ancres ---- */
    function scrollTo(sel) {
        const el = $(sel);
        if (!el) return;
        const h = header?.offsetHeight || 0;
        window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - h - 8, behavior: 'smooth' });
    }

    $$('a[href^="#"]').forEach((a) => {
        a.addEventListener('click', (e) => {
            const id = a.getAttribute('href');
            if (!id || id === '#' || !$(id)) return;
            e.preventDefault();
            scrollTo(id);
            mobileMenu?.classList.add('hidden');
            mobileBtn?.setAttribute('aria-expanded', 'false');
        });
    });
    $$('[data-scrollto]').forEach((btn) => {
        btn.addEventListener('click', () => scrollTo(btn.getAttribute('data-scrollto')));
    });

    /* ---- Apparition au scroll ---- */
    const reveals = $$('.reveal');
    if (matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
        reveals.forEach((el) => el.classList.add('is-visible'));
    } else {
        const io = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        io.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.12 },
        );
        reveals.forEach((el) => io.observe(el));
    }
});
