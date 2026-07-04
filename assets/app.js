/* =========================================================
   Front — header au scroll, menu mobile, ancres, reveal
   (comportements portés depuis la maquette)
   ========================================================= */

document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide?.createIcons) window.lucide.createIcons();

    initCookieConsent();

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

/* =========================================================
   Consentement cookies (RGPD / CNIL)
   - Analytics chargé UNIQUEMENT après acceptation
   - Choix (accepté ou refusé) conservé 6 mois
   - « Gérer les cookies » dans le footer pour changer d'avis
   ========================================================= */
function initCookieConsent() {
    const banner = document.getElementById('cookieBanner');

    const getConsent = () => document.cookie.match(/(?:^|; )cookie_consent=([^;]*)/)?.[1];
    const setConsent = (value) => {
        document.cookie = `cookie_consent=${value};path=/;max-age=${60 * 60 * 24 * 182};SameSite=Lax`;
    };

    const loadAnalytics = (id) => {
        if (!id || window.gtag) return;
        const script = document.createElement('script');
        script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(id)}`;
        document.head.appendChild(script);
        window.dataLayer = window.dataLayer || [];
        window.gtag = function () { window.dataLayer.push(arguments); };
        window.gtag('js', new Date());
        window.gtag('config', id, { anonymize_ip: true });
    };

    if (banner) {
        const analyticsId = banner.dataset.analytics;
        const consent = getConsent();

        if (consent === 'accepted') {
            loadAnalytics(analyticsId);
        } else if (!consent) {
            banner.hidden = false;
        }

        banner.querySelector('#cookieAccept')?.addEventListener('click', () => {
            setConsent('accepted');
            banner.hidden = true;
            loadAnalytics(analyticsId);
        });

        banner.querySelector('#cookieRefuse')?.addEventListener('click', () => {
            setConsent('refused');
            banner.hidden = true;
        });
    }

    // Retrait du consentement : rouvre la barre (lien footer)
    document.querySelectorAll('[data-cookie-settings]').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.cookie = 'cookie_consent=;path=/;max-age=0';
            if (banner) banner.hidden = false;
        });
    });
}
