<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $searchQuery = trim((string) $request->input('q', ''));
        $categorySlug = $request->input('category');
        $sort = $request->input('sort', 'latest');

        $query = Product::query()
            ->with(['category', 'primaryImage'])
            ->where('is_active', true);

        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $material = $request->input('material');

        if ($searchQuery !== '') {
            $query->where('name', 'like', '%' . $searchQuery . '%');
        }

        if ($categorySlug) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug)->where('is_active', true);
            });
        }

        if ($minPrice !== null && $minPrice !== '' && is_numeric($minPrice)) {
            $query->where('base_price', '>=', (float) $minPrice);
        }

        if ($maxPrice !== null && $maxPrice !== '' && is_numeric($maxPrice)) {
            $query->where('base_price', '<=', (float) $maxPrice);
        }

        if ($material) {
            $query->whereHas('variants', function ($q) use ($material) {
                $q->where('material', 'like', '%' . $material . '%');
            });
        }

        match ($sort) {
            'price_asc' => $query->orderBy('base_price', 'asc')->orderBy('id', 'asc'),
            'price_desc' => $query->orderBy('base_price', 'desc')->orderBy('id', 'desc'),
            default => $query->orderBy('created_at', 'desc')->orderBy('id', 'desc'),
        };

        $products = $query->paginate(12)->withQueryString();

        $categories = Cache::remember('active_categories_list_v1', 600, function () {
            return Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        });

        $availableMaterials = ['Gỗ sồi', 'Gỗ tự nhiên', 'Gỗ cao su', 'Gỗ công nghiệp', 'Da', 'Nỉ'];

        $currentCategory = $categorySlug
            ? $categories->firstWhere('slug', $categorySlug)
            : null;

        return view('products.index', compact(
            'products',
            'categories',
            'currentCategory',
            'searchQuery',
            'sort',
            'minPrice',
            'maxPrice',
            'material',
            'availableMaterials',
        ));
    }

    public function show(Product $product)
    {
        if (! $product->is_active) {
            abort(404);
        }

        $product->load(['category', 'images', 'primaryImage', 'variants', 'approvedReviews.user']);

        $canReview = false;
        $userReview = null;
        if (auth()->check()) {
            $userId = auth()->id();
            $canReview = \App\Models\Order::where('user_id', $userId)
                ->where('order_status', 'completed')
                ->where(function ($orderQ) use ($product) {
                    $orderQ->whereHas('items.variant', function ($q) use ($product) {
                        $q->where('product_id', $product->id);
                    })->orWhereHas('items', function ($q) use ($product) {
                        $q->where('product_name', $product->name);
                    });
                })
                ->exists();

            $userReview = $product->reviews()->where('user_id', $userId)->first();
        }

        $relatedProducts = Product::query()
            ->with(['category', 'primaryImage'])
            ->where('category_id', $product->category_id)
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->latest()
            ->limit(4)
            ->get();

        $bundleProducts = Product::query()
            ->with(['category', 'primaryImage', 'variants'])
            ->where('category_id', $product->category_id)
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->limit(2)
            ->get();

        return view('products.show', compact('product', 'relatedProducts', 'bundleProducts', 'canReview', 'userReview'));
    }
}
