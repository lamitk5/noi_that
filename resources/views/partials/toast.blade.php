{{-- Global Toast Notification Component --}}
<div
    id="toast-root"
    class="pointer-events-none fixed top-5 right-5 sm:top-6 sm:right-6 z-[9999] flex w-[min(400px,calc(100vw-2rem))] flex-col gap-2.5"
    aria-live="polite"
    aria-atomic="true"
></div>

<script>
    (function () {
        const root = () => document.getElementById('toast-root');
        const activeTimers = new WeakMap();

        function dismiss(el) {
            if (!el || el.dataset.dismissing === 'true') return;
            el.dataset.dismissing = 'true';
            el.classList.add('opacity-0', 'translate-x-8', 'scale-95');
            window.setTimeout(() => el.remove(), 250);
        }

        function show(type, message) {
            const host = root();
            if (!host || !message) return;

            const normalizedType = ['success', 'error', 'info', 'warning'].includes(type) ? type : 'info';
            const isSuccess = normalizedType === 'success';
            const isError = normalizedType === 'error';
            const isWarning = normalizedType === 'warning';

            const el = document.createElement('div');
            el.className = [
                'pointer-events-auto flex items-start gap-3 rounded-2xl border p-4 shadow-xl backdrop-blur-md',
                'transition-all duration-300 ease-out translate-x-0 opacity-100 scale-100',
                isSuccess ? 'border-emerald-200/90 bg-emerald-50/95 text-emerald-950 dark:border-emerald-800 dark:bg-emerald-950/95 dark:text-emerald-100 shadow-emerald-500/10' : '',
                isError ? 'border-rose-200/90 bg-rose-50/95 text-rose-950 dark:border-rose-800 dark:bg-rose-950/95 dark:text-rose-100 shadow-rose-500/10' : '',
                isWarning ? 'border-amber-200/90 bg-amber-50/95 text-amber-950 dark:border-amber-800 dark:bg-amber-950/95 dark:text-amber-100 shadow-amber-500/10' : '',
                normalizedType === 'info' ? 'border-sky-200/90 bg-sky-50/95 text-sky-950 dark:border-sky-800 dark:bg-sky-950/95 dark:text-sky-100 shadow-sky-500/10' : '',
            ].filter(Boolean).join(' ');

            let iconHtml = '';
            if (isSuccess) {
                iconHtml = '<span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-emerald-500/20 text-emerald-700 dark:text-emerald-300"><svg viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg></span>';
            } else if (isError) {
                iconHtml = '<span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-rose-500/20 text-rose-700 dark:text-rose-300"><svg viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></span>';
            } else if (isWarning) {
                iconHtml = '<span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-amber-500/20 text-amber-700 dark:text-amber-300"><svg viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg></span>';
            } else {
                iconHtml = '<span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-sky-500/20 text-sky-700 dark:text-sky-300"><svg viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>';
            }

            el.innerHTML = iconHtml
                + '<div class="min-w-0 flex-1 text-xs font-semibold leading-relaxed break-words"></div>'
                + '<button type="button" class="shrink-0 rounded-lg p-1 opacity-60 hover:opacity-100 transition" aria-label="Đóng">'
                + '<svg viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>';

            el.querySelector('div.min-w-0').textContent = message;
            el.querySelector('button').addEventListener('click', () => dismiss(el));

            // Initial animation state
            el.classList.add('opacity-0', 'translate-x-8', 'scale-95');
            host.appendChild(el);

            // Animate in
            requestAnimationFrame(() => {
                el.classList.remove('opacity-0', 'translate-x-8', 'scale-95');
            });

            // Auto dismiss timer
            const duration = isError ? 5000 : 3500;
            let timer = window.setTimeout(() => dismiss(el), duration);
            activeTimers.set(el, timer);

            // Pause on hover
            el.addEventListener('mouseenter', () => {
                if (activeTimers.has(el)) {
                    window.clearTimeout(activeTimers.get(el));
                }
            });
            el.addEventListener('mouseleave', () => {
                const newTimer = window.setTimeout(() => dismiss(el), 2000);
                activeTimers.set(el, newTimer);
            });
        }

        window.Toast = {
            success: (msg) => show('success', msg),
            error: (msg) => show('error', msg),
            info: (msg) => show('info', msg),
            warning: (msg) => show('warning', msg),
            show,
        };
        window.AdminToast = window.Toast;

        window.addEventListener('toast', (e) => {
            if (e.detail && e.detail.message) {
                show(e.detail.type || 'success', e.detail.message);
            }
        });
    })();
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        @if (session('success'))
            window.Toast?.success(@js(session('success')));
        @endif
        @if (session('status'))
            window.Toast?.success(@js(session('status')));
        @endif
        @if (session('info'))
            window.Toast?.info(@js(session('info')));
        @endif
        @if (session('warning'))
            window.Toast?.warning(@js(session('warning')));
        @endif
        @if (session('error'))
            window.Toast?.error(@js(session('error')));
        @endif
        @if (isset($errors) && $errors->any())
            window.Toast?.error(@js($errors->first()));
        @endif
    });
</script>
