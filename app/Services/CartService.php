<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    protected string $sessionKey = 'furniture_cart';

    public function getCart(): array
    {
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

    public function clear(): void
    {
        Session::forget($this->sessionKey);
    }

    public function getShippingFee(): float
    {
        $subtotal = $this->getSubtotal();
        if ($subtotal === 0.0) {
            return 0.0;
        }

        return $subtotal >= 5000000 ? 0.0 : 50000.0;
    }

    public function getTotal(): float
    {
        return $this->getSubtotal() + $this->getShippingFee();
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
