<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Remove Vietnamese accents for loose searching.
     */
    protected function removeAccents(string $str): string
    {
        $unicode = [
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd' => 'đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
        ];

        foreach ($unicode as $nonAccent => $accent) {
            $str = preg_replace("/($accent)/iu", $nonAccent, $str);
        }

        return mb_strtolower($str, 'UTF-8');
    }

    /**
     * Fast search suggestions endpoint.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $rawQuery = trim((string) $request->input('q', ''));
        $normalized = $this->removeAccents($rawQuery);

        if ($rawQuery === '' || mb_strlen($rawQuery) < 1) {
            // Empty state suggestions: Popular categories + Featured products
            $popularCategories = Category::where('is_active', true)
                ->orderBy('name')
                ->take(6)
                ->get(['id', 'name', 'slug']);

            $featuredProducts = Product::where('is_active', true)
                ->with(['primaryImage', 'category'])
                ->latest()
                ->take(4)
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'sku' => $p->sku,
                    'price' => (float) $p->base_price,
                    'formatted_price' => number_format($p->base_price, 0, ',', '.') . ' ₫',
                    'category' => $p->category?->name,
                    'image' => $p->primaryImage?->image_url ?? asset('images/placeholder.png'),
                    'url' => route('products.show', $p->slug),
                ]);

            return response()->json([
                'type' => 'empty_state',
                'categories' => $popularCategories,
                'products' => [],
                'popular' => $featuredProducts,
            ]);
        }

        // Search matching categories
        $categories = Category::query()
            ->where('is_active', true)
            ->where(function ($q) use ($rawQuery, $normalized) {
                $q->where('name', 'like', "%{$rawQuery}%")
                    ->orWhere('slug', 'like', "%{$normalized}%");
            })
            ->take(4)
            ->get(['id', 'name', 'slug'])
            ->map(fn ($c) => [
                'name' => $c->name,
                'url' => route('products.index', ['category' => $c->slug]),
            ]);

        // Search matching products by Name, SKU, Description, or Variant SKU
        $products = Product::query()
            ->where('is_active', true)
            ->where(function ($q) use ($rawQuery, $normalized) {
                $q->where('name', 'like', "%{$rawQuery}%")
                    ->orWhere('sku', 'like', "%{$rawQuery}%")
                    ->orWhere('short_description', 'like', "%{$rawQuery}%")
                    ->orWhere('slug', 'like', "%{$normalized}%")
                    ->orWhereHas('variants', function ($vq) use ($rawQuery) {
                        $vq->where('sku', 'like', "%{$rawQuery}%")
                            ->orWhere('color', 'like', "%{$rawQuery}%")
                            ->orWhere('material', 'like', "%{$rawQuery}%");
                    });
            })
            ->with(['primaryImage', 'category'])
            ->take(6)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'sku' => $p->sku,
                'price' => (float) $p->base_price,
                'formatted_price' => number_format($p->base_price, 0, ',', '.') . ' ₫',
                'category' => $p->category?->name,
                'image' => $p->primaryImage?->image_url ?? asset('images/placeholder.png'),
                'url' => route('products.show', $p->slug),
            ]);

        return response()->json([
            'type' => 'results',
            'query' => $rawQuery,
            'categories' => $categories,
            'products' => $products,
            'popular' => [],
        ]);
    }
}
