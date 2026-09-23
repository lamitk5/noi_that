<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Shipping\GhnService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        protected CartService $cartService,
        protected GhnService $ghnService
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

            // Calculate shipping fee from GHN or session
            $shippingFee = (float) session()->get('shipping_fee', config('shop.shipping_fee', 0));
            if (! empty($data['district_id']) && ! empty($data['ward_code'])) {
                $totalWeight = max(500, count($itemsData) * 500);
                $shippingFee = $this->ghnService->calculateFee((int) $data['district_id'], (string) $data['ward_code'], $totalWeight, $subtotal);
            }

            // Handle coupon discount if applied
            $appliedCouponData = session()->get('applied_coupon');
            $couponCode = null;
            $discountAmount = 0.0;

            if (! empty($appliedCouponData['code'])) {
                $coupon = \App\Models\Coupon::where('code', $appliedCouponData['code'])
                    ->lockForUpdate()
                    ->first();

                if ($coupon && $coupon->isValidFor($subtotal)) {
                    $discountAmount = $coupon->calculateDiscount($subtotal);
                    $couponCode = $coupon->code;
                    $coupon->increment('used_count');
                }
            }

            $totalPrice = max(0, ($subtotal - $discountAmount) + $shippingFee);

            $orderCode = $this->generateOrderCode();

            $order = Order::create([
                'user_id' => $user->id,
                'order_code' => $orderCode,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? $user->email,
                'shipping_address' => $data['shipping_address'],
                'province_id' => $data['province_id'] ?? null,
                'province_name' => $data['province_name'] ?? null,
                'district_id' => $data['district_id'] ?? null,
                'district_name' => $data['district_name'] ?? null,
                'ward_code' => $data['ward_code'] ?? null,
                'ward_name' => $data['ward_name'] ?? null,
                'note' => $data['note'] ?? null,
                'total_price' => $totalPrice,
                'shipping_fee' => $shippingFee,
                'coupon_code' => $couponCode,
                'discount_amount' => $discountAmount,
                'payment_method' => $data['payment_method'],
                'payment_status' => 'pending',
                'order_status' => 'pending',
            ]);

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

        // Automatically create GHN shipping order if enabled
        if (config('services.ghn.auto_create_order', true)) {
            try {
                $this->ghnService->createShippingOrder($order);
            } catch (\Throwable $ghnEx) {
                Log::warning('Automatic GHN shipping order creation failed: ' . $ghnEx->getMessage());
            }
        }

        // ONLY clear cart, coupon, and shipping session after transaction has successfully committed
        session()->forget(['applied_coupon', 'shipping_fee', 'shipping_destination']);
        $this->cartService->clear();

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
