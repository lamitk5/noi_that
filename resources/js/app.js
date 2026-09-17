import './bootstrap';
import Alpine from 'alpinejs';

const VALID_THEMES = ['moss', 'wood', 'cream', 'blue', 'black'];

function getInitialTheme() {
    try {
        const saved = localStorage.getItem('moc-an-theme');
        if (saved && VALID_THEMES.includes(saved)) {
            return saved;
        }
    } catch (e) {}
    return document.documentElement.dataset.theme || 'moss';
}

Alpine.data('headerSettings', () => ({
    settingsOpen: false,
    theme: getInitialTheme(),
    setTheme(newTheme) {
        if (!VALID_THEMES.includes(newTheme)) return;
        this.theme = newTheme;
        document.documentElement.dataset.theme = newTheme;
        try {
            localStorage.setItem('moc-an-theme', newTheme);
        } catch (e) {}
    },
}));

window.Alpine = Alpine;
Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('#menu-toggle');
    const mobileMenu = document.querySelector('#mobile-menu');
    const openIcon = document.querySelector('#menu-open-icon');
    const closeIcon = document.querySelector('#menu-close-icon');

    menuToggle?.addEventListener('click', () => {
        const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
        menuToggle.setAttribute('aria-expanded', String(!isOpen));
        mobileMenu?.classList.toggle('hidden');
        openIcon?.classList.toggle('hidden');
        closeIcon?.classList.toggle('hidden');
    });

    mobileMenu?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            mobileMenu.classList.add('hidden');
            menuToggle?.setAttribute('aria-expanded', 'false');
            openIcon?.classList.remove('hidden');
            closeIcon?.classList.add('hidden');
        });
    });

    document.querySelectorAll('.wishlist-button').forEach((button) => {
        button.addEventListener('click', () => {
            button.classList.toggle('is-active');
        });
    });

    let toastTimer;
    const toast = document.querySelector('#toast');
    document.querySelectorAll('.quick-add').forEach((button) => {
        button.addEventListener('click', () => {
            if (!toast) return;
            toast.classList.remove('translate-y-8', 'opacity-0');
            window.clearTimeout(toastTimer);
            toastTimer = window.setTimeout(() => {
                toast.classList.add('translate-y-8', 'opacity-0');
            }, 2200);
        });
    });

    const revealElements = document.querySelectorAll('.reveal-on-scroll');

    if ('IntersectionObserver' in window) {
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;

                const delay = entry.target.dataset.delay ?? '0';
                entry.target.style.setProperty('--reveal-delay', `${delay}ms`);
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -40px',
        });

        revealElements.forEach((element) => revealObserver.observe(element));
    } else {
        revealElements.forEach((element) => element.classList.add('is-visible'));
    }
});
