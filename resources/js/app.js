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

Alpine.data('headerSearch', () => ({
    isOpen: false,
    query: '',
    loading: false,
    results: { categories: [], products: [], popular: [] },
    timer: null,
    openSearch() {
        this.isOpen = true;
        this.$nextTick(() => {
            const input = document.getElementById('header-search-input');
            input && input.focus();
        });
        if (!this.results.popular || this.results.popular.length === 0) {
            this.fetchSuggestions('');
        }
    },
    closeSearch() {
        this.isOpen = false;
    },
    onInput() {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => {
            this.fetchSuggestions(this.query);
        }, 250);
    },
    async fetchSuggestions(q) {
        this.loading = true;
        try {
            const res = await fetch(`/api/search/suggestions?q=${encodeURIComponent(q)}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (res.ok) {
                this.results = await res.json();
            }
        } catch (e) {
            console.error('Search error:', e);
        } finally {
            this.loading = false;
        }
    }
}));

Alpine.data('quickViewModal', () => ({
    open: false,
    loading: false,
    product: null,
    selectedVariant: null,
    quantity: 1,
    submitting: false,
    async openModal(productId) {
        this.open = true;
        this.loading = true;
        this.product = null;
        this.quantity = 1;
        try {
            const res = await fetch(`/api/products/${productId}/quick-view`, {
                headers: { 'Accept': 'application/json' }
            });
            if (res.ok) {
                const data = await res.json();
                this.product = data;
                this.selectedVariant = data.variants && data.variants.length > 0 ? data.variants[0] : null;
            } else {
                this.closeModal();
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Không thể tải thông tin sản phẩm', type: 'error' } }));
            }
        } catch (e) {
            this.closeModal();
        } finally {
            this.loading = false;
        }
    },
    closeModal() {
        this.open = false;
        this.product = null;
    },
    selectVariant(v) {
        this.selectedVariant = v;
        this.quantity = 1;
    },
    async addToCart() {
        if (!this.selectedVariant) return;
        this.submitting = true;
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch('/api/cart/quick-add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || ''
                },
                body: JSON.stringify({
                    variant_id: this.selectedVariant.id,
                    quantity: this.quantity
                })
            });
            const data = await res.json();
            if (res.ok && data.success) {
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: data.message || 'Đã thêm vào giỏ hàng!', type: 'success' } }));
                // Update badge if any
                const badges = document.querySelectorAll('.cart-count-badge');
                badges.forEach(b => b.textContent = data.cart_count);
                this.closeModal();
            } else {
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: data.message || 'Có lỗi xảy ra', type: 'error' } }));
            }
        } catch (e) {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Lỗi mạng khi thêm giỏ hàng', type: 'error' } }));
        } finally {
            this.submitting = false;
        }
    }
}));

Alpine.data('toastManager', () => ({
    toasts: [],
    addToast(detail) {
        const id = Date.now() + Math.random();
        const toast = {
            id,
            message: detail.message || '',
            type: detail.type || 'info',
            visible: true
        };
        this.toasts.push(toast);
        setTimeout(() => {
            this.removeToast(id);
        }, detail.duration || 3200);
    },
    removeToast(id) {
        const t = this.toasts.find(item => item.id === id);
        if (t) {
            t.visible = false;
            setTimeout(() => {
                this.toasts = this.toasts.filter(item => item.id !== id);
            }, 300);
        }
    }
}));

// Global compare helper
window.MocAnCompare = {
    getList() {
        try {
            return JSON.parse(localStorage.getItem('moc-an-compare') || '[]');
        } catch(e) {
            return [];
        }
    },
    toggle(id) {
        let list = this.getList();
        const idx = list.indexOf(id);
        if (idx !== -1) {
            list.splice(idx, 1);
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Đã xóa khỏi danh sách so sánh', type: 'info' } }));
        } else {
            if (list.length >= 4) {
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Chỉ có thể so sánh tối đa 4 sản phẩm cùng lúc.', type: 'error' } }));
                return false;
            }
            list.push(id);
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Đã thêm vào danh sách so sánh!', type: 'success' } }));
        }
        localStorage.setItem('moc-an-compare', JSON.stringify(list));
        window.dispatchEvent(new CustomEvent('compare-updated', { detail: { list } }));
        return true;
    }
};

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
