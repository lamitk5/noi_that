<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductFeatureController extends Controller
{
    /**
     * Compare page (renders up to 4 products side by side).
     */
    public function compare(Request $request): View
    {
        $idsParam = $request->input('ids', '');
        $ids = array_filter(array_map('intval', explode(',', (string) $idsParam)));

        // Max 4 products
        $ids = array_slice($ids, 0, 4);

        $products = collect();
        if (! empty($ids)) {
            $products = Product::query()
                ->whereIn('id', $ids)
                ->where('is_active', true)
                ->with(['category', 'primaryImage', 'variants', 'reviews'])
                ->get()
                ->sortBy(function ($product) use ($ids) {
                    return array_search($product->id, $ids);
                })
                ->values();
        }

        return view('products.compare', compact('products'));
    }

    /**
     * Compare JSON API for dynamic Alpine / JS comparisons.
     */
    public function compareData(Request $request): JsonResponse
    {
        return $this->compareApi($request);
    }

    public function compareApi(Request $request): JsonResponse
    {
        $idsParam = $request->input('ids', '');
        $ids = array_filter(array_map('intval', explode(',', (string) $idsParam)));
        $ids = array_slice($ids, 0, 4);

        if (empty($ids)) {
            return response()->json(['products' => []]);
        }

        $products = Product::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->with(['category', 'primaryImage', 'variants', 'reviews'])
            ->get()
            ->map(function ($p) {
                $variants = $p->variants;
                $materials = $variants->pluck('material')->filter()->unique()->values()->all();
                $colors = $variants->pluck('color')->filter()->unique()->values()->all();
                $sizes = $variants->pluck('size')->filter()->unique()->values()->all();
                $totalStock = $variants->sum('stock');
                $avgRating = $p->reviews->count() > 0 ? round((float) $p->reviews->avg('rating'), 1) : null;

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'sku' => $p->sku,
                    'price' => (float) $p->base_price,
                    'formatted_price' => number_format($p->base_price, 0, ',', '.') . ' ₫',
                    'category' => $p->category?->name,
                    'image' => $p->primaryImage?->image_url ?? asset('images/placeholder.png'),
                    'url' => route('products.show', $p->slug),
                    'materials' => ! empty($materials) ? implode(', ', $materials) : 'Gỗ tự nhiên',
                    'colors' => ! empty($colors) ? implode(', ', $colors) : 'Màu tự nhiên',
                    'sizes' => ! empty($sizes) ? implode(', ', $sizes) : 'Tiêu chuẩn',
                    'total_stock' => $totalStock,
                    'stock_status' => $totalStock > 10 ? 'Còn hàng' : ($totalStock > 0 ? 'Sắp hết' : 'Hết hàng'),
                    'avg_rating' => $avgRating,
                    'reviews_count' => $p->reviews->count(),
                ];
            });

        return response()->json(['products' => $products]);
    }

    /**
     * Quick View endpoint for product preview modal.
     */
    public function quickView(Product $product): JsonResponse
    {
        if (! $product->is_active) {
            return response()->json(['message' => 'Sản phẩm không khả dụng.'], 404);
        }

        $product->load(['category', 'images', 'primaryImage', 'variants']);

        $images = $product->images->map(fn ($img) => [
            'id' => $img->id,
            'url' => $img->image_url,
            'is_primary' => $img->is_primary,
        ]);

        if ($images->isEmpty() && $product->primaryImage) {
            $images->push([
                'id' => $product->primaryImage->id,
                'url' => $product->primaryImage->image_url,
                'is_primary' => true,
            ]);
        }

        $variants = $product->variants->map(fn ($v) => [
            'id' => $v->id,
            'sku' => $v->sku,
            'color' => $v->color,
            'size' => $v->size,
            'material' => $v->material,
            'price' => (float) ($v->price ?? $product->base_price),
            'formatted_price' => number_format($v->price ?? $product->base_price, 0, ',', '.') . ' ₫',
            'stock' => (int) $v->stock,
            'label' => trim(implode(' - ', array_filter([$v->color, $v->size, $v->material]))),
        ]);

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'base_price' => (float) $product->base_price,
            'formatted_price' => number_format($product->base_price, 0, ',', '.') . ' ₫',
            'short_description' => $product->short_description,
            'category' => $product->category?->name,
            'url' => route('products.show', $product->slug),
            'images' => $images,
            'variants' => $variants,
            'total_stock' => $product->variants->sum('stock'),
        ]);
    }
}
