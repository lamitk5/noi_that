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

Alpine.data('chatWidget', () => ({
    open: false,
    auth: false,
    chatId: null,
    messages: [],
    draft: '',
    loading: false,
    sending: false,
    error: '',
    lastId: 0,
    timer: null,
    unread: 0,

    async init() {
        this.auth = this.$root.dataset.auth === '1';
        this.$nextTick(() => this.scrollToBottom(false));
        if (this.auth) {
            try {
                const res = await fetch('/api/chat/session', { headers: this.headers() });
                if (res.ok) {
                    const data = await res.json();
                    if (data.success) {
                        this.chatId = data.chat_id;
                        this.unread = Number(data.unread) || 0;
                    }
                }
            } catch (e) { /* ignore */ }
        }
    },

    csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    },

    headers() {
        return {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': this.csrf(),
        };
    },

    async toggle() {
        this.open = !this.open;
        if (this.open) {
            this.unread = 0;
            await this.bootstrap();
            this.startPolling();
        } else {
            this.stopPolling();
        }
    },

    async bootstrap() {
        if (!this.auth) return;
        this.loading = true;
        this.error = '';
        try {
            if (!this.chatId) {
                const res = await fetch('/api/chat/session', { headers: this.headers() });
                const data = await res.json();
                if (res.status === 401) {
                    this.error = 'Vui lòng đăng nhập để trò chuyện.';
                    return;
                }
                if (data.success) {
                    this.chatId = data.chat_id;
                }
            }
            if (this.chatId) {
                await this.fetchMessages();
            }
        } catch (e) {
            this.error = 'Không thể kết nối. Vui lòng thử lại.';
        } finally {
            this.loading = false;
        }
    },

    async fetchMessages() {
        if (!this.chatId) return;
        try {
            const url = `/api/chat/${this.chatId}/messages?after=${this.lastId}`;
            const res = await fetch(url, { headers: this.headers() });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.success || !data.messages.length) return;
            const known = new Set(this.messages.map((m) => m.id));
            data.messages.forEach((m) => {
                if (!known.has(m.id)) {
                    this.messages.push(m);
                    this.lastId = Math.max(this.lastId, m.id);
                }
            });
            if (this.open) {
                this.unread = 0;
            }
            this.scrollToBottom();
        } catch (e) { /* ignore */ }
    },

    startPolling() {
        this.stopPolling();
        this.timer = window.setInterval(() => this.fetchMessages(), 4000);
    },

    stopPolling() {
        if (this.timer) {
            window.clearInterval(this.timer);
            this.timer = null;
        }
    },

    async send() {
        const text = this.draft.trim();
        if (!text || !this.chatId || this.sending) return;
        this.sending = true;
        this.error = '';
        try {
            const res = await fetch(`/api/chat/${this.chatId}/message`, {
                method: 'POST',
                headers: { ...this.headers(), 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text }),
            });
            const data = await res.json().catch(() => ({}));
            if (res.status === 419) {
                this.error = 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.';
                return;
            }
            if (res.status === 401) {
                this.error = 'Vui lòng đăng nhập để trò chuyện.';
                return;
            }
            if (res.status === 422 || !data.success) {
                this.error = data.message || 'Không gửi được tin nhắn.';
                return;
            }
            this.draft = '';
            const m = data.message;
            if (!this.messages.some((x) => x.id === m.id)) {
                this.messages.push({ ...m, role: 'user' });
                this.lastId = Math.max(this.lastId, m.id);
            }
            this.scrollToBottom();
        } catch (e) {
            this.error = 'Không gửi được tin nhắn. Vui lòng thử lại.';
        } finally {
            this.sending = false;
        }
    },

    scrollToBottom(smooth = true) {
        this.$nextTick(() => {
            const el = this.$refs.list;
            if (!el) return;
            el.scrollTo({ top: el.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
        });
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

    function updateWishlistCounters(count) {
        document.querySelectorAll('.wishlist-badge-count').forEach((badge) => {
            badge.textContent = count;
            if (count > 0) {
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        });
        const totalText = document.querySelector('#wishlist-total-count');
        if (totalText) {
            totalText.textContent = count;
        }
    }

    document.querySelectorAll('.wishlist-button').forEach((button) => {
        button.addEventListener('click', async (event) => {
            event.preventDefault();
            const url = button.dataset.wishlistUrl;
            if (!url) {
                button.classList.toggle('is-active');
                return;
            }
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token || '',
                    },
                });
                const data = await response.json();
                if (response.status === 401) {
                    window.location.href = data.redirect || '/login';
                    return;
                }
                if (data.success) {
                    button.classList.toggle('is-active', !!data.in_wishlist);
                    if (typeof data.count === 'number') {
                        updateWishlistCounters(data.count);
                    }
                    const msg = data.message || (data.in_wishlist ? 'Đã thêm vào danh sách yêu thích!' : 'Đã xóa khỏi danh sách yêu thích!');
                    window.Toast?.success(msg);
                } else if (data.message) {
                    window.Toast?.error(data.message);
                }
            } catch (e) {
                button.classList.toggle('is-active');
            }
        });
    });

    document.querySelectorAll('.wishlist-detail-btn').forEach((btn) => {
        btn.addEventListener('click', async (event) => {
            event.preventDefault();
            const url = btn.dataset.wishlistUrl;
            if (!url) return;
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token || '',
                    },
                });
                const data = await response.json();
                if (response.status === 401) {
                    window.location.href = data.redirect || '/login';
                    return;
                }
                if (data.success) {
                    const inWishlist = !!data.in_wishlist;
                    btn.classList.toggle('is-active', inWishlist);
                    btn.classList.toggle('border-rose-300', inWishlist);
                    btn.classList.toggle('bg-rose-50/50', inWishlist);
                    btn.classList.toggle('text-rose-600', inWishlist);
                    btn.classList.toggle('border-ui-border', !inWishlist);
                    btn.classList.toggle('bg-surface', !inWishlist);
                    btn.classList.toggle('text-muted', !inWishlist);

                    const icon = btn.querySelector('.wishlist-icon') || btn.querySelector('svg');
                    if (icon) {
                        icon.setAttribute('fill', inWishlist ? 'currentColor' : 'none');
                        icon.classList.toggle('fill-current', inWishlist);
                        icon.classList.toggle('text-rose-600', inWishlist);
                    }

                    const label = btn.querySelector('.wishlist-btn-text');
                    if (label) {
                        label.textContent = inWishlist ? 'Đã lưu trong yêu thích' : 'Thêm vào danh sách yêu thích';
                    }

                    if (typeof data.count === 'number') {
                        updateWishlistCounters(data.count);
                    }
                    window.Toast?.success(data.message || (inWishlist ? 'Đã thêm vào danh sách yêu thích!' : 'Đã xóa khỏi danh sách yêu thích!'));
                } else if (data.message) {
                    window.Toast?.error(data.message);
                }
            } catch (e) {
                console.error(e);
            }
        });
    });

    document.querySelectorAll('.wishlist-remove-btn').forEach((btn) => {
        btn.addEventListener('click', async (event) => {
            event.preventDefault();
            const url = btn.dataset.removeUrl;
            const productId = btn.dataset.productId;
            if (!url) {
                btn.closest('form')?.submit();
                return;
            }
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            try {
                const formData = new FormData();
                if (token) formData.append('_token', token);
                formData.append('_method', 'DELETE');

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token || '',
                        'X-HTTP-Method-Override': 'DELETE',
                    },
                    body: formData,
                });
                const data = await response.json();
                if (response.status === 401) {
                    window.location.href = data.redirect || '/login';
                    return;
                }
                if (data.success) {
                    const card = document.querySelector(`#wishlist-item-${productId}`) || btn.closest('article');
                    if (card) {
                        card.style.transition = 'opacity 300ms ease, transform 300ms ease';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            card.remove();
                            const grid = document.querySelector('#wishlist-grid');
                            if (grid && grid.querySelectorAll('article').length === 0) {
                                document.querySelector('#wishlist-empty-placeholder')?.classList.remove('hidden');
                            }
                        }, 300);
                    }
                    if (typeof data.count === 'number') {
                        updateWishlistCounters(data.count);
                    }
                    window.Toast?.success(data.message || 'Đã xóa khỏi danh sách yêu thích!');
                } else if (data.message) {
                    window.Toast?.error(data.message);
                }
            } catch (e) {
                btn.closest('form')?.submit();
            }
        });
    });

    document.querySelectorAll('.quick-add').forEach((button) => {
        button.addEventListener('click', () => {
            window.Toast?.success('Đã thêm sản phẩm vào giỏ hàng!');
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
