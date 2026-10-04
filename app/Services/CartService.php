<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    protected string $sessionKey = 'furniture_cart';
    protected string $buyNowSessionKey = 'buy_now_cart';
    protected string $selectedSessionKey = 'furniture_cart_selected';

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
        $price = (float) $variant->final_price;
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
            $price = (float) ($variant?->final_price ?? $line['price'] ?? 0);
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

    public function getSelectedKeys(): array
    {
        if ($this->isBuyNowMode()) {
            return array_keys(Session::get($this->buyNowSessionKey, []));
        }

        $cart = Session::get($this->sessionKey, []);
        $cartKeys = array_map('strval', array_keys($cart));

        if (empty($cartKeys)) {
            return [];
        }

        $selected = Session::get($this->selectedSessionKey);

        if ($selected === null) {
            return $cartKeys;
        }

        $selected = array_map('strval', (array) $selected);

        return array_values(array_intersect($selected, $cartKeys));
    }

    public function setSelectedKeys(array $keys): void
    {
        $cart = Session::get($this->sessionKey, []);
        $cartKeys = array_map('strval', array_keys($cart));
        $keys = array_map('strval', $keys);

        $validKeys = array_values(array_intersect($keys, $cartKeys));
        Session::put($this->selectedSessionKey, $validKeys);
    }

    public function isItemSelected(string $cartKey): bool
    {
        return in_array((string) $cartKey, $this->getSelectedKeys(), true);
    }

    public function getSelectedCart(): array
    {
        if ($this->isBuyNowMode()) {
            return Session::get($this->buyNowSessionKey, []);
        }

        $cart = Session::get($this->sessionKey, []);
        $selectedKeys = $this->getSelectedKeys();

        return array_filter($cart, function ($key) use ($selectedKeys) {
            return in_array((string) $key, $selectedKeys, true);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function getSelectedItems(): Collection
    {
        return collect($this->getSelectedCart())->map(function (array $line) {
            $variant = ProductVariant::with('product.category', 'product.primaryImage')->find($line['variant_id'] ?? 0);
            $product = $variant?->product;
            $price = (float) ($variant?->final_price ?? $line['price'] ?? 0);
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

    public function getSelectedSubtotal(): float
    {
        return (float) $this->getSelectedItems()->sum('line_total');
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

        $price = (float) $variant->final_price;

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

        if (Session::has($this->selectedSessionKey)) {
            $selected = Session::get($this->selectedSessionKey, []);
            if (is_array($selected) && ! in_array($id, $selected, true)) {
                $selected[] = $id;
                Session::put($this->selectedSessionKey, $selected);
            }
        }

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

        $selected = Session::get($this->selectedSessionKey);
        if (is_array($selected)) {
            Session::put($this->selectedSessionKey, array_values(array_diff($selected, [$cartKey])));
        }

        return $cart;
    }

    protected string $couponSessionKey = 'furniture_coupon';

    public function getAvailableCoupons(): array
    {
        return Coupon::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Coupon $coupon) => [$coupon->code => $coupon->toCartArray()])
            ->all();
    }

    public function getCoupon(): ?array
    {
        return Session::get($this->couponSessionKey);
    }

    public function applyCoupon(string $code, ?User $user): array
    {
        if (! $user) {
            throw new \InvalidArgumentException('Vui lòng đăng nhập để sử dụng mã giảm giá (mỗi tài khoản được dùng 1 mã).');
        }

        $redemptions = app(CouponRedemptionService::class);
        if ($used = $redemptions->redemptionFor($user)) {
            throw new \InvalidArgumentException($redemptions->usedMessage($used));
        }

        $code = strtoupper(trim($code));
        $coupon = Coupon::findByCode($code);
        if (! $coupon) {
            throw new \InvalidArgumentException('Mã giảm giá "'.$code.'" không tồn tại hoặc đã hết hạn.');
        }

        $subtotal = $this->getSelectedSubtotal();
        if ($subtotal <= 0) {
            throw new \InvalidArgumentException('Vui lòng chọn ít nhất 1 sản phẩm trước khi áp dụng mã giảm giá.');
        }

        if (! $coupon->isValidFor($subtotal, $error)) {
            throw new \InvalidArgumentException($error.' Tạm tính các món đã chọn: '.number_format($subtotal, 0, ',', '.').'₫.');
        }

        $data = $coupon->toCartArray();
        Session::put($this->couponSessionKey, $data);

        return $data;
    }

    public function removeCoupon(): void
    {
        Session::forget($this->couponSessionKey);
    }

    public function getDiscountAmount(?float $subtotal = null): float
    {
        $coupon = $this->getCoupon();
        if (! $coupon) {
            return 0.0;
        }

        $subtotal = $subtotal ?? $this->getSelectedSubtotal();
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

    public function removeSelected(): void
    {
        if ($this->isBuyNowMode()) {
            $this->clearBuyNow();
            return;
        }

        $cart = Session::get($this->sessionKey, []);
        $selectedKeys = $this->getSelectedKeys();

        foreach ($selectedKeys as $key) {
            unset($cart[$key]);
        }

        Session::put($this->sessionKey, $cart);
        Session::forget([$this->selectedSessionKey, 'shipping_fee', 'shipping_destination']);

        if (empty($cart)) {
            Session::forget($this->couponSessionKey);
        }
    }

    public function clear(): void
    {
        Session::forget([
            $this->sessionKey,
            $this->selectedSessionKey,
            $this->couponSessionKey,
            'shipping_fee',
            'shipping_destination',
        ]);
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
        $subtotal = $this->getSelectedSubtotal();
        if ($subtotal === 0.0) {
            return 0.0;
        }

        $shipping = $this->hasCalculatedShipping() ? $this->getShippingFee() : 0.0;
        $total = $subtotal + $shipping - $this->getDiscountAmount($subtotal);
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
