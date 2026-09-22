<?php

namespace App\Services;

use App\Mail\OrderStatusChangedMail;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Notifications\OrderStatusUpdatedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class OrderWorkflowService
{
    /**
     * Define valid state transition graph.
     */
    protected array $forwardGraph = [
        Order::STATUS_PENDING => [Order::STATUS_CONFIRMED],
        Order::STATUS_CONFIRMED => [Order::STATUS_PACKED],
        Order::STATUS_PACKED => [Order::STATUS_SHIPPING],
        Order::STATUS_SHIPPING => [Order::STATUS_COMPLETED],
        Order::STATUS_COMPLETED => [],
        Order::STATUS_CANCELED => [],
    ];

    /**
     * Get valid next statuses for an order.
     */
    public function getAllowedNextStatuses(Order $order): array
    {
        $current = $order->order_status;
        $allowed = $this->forwardGraph[$current] ?? [];

        // Add cancellation if current state allows it
        if (in_array($current, [Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_PACKED], true)) {
            // Cancellation is restricted if already paid online/bank transfer
            $isPaidOnlineOrBank = $order->payment_status === 'paid' && in_array($order->payment_method, ['bank_transfer', 'vnpay', 'momo'], true);
            $hasPendingOnlineAttempt = in_array($order->payment_method, ['vnpay', 'momo'], true)
                && $order->paymentTransactions()->where('status', 'pending')->exists();

            if (! $isPaidOnlineOrBank && ! $hasPendingOnlineAttempt) {
                $allowed[] = Order::STATUS_CANCELED;
            }
        }

        return $allowed;
    }

    /**
     * Check if a transition is valid.
     */
    public function canTransition(Order $order, string $newStatus): bool
    {
        return in_array($newStatus, $this->getAllowedNextStatuses($order), true);
    }

    /**
     * Execute a status transition inside a transaction.
     *
     * @throws ValidationException
     */
    public function transition(Order $order, string $newStatus): Order
    {
        return DB::transaction(function () use ($order, $newStatus) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($newStatus === Order::STATUS_CANCELED) {
                return $this->cancel($lockedOrder);
            }

            // Validate forward transition
            $allowedForward = $this->forwardGraph[$lockedOrder->order_status] ?? [];
            if (! in_array($newStatus, $allowedForward, true)) {
                throw ValidationException::withMessages([
                    'status' => "Không thể chuyển trạng thái từ \"{$lockedOrder->statusLabel()}\" sang \"{$newStatus}\".",
                ]);
            }

            // Guard: Non-COD orders must be paid before being confirmed
            if ($lockedOrder->order_status === Order::STATUS_PENDING && $newStatus === Order::STATUS_CONFIRMED) {
                if (in_array($lockedOrder->payment_method, ['vnpay', 'momo', 'bank_transfer'], true) && $lockedOrder->payment_status !== 'paid') {
                    throw ValidationException::withMessages([
                        'status' => 'Đơn hàng trực tuyến/chuyển khoản chưa được thanh toán, không thể xác nhận đơn.',
                    ]);
                }
            }

            // Guard & Side-Effect: COD completes -> marks payment_status = paid
            if ($lockedOrder->order_status === Order::STATUS_SHIPPING && $newStatus === Order::STATUS_COMPLETED) {
                if ($lockedOrder->payment_method === 'cod') {
                    $lockedOrder->payment_status = 'paid';
                }
            }

            $lockedOrder->order_status = $newStatus;
            $lockedOrder->save();

            if ($lockedOrder->order_status === Order::STATUS_COMPLETED && $lockedOrder->payment_status === 'paid') {
                $this->awardLoyaltyPoints($lockedOrder);
            }

            $this->notifyCustomer($lockedOrder, $newStatus);

            return $lockedOrder;
        });
    }

    /**
     * Cancel an order and restore stock atomically (exactly once).
     *
     * @throws ValidationException
     */
    public function cancel(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->order_status === Order::STATUS_CANCELED) {
                throw ValidationException::withMessages([
                    'status' => 'Đơn hàng này đã được hủy trước đó.',
                ]);
            }

            if (! in_array($lockedOrder->order_status, [Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_PACKED], true)) {
                throw ValidationException::withMessages([
                    'status' => "Đơn hàng đang ở trạng thái \"{$lockedOrder->statusLabel()}\", không thể hủy.",
                ]);
            }

            if ($lockedOrder->payment_status === 'paid' && in_array($lockedOrder->payment_method, ['bank_transfer', 'vnpay', 'momo'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Đơn hàng đã thanh toán cần xử lý hoàn tiền trước khi hủy.',
                ]);
            }

            if (in_array($lockedOrder->payment_method, ['vnpay', 'momo'], true)) {
                if ($lockedOrder->paymentTransactions()->where('status', 'pending')->exists()) {
                    throw ValidationException::withMessages([
                        'status' => 'Đơn hàng đang chờ xác nhận thanh toán.',
                    ]);
                }
            }

            // If GHN shipment exists, confirm remote state and cancel on GHN DEV first
            if ($lockedOrder->ghn_order_code) {
                try {
                    app(\App\Services\Shipping\ShippingManager::class)->cancelShipment($lockedOrder);
                } catch (\App\Exceptions\GHNException $e) {
                    throw ValidationException::withMessages([
                        'order' => 'Không thể hủy đơn hàng vì vận đơn GHN không cho phép hủy hoặc đang giao.',
                    ]);
                }
            }

            // Restore variant stocks atomically with lockForUpdate in sorted order
            $items = $lockedOrder->items;
            $variantIds = $items->pluck('product_variant_id')->filter()->unique()->values()->all();
            sort($variantIds);

            $variants = ProductVariant::query()
                ->whereIn('id', $variantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                if ($item->product_variant_id && $variant = $variants->get($item->product_variant_id)) {
                    $variant->increment('stock', $item->quantity);
                }
            }

            $lockedOrder->order_status = Order::STATUS_CANCELED;
            $lockedOrder->save();

            $this->refundLoyaltyPoints($lockedOrder);
            $this->notifyCustomer($lockedOrder, Order::STATUS_CANCELED);

            return $lockedOrder;
        });
    }

    /**
     * Mark a bank transfer order as paid by admin.
     *
     * @throws ValidationException
     */
    public function markBankTransferPaid(Order $order, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $note) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->payment_method !== 'bank_transfer') {
                throw ValidationException::withMessages([
                    'payment' => 'Chỉ có thể xác nhận thanh toán thủ công cho đơn hàng Chuyển khoản ngân hàng.',
                ]);
            }

            if ($lockedOrder->order_status === Order::STATUS_CANCELED) {
                throw ValidationException::withMessages([
                    'payment' => 'Đơn hàng đã bị hủy, không thể xác nhận thanh toán.',
                ]);
            }

            if ($lockedOrder->payment_status === 'paid') {
                throw ValidationException::withMessages([
                    'payment' => 'Đơn hàng này đã được xác nhận thanh toán.',
                ]);
            }

            $lockedOrder->payment_status = 'paid';
            if ($note) {
                $lockedOrder->note = trim(($lockedOrder->note ? $lockedOrder->note . ' | ' : '') . $note);
            }
            $lockedOrder->save();

            // Trigger GHN DEV shipment creation idempotently
            try {
                app(\App\Services\Shipping\ShippingManager::class)->ensureShipmentCreated($lockedOrder);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("GHN shipment creation failed for Bank Transfer order #{$lockedOrder->order_code}: " . $e->getMessage());
            }

            if ($lockedOrder->order_status === Order::STATUS_COMPLETED) {
                $this->awardLoyaltyPoints($lockedOrder);
            }

            return $lockedOrder;
        });
    }

    /**
     * Award loyalty points when an order is completed & paid.
     */
    protected function awardLoyaltyPoints(Order $order): void
    {
        if (! $order->user_id) {
            return;
        }

        $alreadyAwarded = LoyaltyTransaction::where('order_id', $order->id)
            ->where('type', 'earn')
            ->exists();

        if ($alreadyAwarded) {
            return;
        }

        // 1 point per 10,000 VND
        $points = (int) floor($order->total_price / 10000);
        if ($points > 0) {
            $user = $order->user;
            if ($user) {
                $user->increment('loyalty_points', $points);
                $user->recalculateTier();

                LoyaltyTransaction::create([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'points' => $points,
                    'type' => 'earn',
                    'description' => "Tích điểm từ đơn hàng hoàn tất #{$order->order_code}",
                ]);
            }
        }
    }

    /**
     * Refund redeemed loyalty points when an order is canceled.
     */
    protected function refundLoyaltyPoints(Order $order): void
    {
        if (! $order->user_id || $order->points_used <= 0) {
            return;
        }

        $alreadyRefunded = LoyaltyTransaction::where('order_id', $order->id)
            ->where('type', 'refund')
            ->exists();

        if ($alreadyRefunded) {
            return;
        }

        $user = $order->user;
        if ($user) {
            $user->increment('loyalty_points', $order->points_used);
            $user->recalculateTier();

            LoyaltyTransaction::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'points' => $order->points_used,
                'type' => 'refund',
                'description' => "Hoàn lại {$order->points_used} điểm từ đơn hàng đã hủy #{$order->order_code}",
            ]);
        }
    }

    /**
     * Notify customer about status transition via notification and email.
     */
    protected function notifyCustomer(Order $order, string $newStatus): void
    {
        try {
            if ($order->user) {
                $order->user->notify(new OrderStatusUpdatedNotification($order, $newStatus));
            }

            if ($order->customer_email) {
                Mail::to($order->customer_email)->send(new OrderStatusChangedMail($order, $newStatus));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to dispatch order status notification/email: ' . $e->getMessage());
        }
    }
}
