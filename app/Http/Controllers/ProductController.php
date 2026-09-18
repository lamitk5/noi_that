<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display product catalog with filtering, searching, and sorting.
     */
    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->only([
            'category_id',
            'category_slug',
            'min_price',
            'max_price',
            'material',
            'search',
            'in_stock',
            'sort',
        ]);

        $query = Product::active()
            ->with(['category', 'images'])
            ->filter($filters);

        $products = $query->paginate(12)->withQueryString();

        $categories = Category::active()->withCount('products')->get();

        $materials = Product::active()
            ->whereNotNull('material')
            ->distinct()
            ->pluck('material');

        $priceStats = [
            'min' => Product::active()->min('price') ?? 0,
            'max' => Product::active()->max('price') ?? 0,
        ];

        $data = compact('products', 'categories', 'materials', 'priceStats', 'filters');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('products.index', $data);
    }

    /**
     * Display product details.
     */
    public function show(Request $request, string $slug): View|JsonResponse
    {
        $product = Product::active()
            ->where('slug', $slug)
            ->with(['category', 'images'])
            ->firstOrFail();

        // Increment view counter
        $product->increment('views_count');

        // Related products in the same category
        $relatedProducts = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with(['category', 'images'])
            ->take(4)
            ->get();

        $data = compact('product', 'relatedProducts');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('products.show', $data);
    }
}
