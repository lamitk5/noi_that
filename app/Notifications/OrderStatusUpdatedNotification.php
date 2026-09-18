<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderStatusUpdatedNotification extends Notification
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
            'title' => "Đơn hàng #{$this->order->order_code} đã cập nhật",
            'message' => "Trạng thái đơn hàng của bạn hiện tại là: {$this->order->statusLabel()}.",
            'action_url' => route('orders.show', $this->order->order_code),
        ];
    }
}
