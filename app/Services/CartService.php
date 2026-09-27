<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    protected string $sessionKey = 'furniture_cart';
    protected string $buyNowSessionKey = 'buy_now_cart';

    public function isBuyNowMode(): bool
    {
        return (bool) Session::get('is_buy_now', false);
    }

    public function setBuyNowMode(bool $active): void
    {
        Session::put('is_buy_now', $active);
        if (! $active) {
            Session::forget($this->buyNowSessionKey);
        }
    }

    public function setBuyNowItem(Product $product, int $quantity, ProductVariant $variant): void
    {
        $price = (float) $variant->price;
        $id = $this->lineKey($product->id, $variant->id);

        $item = [
            'id' => $product->id,
            'key' => $id,
            'variant_id' => $variant->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $variant->sku ?: $product->sku,
            'price' => $price,
            'original_price' => $price,
            'image' => $product->primary_image_url,
            'material' => $variant->material ?: $product->material,
            'size' => $variant->size,
            'color_name' => $variant->color,
            'variant_label' => trim(($variant->size ? $variant->size . ' · ' : '') . ($variant->color ?? '')),
            'quantity' => $quantity,
            'subtotal' => $price * $quantity,
        ];

        Session::put($this->buyNowSessionKey, [$id => $item]);
        Session::put('is_buy_now', true);
        Session::forget(['shipping_fee', 'shipping_destination']);
    }

    public function clearBuyNow(): void
    {
        Session::forget([$this->buyNowSessionKey, 'is_buy_now', 'shipping_fee', 'shipping_destination']);
    }

    public function getRegularCart(): array
    {
        return Session::get($this->sessionKey, []);
    }

    public function getCart(): array
    {
        if ($this->isBuyNowMode()) {
            return Session::get($this->buyNowSessionKey, []);
        }

        return Session::get($this->sessionKey, []);
    }

    /**
     * Cart lines hydrated for Mộc An cart view.
     */
    public function getItems(): Collection
    {
        return collect($this->getCart())->map(function (array $line) {
            $variant = ProductVariant::with('product.category', 'product.primaryImage')->find($line['variant_id'] ?? 0);
            $product = $variant?->product;
            $price = (float) ($line['price'] ?? $variant?->price ?? 0);
            $qty = (int) ($line['quantity'] ?? 1);

            return (object) [
                'key' => $line['key'] ?? null,
                'product' => $product,
                'variant' => $variant,
                'quantity' => $qty,
                'unit_price' => $price,
                'line_total' => $price * $qty,
            ];
        })->filter(fn ($item) => $item->product && $item->variant)->values();
    }

    public function getSubtotal(): float
    {
        return (float) $this->getItems()->sum('line_total');
    }

    public function lineKey(int $productId, ?int $variantId = null): string
    {
        return $variantId ? "{$productId}-{$variantId}" : (string) $productId;
    }

    public function add(Product $product, int $quantity = 1, ?ProductVariant $variant = null): array
    {
        if (! $variant) {
            $variant = $product->variants()->where('stock', '>', 0)->first();
        }

        if (! $variant) {
            throw new \InvalidArgumentException('Sản phẩm chưa có biến thể khả dụng.');
        }

        if (! $product->is_active) {
            throw new \InvalidArgumentException('Sản phẩm hiện không khả dụng.');
        }

        $cart = $this->getCart();
        $id = $this->lineKey($product->id, $variant->id);
        $currentQty = isset($cart[$id]) ? $cart[$id]['quantity'] : 0;
        $newQty = $currentQty + $quantity;

        if ($newQty > $variant->stock) {
            throw new \InvalidArgumentException("Số lượng yêu cầu ({$newQty}) vượt quá số lượng còn lại trong kho ({$variant->stock}).");
        }

        $price = (float) $variant->price;

        $cart[$id] = [
            'id' => $product->id,
            'key' => $id,
            'variant_id' => $variant->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $variant->sku ?: $product->sku,
            'price' => $price,
            'original_price' => $price,
            'image' => $product->primary_image_url,
            'material' => $variant->material ?: $product->material,
            'size' => $variant->size,
            'color_name' => $variant->color,
            'variant_label' => trim(($variant->size ? $variant->size . ' · ' : '') . ($variant->color ?? '')),
            'quantity' => $newQty,
            'subtotal' => $price * $newQty,
        ];

        Session::put($this->sessionKey, $cart);

        return $cart;
    }

    public function update(string $cartKey, int $quantity): array
    {
        $cart = $this->getCart();
        $cartKey = $this->resolveKey($cart, $cartKey);

        if (! isset($cart[$cartKey])) {
            throw new \InvalidArgumentException('Sản phẩm không có trong giỏ hàng.');
        }

        if ($quantity <= 0) {
            return $this->remove($cartKey);
        }

        $variant = ProductVariant::find($cart[$cartKey]['variant_id'] ?? 0);
        if (! $variant) {
            throw new \InvalidArgumentException('Biến thể không còn khả dụng.');
        }

        if ($quantity > $variant->stock) {
            throw new \InvalidArgumentException("Số lượng yêu cầu ({$quantity}) vượt quá tồn kho ({$variant->stock}).");
        }

        $cart[$cartKey]['quantity'] = $quantity;
        $cart[$cartKey]['subtotal'] = $cart[$cartKey]['price'] * $quantity;

        Session::put($this->sessionKey, $cart);

        return $cart;
    }

    public function remove(string $cartKey): array
    {
        $cart = $this->getCart();
        $cartKey = $this->resolveKey($cart, $cartKey);

        unset($cart[$cartKey]);
        Session::put($this->sessionKey, $cart);

        return $cart;
    }

    protected string $couponSessionKey = 'furniture_coupon';

    public const AVAILABLE_COUPONS = [
        'MOCAN10' => [
            'code' => 'MOCAN10',
            'type' => 'percent',
            'value' => 10,
            'max_discount' => 500000,
            'min_order' => 0,
            'description' => 'Giảm 10% (tối đa 500.000₫)',
        ],
        'MOCAN20' => [
            'code' => 'MOCAN20',
            'type' => 'percent',
            'value' => 20,
            'max_discount' => 1000000,
            'min_order' => 3000000,
            'description' => 'Giảm 20% (tối đa 1.000.000₫ cho đơn từ 3tr)',
        ],
        'FREESHIP' => [
            'code' => 'FREESHIP',
            'type' => 'shipping',
            'value' => 50000,
            'max_discount' => 50000,
            'min_order' => 0,
            'description' => 'Miễn phí vận chuyển (tối đa 50.000₫)',
        ],
        'VIP500' => [
            'code' => 'VIP500',
            'type' => 'fixed',
            'value' => 500000,
            'max_discount' => 500000,
            'min_order' => 5000000,
            'description' => 'Giảm trực tiếp 500.000₫ cho đơn từ 5tr',
        ],
        'CHAOBAN50' => [
            'code' => 'CHAOBAN50',
            'type' => 'fixed',
            'value' => 50000,
            'max_discount' => 50000,
            'min_order' => 500000,
            'description' => 'Giảm 50.000₫ cho đơn từ 500.000₫',
        ],
    ];

    public function getAvailableCoupons(): array
    {
        return self::AVAILABLE_COUPONS;
    }

    public function getCoupon(): ?array
    {
        return Session::get($this->couponSessionKey);
    }

    public function applyCoupon(string $code): array
    {
        $code = strtoupper(trim($code));
        if (! isset(self::AVAILABLE_COUPONS[$code])) {
            throw new \InvalidArgumentException('Mã giảm giá "'.$code.'" không tồn tại hoặc đã hết hạn.');
        }

        $coupon = self::AVAILABLE_COUPONS[$code];
        $subtotal = $this->getSubtotal();

        if ($subtotal < $coupon['min_order']) {
            $formattedMin = number_format($coupon['min_order'], 0, ',', '.');
            throw new \InvalidArgumentException("Mã {$code} chỉ áp dụng cho đơn hàng từ {$formattedMin}₫. Tạm tính hiện tại: ".number_format($subtotal, 0, ',', '.')."₫.");
        }

        Session::put($this->couponSessionKey, $coupon);

        return $coupon;
    }

    public function removeCoupon(): void
    {
        Session::forget($this->couponSessionKey);
    }

    public function getDiscountAmount(): float
    {
        $coupon = $this->getCoupon();
        if (! $coupon) {
            return 0.0;
        }

        $subtotal = $this->getSubtotal();
        if ($subtotal < ($coupon['min_order'] ?? 0)) {
            return 0.0;
        }

        $discount = 0.0;
        if ($coupon['type'] === 'percent') {
            $discount = $subtotal * ((float) $coupon['value'] / 100);
            if (! empty($coupon['max_discount']) && $discount > (float) $coupon['max_discount']) {
                $discount = (float) $coupon['max_discount'];
            }
        } elseif ($coupon['type'] === 'fixed') {
            $discount = min($subtotal, (float) $coupon['value']);
        } elseif ($coupon['type'] === 'shipping') {
            $shippingFee = $this->getShippingFee();
            $discount = min($shippingFee, (float) ($coupon['max_discount'] ?? 50000.0));
        }

        return (float) $discount;
    }

    public function clear(): void
    {
        Session::forget($this->sessionKey);
        Session::forget($this->couponSessionKey);
    }

    public function hasCalculatedShipping(): bool
    {
        return Session::has('shipping_fee');
    }

    public function getShippingFee(): float
    {
        if (Session::has('shipping_fee')) {
            return (float) Session::get('shipping_fee');
        }

        return 0.0;
    }

    public function getTotal(): float
    {
        $subtotal = $this->getSubtotal();
        if ($subtotal === 0.0) {
            return 0.0;
        }

        $shipping = $this->hasCalculatedShipping() ? $this->getShippingFee() : 0.0;
        $total = $subtotal + $shipping - $this->getDiscountAmount();
        return max(0.0, (float) $total);
    }

    public function count(): int
    {
        return (int) $this->getItems()->sum('quantity');
    }

    protected function resolveKey(array $cart, string $cartKey): string
    {
        if (isset($cart[$cartKey])) {
            return $cartKey;
        }

        foreach ($cart as $key => $line) {
            if ((string) ($line['variant_id'] ?? '') === (string) $cartKey
                || (string) ($line['id'] ?? '') === (string) $cartKey) {
                return (string) $key;
            }
        }

        return $cartKey;
    }
}
