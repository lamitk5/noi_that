<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

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

        if ($searchQuery !== '') {
            $query->where('name', 'like', '%' . $searchQuery . '%');
        }

        if ($categorySlug) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug)->where('is_active', true);
            });
        }

        match ($sort) {
            'price_asc' => $query->orderBy('base_price', 'asc')->orderBy('id', 'asc'),
            'price_desc' => $query->orderBy('base_price', 'desc')->orderBy('id', 'desc'),
            default => $query->orderBy('created_at', 'desc')->orderBy('id', 'desc'),
        };

        $products = $query->paginate(12)->withQueryString();

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $currentCategory = $categorySlug
            ? $categories->firstWhere('slug', $categorySlug)
            : null;

        return view('products.index', compact(
            'products',
            'categories',
            'currentCategory',
            'searchQuery',
            'sort',
        ));
    }

    public function show(string $slug)
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with(['category', 'images', 'primaryImage', 'variants'])
            ->firstOrFail();

        $product->increment('views_count');

        $relatedProducts = Product::query()
            ->with(['category', 'primaryImage'])
            ->where('category_id', $product->category_id)
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->latest()
            ->limit(4)
            ->get();

        return view('products.show', compact('product', 'relatedProducts'));
    }
}
