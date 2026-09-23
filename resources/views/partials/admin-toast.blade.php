{{-- Admin toast host — messages injected from session flash via layouts/admin.blade.php --}}
<div
    id="admin-toast-root"
    class="pointer-events-none fixed bottom-6 right-6 z-[120] flex w-[min(360px,calc(100vw-2rem))] flex-col gap-2"
    aria-live="polite"
    aria-atomic="true"
></div>

<script>
    (function () {
        const root = () => document.getElementById('admin-toast-root');
        const timers = new WeakMap();

        function dismiss(el) {
            if (!el) return;
            el.classList.add('opacity-0', 'translate-x-4');
            window.setTimeout(() => el.remove(), 220);
        }

        function show(type, message) {
            const host = root();
            if (!host || !message) return;

            const isSuccess = type === 'success';
            const el = document.createElement('div');
            el.className = [
                'pointer-events-auto flex items-start gap-3 rounded-2xl border px-4 py-3 shadow-xl backdrop-blur-md',
                'transition-all duration-200 translate-x-0 opacity-100',
                isSuccess
                    ? 'border-emerald-200 bg-emerald-50/95 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/95 dark:text-emerald-100'
                    : 'border-rose-200 bg-rose-50/95 text-rose-900 dark:border-rose-800 dark:bg-rose-950/95 dark:text-rose-100',
            ].join(' ');

            const icon = isSuccess
                ? '<span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-emerald-500/20 text-emerald-700 dark:text-emerald-300"><svg viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 13 4 4L19 7"/></svg></span>'
                : '<span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-rose-500/20 text-rose-700 dark:text-rose-300"><svg viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 18 18 6M6 6l12 12"/></svg></span>';

            el.innerHTML = icon
                + '<div class="min-w-0 flex-1 text-xs font-semibold leading-relaxed break-words"></div>'
                + '<button type="button" class="shrink-0 rounded-lg p-1 opacity-60 hover:opacity-100 transition" aria-label="Đóng thông báo">'
                + '<svg viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 18 18 6M6 6l12 12"/></svg></button>';

            el.querySelector('div.min-w-0').textContent = message;
            el.querySelector('button').addEventListener('click', () => dismiss(el));

            host.appendChild(el);

            const timer = window.setTimeout(() => dismiss(el), isSuccess ? 3200 : 5000);
            timers.set(el, timer);
        }

        window.AdminToast = {
            success: (msg) => show('success', msg),
            error: (msg) => show('error', msg),
            show,
        };
    })();
</script>
