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
    tab: 'ai',
    aiMessages: [],
    aiDraft: '',
    aiSending: false,
    aiLoaded: false,
    aiError: '',
    aiSuggestions: [
        'Danh mục sản phẩm trên web là gì?',
        'Sofa phòng khách dưới 15 triệu',
        'Giường gỗ cho phòng ngủ nhỏ',
        'Bàn làm việc gỗ sồi',
    ],
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
            await this.activateTab(this.tab);
        } else {
            this.stopPolling();
        }
    },

    async activateTab(tab) {
        this.tab = tab;
        if (tab === 'ai') {
            this.stopPolling();
            await this.loadAiHistory();
            this.scrollToBottom(false);
            return;
        }
        this.unread = 0;
        await this.bootstrap();
        this.startPolling();
    },

    async loadAiHistory() {
        if (this.aiLoaded) return;
        this.aiLoaded = true;
        try {
            const res = await fetch('/api/ai-chat', { headers: this.headers() });
            if (!res.ok) return;
            const data = await res.json();
            if (data.success && Array.isArray(data.messages)) {
                this.aiMessages = data.messages;
            }
        } catch (e) { /* ignore */ }
    },

    async sendAi(preset = null) {
        const text = (preset ?? this.aiDraft).trim();
        if (!text || this.aiSending) return;
        this.aiSending = true;
        this.aiError = '';
        const pending = { id: `local-${Date.now()}`, role: 'user', content: text, created_at: '', products: [] };
        this.aiMessages.push(pending);
        this.aiDraft = '';
        this.scrollToBottom();
        try {
            const res = await fetch('/api/ai-chat', {
                method: 'POST',
                headers: { ...this.headers(), 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text }),
            });
            const data = await res.json().catch(() => ({}));
            if (res.status === 419) {
                this.aiError = 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.';
            } else if (res.status === 429) {
                this.aiError = 'Bạn hỏi hơi nhanh, vui lòng đợi một chút rồi thử lại nhé.';
            } else if (!res.ok || !data.success) {
                this.aiError = data.message || 'Trợ lý AI đang bận. Vui lòng thử lại.';
            } else {
                this.aiMessages.push(data.message);
            }
            if (this.aiError) {
                this.aiMessages = this.aiMessages.filter((m) => m.id !== pending.id);
                this.aiDraft = text;
            }
        } catch (e) {
            this.aiMessages = this.aiMessages.filter((m) => m.id !== pending.id);
            this.aiDraft = text;
            this.aiError = 'Không kết nối được trợ lý AI. Vui lòng thử lại.';
        } finally {
            this.aiSending = false;
            this.scrollToBottom();
        }
    },

    async resetAi() {
        this.aiMessages = [];
        this.aiError = '';
        try {
            await fetch('/api/ai-chat/reset', { method: 'POST', headers: this.headers() });
        } catch (e) { /* ignore */ }
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
            const el = this.tab === 'ai' ? this.$refs.aiList : this.$refs.list;
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
            badge.textContent = String(count);
            badge.classList.remove('hidden');
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

    document.addEventListener('click', async (event) => {
        const button = event.target.closest('.compare-toggle');
        if (!button) return;

        event.preventDefault();
        const url = button.dataset.compareUrl;
        if (!url || button.dataset.busy === '1') return;

        button.dataset.busy = '1';
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

            if (data.limited) {
                window.Toast?.error(data.message || 'Chỉ so sánh được tối đa 3 sản phẩm.');
                return;
            }

            if (!response.ok || !data.success) {
                window.Toast?.error(data.message || 'Không cập nhật được bảng so sánh.');
                return;
            }

            if (document.querySelector('[data-compare-page]')) {
                window.location.reload();
                return;
            }

            const productId = String(data.product_id);
            const inCompare = !!data.in_compare;
            document.querySelectorAll(`.compare-toggle[data-product-id="${productId}"]`).forEach((el) => {
                el.classList.toggle('is-active', inCompare);
                el.setAttribute('aria-pressed', inCompare ? 'true' : 'false');
                const label = el.querySelector('.compare-btn-text');
                if (label) {
                    const detail = el.classList.contains('compare-detail-btn');
                    label.textContent = inCompare
                        ? (detail ? 'Đang trong bảng so sánh' : 'Đang so sánh')
                        : (detail ? 'Thêm vào so sánh' : 'So sánh');
                }
                if (el.classList.contains('compare-button') || el.classList.contains('compare-detail-btn')) {
                    const name = data.item?.name || '';
                    el.setAttribute('aria-label', inCompare ? `Bỏ ${name} khỏi so sánh` : `Thêm ${name} vào so sánh`);
                    if (el.classList.contains('compare-button')) {
                        el.title = inCompare ? 'Bỏ khỏi so sánh' : 'Thêm vào so sánh';
                    }
                }
            });

            updateCompareBar(data);
            window.Toast?.success(data.message);
        } catch (e) {
            window.Toast?.error('Không cập nhật được bảng so sánh.');
        } finally {
            button.dataset.busy = '0';
        }
    });

    function updateCompareBar(data) {
        const count = Number(data.count || 0);
        document.querySelectorAll('.compare-badge-count').forEach((badge) => {
            badge.textContent = String(count);
            badge.classList.toggle('hidden', count === 0);
        });

        const bar = document.getElementById('compare-bar');
        const list = document.getElementById('compare-bar-items');
        const countEl = document.getElementById('compare-bar-count');
        const go = document.getElementById('compare-bar-go');
        if (countEl) countEl.textContent = String(count);
        if (bar) bar.toggleAttribute('hidden', count === 0);
        if (go) {
            go.classList.toggle('pointer-events-none', count < 2);
            go.classList.toggle('opacity-40', count < 2);
            if (count < 2) go.setAttribute('aria-disabled', 'true');
            else go.removeAttribute('aria-disabled');
        }
        if (!list || !data.item) return;

        const productId = String(data.product_id);
        const existing = list.querySelector(`.compare-bar-item[data-product-id="${productId}"]`);
        if (!data.in_compare) {
            existing?.remove();
            return;
        }
        if (existing) return;

        const item = document.createElement('div');
        item.className = 'compare-bar-item relative flex w-40 shrink-0 items-center gap-2 rounded-xl border border-ui-border bg-page p-1.5 pr-6';
        item.dataset.productId = productId;

        const img = document.createElement('img');
        img.src = data.item.image || '';
        img.alt = '';
        img.className = 'size-12 shrink-0 rounded-lg object-cover';

        const name = document.createElement('p');
        name.className = 'truncate text-[11px] font-semibold leading-snug text-heading';
        name.textContent = data.item.name || '';

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'compare-toggle absolute right-1 top-1 grid size-5 place-items-center rounded-full bg-heading text-[11px] font-bold leading-none text-page';
        remove.dataset.compareUrl = data.item.toggle_url || '';
        remove.dataset.productId = productId;
        remove.setAttribute('aria-label', `Bỏ ${data.item.name || ''} khỏi so sánh`);
        remove.textContent = '×';

        item.append(img, name, remove);
        list.appendChild(item);
    }

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

    document.querySelectorAll('.password-toggle').forEach((button) => {
        button.addEventListener('click', () => {
            const field = button.closest('.password-field');
            const input = field?.querySelector('input');
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
            button.setAttribute('aria-label', show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
            button.querySelector('.password-toggle-show')?.classList.toggle('hidden', show);
            button.querySelector('.password-toggle-hide')?.classList.toggle('hidden', !show);
        });
    });

    document.querySelectorAll('[data-submit-once]').forEach((button) => {
        button.closest('form')?.addEventListener('submit', () => {
            if (button.disabled) return;
            button.disabled = true;
            button.textContent = button.dataset.submitLabel || 'Đang xử lý...';
        });
    });
});
