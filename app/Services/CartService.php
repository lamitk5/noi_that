<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Session;

class CartService
{
    protected string $sessionKey = 'furniture_cart';

    /**
     * Get full cart items from session.
     */
    public function getCart(): array
    {
        return Session::get($this->sessionKey, []);
    }

    /**
     * Add product to cart with stock validation.
     */
    public function add(Product $product, int $quantity = 1): array
    {
        $cart = $this->getCart();
        $id = $product->id;

        $currentQty = isset($cart[$id]) ? $cart[$id]['quantity'] : 0;
        $newQty = $currentQty + $quantity;

        if ($newQty > $product->stock_quantity) {
            throw new \InvalidArgumentException("Số lượng yêu cầu ({$newQty}) vượt quá số lượng còn lại trong kho ({$product->stock_quantity}).");
        }

        $effectivePrice = $product->final_price;

        $cart[$id] = [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'price' => $effectivePrice,
            'original_price' => (float) $product->price,
            'image' => $product->primary_image_url,
            'material' => $product->material,
            'quantity' => $newQty,
            'subtotal' => $effectivePrice * $newQty,
        ];

        Session::put($this->sessionKey, $cart);

        return $cart;
    }

    /**
     * Update product quantity in cart.
     */
    public function update(int $productId, int $quantity): array
    {
        $cart = $this->getCart();

        if (!isset($cart[$productId])) {
            throw new \InvalidArgumentException('Sản phẩm không có trong giỏ hàng.');
        }

        if ($quantity <= 0) {
            return $this->remove($productId);
        }

        $product = Product::findOrFail($productId);
        if ($quantity > $product->stock_quantity) {
            throw new \InvalidArgumentException("Số lượng yêu cầu ({$quantity}) vượt quá tồn kho ({$product->stock_quantity}).");
        }

        $cart[$productId]['quantity'] = $quantity;
        $cart[$productId]['subtotal'] = $cart[$productId]['price'] * $quantity;

        Session::put($this->sessionKey, $cart);

        return $cart;
    }

    /**
     * Remove item from cart.
     */
    public function remove(int $productId): array
    {
        $cart = $this->getCart();
        unset($cart[$productId]);
        Session::put($this->sessionKey, $cart);

        return $cart;
    }

    /**
     * Clear all items in cart.
     */
    public function clear(): void
    {
        Session::forget($this->sessionKey);
    }

    /**
     * Calculate subtotal of all items.
     */
    public function getSubtotal(): float
    {
        $cart = $this->getCart();
        return (float) array_sum(array_column($cart, 'subtotal'));
    }

    /**
     * Calculate shipping fee (Free shipping for orders >= 5,000,000 VND).
     */
    public function getShippingFee(): float
    {
        $subtotal = $this->getSubtotal();
        if ($subtotal === 0.0) {
            return 0.0;
        }

        return $subtotal >= 5000000 ? 0.0 : 50000.0;
    }

    /**
     * Calculate total price including shipping fee.
     */
    public function getTotal(): float
    {
        return $this->getSubtotal() + $this->getShippingFee();
    }

    /**
     * Count total items quantity in cart.
     */
    public function count(): int
    {
        $cart = $this->getCart();
        return array_sum(array_column($cart, 'quantity'));
    }
}
