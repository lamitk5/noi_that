<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Get all cart items loaded with real database models.
     */
    public function getItems(): Collection
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return collect();
        }

        $variants = ProductVariant::query()
            ->with(['product.primaryImage', 'product.category'])
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        $items = collect();

        foreach ($cart as $variantId => $quantity) {
            $variant = $variants->get($variantId);
            if (! $variant || ! $variant->product || ! $variant->product->is_active) {
                continue;
            }

            $unitPrice = (float) ($variant->price ?? $variant->product->base_price);
            $qty = (int) $quantity;

            $items->push((object) [
                'variant_id' => $variant->id,
                'variant' => $variant,
                'product' => $variant->product,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $unitPrice * $qty,
                'stock' => (int) $variant->stock,
                'is_available' => $variant->stock > 0,
                'has_sufficient_stock' => $variant->stock >= $qty,
            ]);
        }

        return $items;
    }

    /**
     * Add a variant to the cart.
     */
    public function add(int $variantId, int $quantity = 1): void
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Số lượng thêm vào giỏ phải lớn hơn hoặc bằng 1.',
            ]);
        }

        $variant = ProductVariant::with('product')->find($variantId);
        if (! $variant || ! $variant->product || ! $variant->product->is_active) {
            throw ValidationException::withMessages([
                'variant_id' => 'Sản phẩm hoặc tùy chọn không tồn tại hoặc đã ngừng kinh doanh.',
            ]);
        }

        if ($variant->stock <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Sản phẩm này hiện đã hết hàng trong kho.',
            ]);
        }

        $cart = session()->get('cart', []);
        $currentQty = (int) ($cart[$variantId] ?? 0);
        $newQty = $currentQty + $quantity;

        if ($newQty > $variant->stock) {
            throw ValidationException::withMessages([
                'quantity' => "Số lượng vượt quá tồn kho hiện có (kho chỉ còn {$variant->stock} sản phẩm).",
            ]);
        }

        $cart[$variantId] = $newQty;
        session()->put('cart', $cart);
    }

    /**
     * Update quantity for a variant in the cart.
     */
    public function update(int $variantId, int $quantity): void
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Số lượng phải lớn hơn hoặc bằng 1.',
            ]);
        }

        $variant = ProductVariant::with('product')->find($variantId);
        if (! $variant || ! $variant->product || ! $variant->product->is_active) {
            throw ValidationException::withMessages([
                'variant_id' => 'Sản phẩm hoặc tùy chọn không tồn tại.',
            ]);
        }

        if ($quantity > $variant->stock) {
            throw ValidationException::withMessages([
                'quantity' => "Số lượng vượt quá tồn kho hiện có (kho chỉ còn {$variant->stock} sản phẩm).",
            ]);
        }

        $cart = session()->get('cart', []);
        $cart[$variantId] = $quantity;
        session()->put('cart', $cart);
    }

    /**
     * Remove a variant from the cart.
     */
    public function remove(int $variantId): void
    {
        $cart = session()->get('cart', []);
        unset($cart[$variantId]);
        session()->put('cart', $cart);
    }

    /**
     * Clear all items in the cart.
     */
    public function clear(): void
    {
        session()->forget('cart');
    }

    /**
     * Get total quantity of items in the cart.
     */
    public function count(): int
    {
        return (int) array_sum(session()->get('cart', []));
    }

    /**
     * Calculate cart subtotal.
     */
    public function subtotal(): float
    {
        return (float) $this->getItems()->sum('line_total');
    }
}
