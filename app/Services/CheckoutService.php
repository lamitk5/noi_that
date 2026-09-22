<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        protected CartService $cartService,
        protected \App\Services\Shipping\ShippingManager $shippingManager
    ) {}

    /**
     * Perform atomic transactional checkout and order creation.
     *
     * @throws ValidationException
     */
    public function checkout(User $user, array $data): Order
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            throw ValidationException::withMessages([
                'cart' => 'Giỏ hàng của bạn đang trống.',
            ]);
        }

        $order = DB::transaction(function () use ($user, $data, $cart) {
            $variantIds = array_keys($cart);
            sort($variantIds);

            // Lock variants in stable sorted order to prevent deadlocks
            $variants = ProductVariant::query()
                ->with('product')
                ->whereIn('id', $variantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            $itemsData = [];

            foreach ($cart as $variantId => $quantity) {
                $variant = $variants->get($variantId);
                $qty = (int) $quantity;

                if (! $variant || ! $variant->product || ! $variant->product->is_active) {
                    throw ValidationException::withMessages([
                        'cart' => 'Một số sản phẩm không còn tồn tại hoặc đã ngừng kinh doanh. Vui lòng kiểm tra lại giỏ hàng.',
                    ]);
                }

                if ($variant->stock < $qty) {
                    throw ValidationException::withMessages([
                        'stock' => "Sản phẩm \"{$variant->product->name}\" không đủ số lượng trong kho (còn {$variant->stock} sản phẩm).",
                    ]);
                }

                // Authoritative server-side price
                $unitPrice = (float) ($variant->price ?? $variant->product->base_price);
                $subtotal += $unitPrice * $qty;

                // Snapshot variant attributes
                $variantAttrs = array_filter([$variant->color, $variant->size, $variant->material]);
                $variantInfo = ! empty($variantAttrs) ? implode(' / ', $variantAttrs) : 'Tiêu chuẩn';

                $itemsData[] = [
                    'variant' => $variant,
                    'product_name' => $variant->product->name,
                    'variant_info' => $variantInfo,
                    'quantity' => $qty,
                    'price' => $unitPrice,
                ];
            }

            $voucherId = null;
            $discountAmount = 0;
            if ($voucherCode = session()->get('applied_voucher_code')) {
                $voucher = \App\Models\Voucher::where('code', $voucherCode)->first();
                if ($voucher && $voucher->isValidFor($user, $subtotal)) {
                    $voucherId = $voucher->id;
                    $discountAmount = $voucher->calculateDiscount($subtotal);
                    $voucher->increment('used_count');
                    \App\Models\VoucherUsage::create([
                        'voucher_id' => $voucher->id,
                        'user_id' => $user->id,
                        'discount_amount' => $discountAmount,
                    ]);
                }
            }

            $pointsUsed = 0;
            $pointsDiscount = 0;
            if ($points = session()->get('applied_loyalty_points')) {
                $availablePoints = $user->loyalty_points;
                $pointsUsed = min((int) $points, $availablePoints);
                if ($pointsUsed > 0) {
                    $maxDiscount = max(0, $subtotal - $discountAmount);
                    $pointsDiscount = min($pointsUsed * 1000, $maxDiscount);
                    $user->decrement('loyalty_points', $pointsUsed);
                } else {
                    $pointsUsed = 0;
                }
            }

            $toDistrictId = ! empty($data['to_district_id']) ? (int) $data['to_district_id'] : null;
            $toWardCode = ! empty($data['to_ward_code']) ? (string) $data['to_ward_code'] : null;

            $itemsForWeight = collect($itemsData)->map(fn ($i) => (object) [
                'variant' => $i['variant'],
                'quantity' => $i['quantity'],
            ]);
            $orderWeight = $this->shippingManager->getGhnOrderService()->weightForItems($itemsForWeight);

            $shippingFee = 0.0;
            if ($toDistrictId && $toWardCode && $this->shippingManager->getGhnService()->isConfigured()) {
                try {
                    $feeData = $this->shippingManager->getGhnService()->calculateFee($toDistrictId, $toWardCode, $orderWeight);
                    $shippingFee = (float) ($feeData['total'] ?? $feeData['service_fee'] ?? $feeData['fee'] ?? 0);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('GHN calculateFee failed during checkout: ' . $e->getMessage());
                    $shippingFee = (float) config('shop.shipping_fee', 80000);
                }
            } else {
                $shippingFee = (float) config('shop.shipping_fee', 0);
            }

            $totalPrice = max(0, $subtotal + $shippingFee - $discountAmount - $pointsDiscount);

            $orderCode = $this->generateOrderCode();

            $order = Order::create([
                'user_id' => $user->id,
                'order_code' => $orderCode,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? $user->email,
                'shipping_address' => $data['shipping_address'],
                'to_district_id' => $toDistrictId,
                'to_ward_code' => $toWardCode,
                'note' => $data['note'] ?? null,
                'total_price' => $totalPrice,
                'shipping_fee' => $shippingFee,
                'voucher_id' => $voucherId,
                'discount_amount' => $discountAmount,
                'points_used' => $pointsUsed,
                'points_discount' => $pointsDiscount,
                'payment_method' => $data['payment_method'],
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'shipping_status' => 'pending',
            ]);

            if ($voucherId) {
                \App\Models\VoucherUsage::where('voucher_id', $voucherId)
                    ->where('user_id', $user->id)
                    ->whereNull('order_id')
                    ->latest('id')
                    ->first()
                    ?->update(['order_id' => $order->id]);
            }

            if ($pointsUsed > 0) {
                \App\Models\LoyaltyTransaction::create([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'points' => -$pointsUsed,
                    'type' => 'redeem',
                    'description' => "Đổi {$pointsUsed} điểm giảm giá cho đơn hàng #{$order->order_code}",
                ]);
            }

            foreach ($itemsData as $item) {
                $order->items()->create([
                    'product_variant_id' => $item['variant']->id,
                    'product_name' => $item['product_name'],
                    'variant_info' => $item['variant_info'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);

                $item['variant']->decrement('stock', $item['quantity']);
            }

            return $order;
        });

        // COD order: create GHN DEV waybill once immediately after local transaction commits
        if ($order->payment_method === 'cod') {
            try {
                $this->shippingManager->ensureShipmentCreated($order);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("GHN shipment creation failed for COD order #{$order->order_code}: " . $e->getMessage());
            }
        }

        // Clear cart and checkout session state
        $this->cartService->clear();
        session()->forget(['applied_voucher_code', 'applied_loyalty_points']);

        // Send notifications & emails
        try {
            $user->notify(new \App\Notifications\OrderPlacedNotification($order));
            if ($order->customer_email) {
                \Illuminate\Support\Facades\Mail::to($order->customer_email)
                    ->send(new \App\Mail\OrderPlacedMail($order));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to dispatch order notification/email: ' . $e->getMessage());
        }

        return $order;
    }

    /**
     * Generate a unique, human-readable order code.
     */
    protected function generateOrderCode(): string
    {
        do {
            $code = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Order::where('order_code', $code)->exists());

        return $code;
    }
}
