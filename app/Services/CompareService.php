<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class CompareService
{
    public const SESSION_KEY = 'compare_product_ids';

    public const MAX = 3;

    /**
     * Active products currently in the comparison list, in the order they were added.
     */
    public function products(bool $detailed = false): Collection
    {
        $ids = array_values(array_unique(array_map('intval', session(self::SESSION_KEY, []))));

        if ($ids === []) {
            return collect();
        }

        $query = Product::query()
            ->active()
            ->with('primaryImage')
            ->whereIn('id', $ids);

        if ($detailed) {
            $query->with(['category', 'variants'])->withRatingSummary();
        }

        $products = $query->get()
            ->sortBy(fn (Product $product) => array_search($product->id, $ids, true))
            ->values();

        $validIds = $products->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($validIds !== $ids) {
            session([self::SESSION_KEY => $validIds]);
        }

        return $products;
    }

    /**
     * @return array{in_compare: bool, limited: bool, message: string, count: int}
     */
    public function toggle(Product $product): array
    {
        $ids = $this->products()->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (in_array($product->id, $ids, true)) {
            $ids = array_values(array_filter($ids, fn (int $id) => $id !== (int) $product->id));
            session([self::SESSION_KEY => $ids]);

            return [
                'in_compare' => false,
                'limited' => false,
                'message' => 'Đã bỏ "'.$product->name.'" khỏi bảng so sánh.',
                'count' => count($ids),
            ];
        }

        if (count($ids) >= self::MAX) {
            return [
                'in_compare' => false,
                'limited' => true,
                'message' => 'Chỉ so sánh được tối đa '.self::MAX.' sản phẩm. Hãy bỏ bớt một món.',
                'count' => count($ids),
            ];
        }

        $ids[] = (int) $product->id;
        session([self::SESSION_KEY => $ids]);

        return [
            'in_compare' => true,
            'limited' => false,
            'message' => 'Đã thêm "'.$product->name.'" vào bảng so sánh.',
            'count' => count($ids),
        ];
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
