<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    /**
     * Get similar products from the same category.
     */
    public function getSimilarProducts(Product $product, int $limit = 4): Collection
    {
        return Product::query()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->with(['category', 'primaryImage'])
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * Get products frequently bought together with the given product.
     */
    public function getFrequentlyBoughtTogether(Product $product, int $limit = 3): Collection
    {
        // 1. Get variant IDs of this product
        $variantIds = $product->variants()->pluck('id');

        if ($variantIds->isEmpty()) {
            return $this->getSimilarProducts($product, $limit);
        }

        // 2. Find order IDs containing this product (strictly completed & paid only)
        $orderIds = OrderItem::whereIn('product_variant_id', $variantIds)
            ->whereHas('order', function ($query) {
                $query->where('order_status', Order::STATUS_COMPLETED)
                    ->where('payment_status', Order::PAYMENT_PAID);
            })
            ->pluck('order_id')
            ->unique();

        if ($orderIds->isEmpty()) {
            return $this->getSimilarProducts($product, $limit);
        }

        // 3. Find other product IDs in those orders
        $coBoughtVariantIds = OrderItem::whereIn('order_id', $orderIds)
            ->whereNotIn('product_variant_id', $variantIds)
            ->select('product_variant_id', DB::raw('COUNT(*) as frequency'))
            ->groupBy('product_variant_id')
            ->orderByDesc('frequency')
            ->take(10)
            ->pluck('product_variant_id');

        if ($coBoughtVariantIds->isEmpty()) {
            return $this->getSimilarProducts($product, $limit);
        }

        $coBoughtProducts = Product::query()
            ->whereHas('variants', function ($q) use ($coBoughtVariantIds) {
                $q->whereIn('id', $coBoughtVariantIds);
            })
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->with(['category', 'primaryImage'])
            ->take($limit)
            ->get();

        // If fewer than limit, backfill with similar products
        if ($coBoughtProducts->count() < $limit) {
            $needed = $limit - $coBoughtProducts->count();
            $existingIds = $coBoughtProducts->pluck('id')->push($product->id)->all();

            $backfill = Product::query()
                ->where('category_id', $product->category_id)
                ->whereNotIn('id', $existingIds)
                ->where('is_active', true)
                ->with(['category', 'primaryImage'])
                ->take($needed)
                ->get();

            $coBoughtProducts = $coBoughtProducts->merge($backfill);
        }

        return $coBoughtProducts;
    }

    /**
     * Get recently viewed products from session.
     */
    public function getRecentlyViewed(array|int $excludeIds = [], int $limit = 4): Collection
    {
        $exclude = is_array($excludeIds) ? $excludeIds : [$excludeIds];
        $viewedIds = session()->get('recently_viewed', []);

        if (empty($viewedIds)) {
            return new Collection();
        }

        $filteredIds = array_values(array_diff($viewedIds, $exclude));

        if (empty($filteredIds)) {
            return new Collection();
        }

        $products = Product::query()
            ->whereIn('id', $filteredIds)
            ->where('is_active', true)
            ->with(['category', 'primaryImage'])
            ->get()
            ->keyBy('id');

        // Preserve session ordering (most recently viewed first)
        $ordered = new Collection();
        foreach ($filteredIds as $id) {
            if (isset($products[$id])) {
                $ordered->push($products[$id]);
                if ($ordered->count() >= $limit) {
                    break;
                }
            }
        }

        return $ordered;
    }

    /**
     * Record a product visit in session.
     */
    public function recordView(Product $product): void
    {
        $viewed = session()->get('recently_viewed', []);

        // Prepend and keep unique, max 10
        $viewed = array_values(array_unique(array_merge([$product->id], $viewed)));

        if (count($viewed) > 10) {
            $viewed = array_slice($viewed, 0, 10);
        }

        session()->put('recently_viewed', $viewed);
    }
}
