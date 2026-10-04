<div
    id="order-status-watch"
    hidden
    data-order-status="{{ $order->order_status }}"
    data-payment-status="{{ $order->payment_status }}"
    data-ghn-status="{{ $order->ghn_status }}"
></div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const node = document.getElementById('order-status-watch');
        if (!node) return;

        const current = [node.dataset.orderStatus, node.dataset.paymentStatus, node.dataset.ghnStatus].join('|');

        window.setInterval(async () => {
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('status', '1');
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    cache: 'no-store',
                });
                if (!response.ok) return;
                const data = await response.json();
                const next = [data.order_status, data.payment_status, data.ghn_status].join('|');
                if (next !== current) {
                    window.location.reload();
                }
            } catch (e) {}
        }, 8000);
    });
</script>
