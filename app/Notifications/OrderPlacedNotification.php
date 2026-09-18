<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderPlacedNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_code' => $this->order->order_code,
            'title' => "Đặt hàng thành công #{$this->order->order_code}",
            'message' => 'Cảm ơn bạn đã đặt hàng tại Mộc An với tổng thanh toán: ' . number_format($this->order->total_price, 0, ',', '.') . 'đ.',
            'action_url' => route('orders.show', $this->order->order_code),
        ];
    }
}
