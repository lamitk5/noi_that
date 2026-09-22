<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\UserCartItem;
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

        if (auth()->check()) {
            $unitPrice = (float) ($variant->price ?? $variant->product->base_price);
            UserCartItem::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'product_variant_id' => $variantId,
                ],
                [
                    'quantity' => $newQty,
                    'price_when_added' => $unitPrice,
                ]
            );
        }
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

        if (auth()->check()) {
            UserCartItem::where('user_id', auth()->id())
                ->where('product_variant_id', $variantId)
                ->update(['quantity' => $quantity]);
        }
    }

    /**
     * Remove a variant from the cart.
     */
    public function remove(int $variantId): void
    {
        $cart = session()->get('cart', []);
        unset($cart[$variantId]);
        session()->put('cart', $cart);

        if (auth()->check()) {
            UserCartItem::where('user_id', auth()->id())
                ->where('product_variant_id', $variantId)
                ->delete();
        }
    }

    /**
     * Clear all items in the cart.
     */
    public function clear(): void
    {
        session()->forget('cart');

        if (auth()->check()) {
            UserCartItem::where('user_id', auth()->id())->delete();
        }
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

    /**
     * Merge guest cart with user cart safely upon login.
     */
    public function mergeGuestCart(User $user): void
    {
        $guestCart = session()->get('cart', []);
        $userCartItems = UserCartItem::where('user_id', $user->id)
            ->with(['variant.product'])
            ->get()
            ->keyBy('product_variant_id');

        $mergedCart = [];

        // 1. Process items already in user's saved cart
        foreach ($userCartItems as $variantId => $item) {
            $variant = $item->variant;
            if (! $variant || ! $variant->product || ! $variant->product->is_active || $variant->stock <= 0) {
                $item->delete();
                continue;
            }

            $currentStock = (int) $variant->stock;
            $userQty = (int) $item->quantity;
            $guestQty = (int) ($guestCart[$variantId] ?? 0);
            $combinedQty = min($userQty + $guestQty, $currentStock);

            $mergedCart[$variantId] = $combinedQty;
            $item->update(['quantity' => $combinedQty]);
            unset($guestCart[$variantId]);
        }

        // 2. Process remaining guest items not yet in user cart
        if (! empty($guestCart)) {
            $remainingVariants = ProductVariant::query()
                ->with('product')
                ->whereIn('id', array_keys($guestCart))
                ->get()
                ->keyBy('id');

            foreach ($guestCart as $variantId => $guestQty) {
                $variant = $remainingVariants->get($variantId);
                if (! $variant || ! $variant->product || ! $variant->product->is_active || $variant->stock <= 0) {
                    continue;
                }

                $safeQty = min((int) $guestQty, (int) $variant->stock);
                $unitPrice = (float) ($variant->price ?? $variant->product->base_price);

                $mergedCart[$variantId] = $safeQty;
                UserCartItem::create([
                    'user_id' => $user->id,
                    'product_variant_id' => $variantId,
                    'quantity' => $safeQty,
                    'price_when_added' => $unitPrice,
                ]);
            }
        }

        session()->put('cart', $mergedCart);
    }

    /**
     * Check inventory changes and price differences before checkout.
     * Automatically adjusts quantities if stock dropped, and returns warning messages.
     */
    public function checkInventoryAndPriceChanges(): array
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return [];
        }

        $variants = ProductVariant::with('product')
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        $warnings = [];
        $adjustedCart = $cart;
        $needsSessionUpdate = false;

        $savedPrices = [];
        if (auth()->check()) {
            $savedPrices = UserCartItem::where('user_id', auth()->id())
                ->pluck('price_when_added', 'product_variant_id')
                ->all();
        }

        foreach ($cart as $variantId => $qty) {
            $variant = $variants->get($variantId);

            // Inactive or deleted product
            if (! $variant || ! $variant->product || ! $variant->product->is_active) {
                unset($adjustedCart[$variantId]);
                $this->remove($variantId);
                $needsSessionUpdate = true;
                $warnings[] = 'Một số sản phẩm trong giỏ hàng đã ngừng kinh doanh và được loại bỏ khỏi giỏ hàng.';
                continue;
            }

            // Out of stock
            if ($variant->stock <= 0) {
                unset($adjustedCart[$variantId]);
                $this->remove($variantId);
                $needsSessionUpdate = true;
                $warnings[] = "Sản phẩm \"{$variant->product->name}\" hiện đã hết hàng và được loại bỏ khỏi giỏ hàng.";
                continue;
            }

            // Stock lower than cart quantity
            if ($qty > $variant->stock) {
                $adjustedCart[$variantId] = (int) $variant->stock;
                $this->update($variantId, (int) $variant->stock);
                $needsSessionUpdate = true;
                $warnings[] = "Sản phẩm \"{$variant->product->name}\" chỉ còn {$variant->stock} sản phẩm trong kho. Số lượng trong giỏ hàng đã được tự động cập nhật.";
            }

            // Price change detection
            $currentPrice = (float) ($variant->price ?? $variant->product->base_price);
            if (isset($savedPrices[$variantId]) && $savedPrices[$variantId] !== null) {
                $savedPrice = (float) $savedPrices[$variantId];
                if ($savedPrice > 0 && abs($currentPrice - $savedPrice) >= 0.01) {
                    $formattedCurrent = number_format($currentPrice, 0, ',', '.') . ' ₫';
                    $formattedSaved = number_format($savedPrice, 0, ',', '.') . ' ₫';
                    $warnings[] = "Giá sản phẩm \"{$variant->product->name}\" đã được cập nhật từ {$formattedSaved} thành {$formattedCurrent}.";
                    UserCartItem::where('user_id', auth()->id())
                        ->where('product_variant_id', $variantId)
                        ->update(['price_when_added' => $currentPrice]);
                }
            }
        }

        if ($needsSessionUpdate) {
            session()->put('cart', $adjustedCart);
        }

        return array_unique($warnings);
    }

    /**
     * Buy Again: adds active, in-stock variants from a completed order into the cart.
     * Returns summary of successfully added items and individual warnings for unavailable items.
     */
    public function buyAgain(Order $order): array
    {
        $order->loadMissing('items.variant.product');

        $addedCount = 0;
        $warnings = [];

        foreach ($order->items as $item) {
            $variant = $item->variant;

            if (! $variant || ! $variant->product || ! $variant->product->is_active) {
                $warnings[] = "Sản phẩm \"{$item->product_name}\" hiện không còn kinh doanh.";
                continue;
            }

            if ($variant->stock <= 0) {
                $warnings[] = "Sản phẩm \"{$item->product_name}\" hiện đã hết hàng.";
                continue;
            }

            $currentCartQty = (int) (session()->get('cart', [])[$variant->id] ?? 0);
            $availableStock = (int) $variant->stock - $currentCartQty;

            if ($availableStock <= 0) {
                $warnings[] = "Bạn đã có đủ số lượng tối đa hiện có của sản phẩm \"{$item->product_name}\" trong giỏ hàng.";
                continue;
            }

            $qtyToAdd = min((int) $item->quantity, $availableStock);

            try {
                $this->add($variant->id, $qtyToAdd);
                $addedCount++;

                if ($qtyToAdd < $item->quantity) {
                    $warnings[] = "Sản phẩm \"{$item->product_name}\" chỉ còn {$variant->stock} sản phẩm. Đã thêm số lượng tối đa có thể.";
                }
            } catch (\Exception $e) {
                $warnings[] = "Không thể thêm \"{$item->product_name}\": " . $e->getMessage();
            }
        }

        return [
            'added_count' => $addedCount,
            'warnings' => $warnings,
        ];
    }
}
