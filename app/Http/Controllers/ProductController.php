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

    public function show(
        Product $product,
        \App\Services\ReviewService $reviewService,
        \App\Services\RecommendationService $recommendationService
    ) {
        if (! $product->is_active) {
            abort(404);
        }

        $product->load(['category', 'images', 'primaryImage', 'variants']);

        // Record browsing history
        $recommendationService->recordView($product);

        $reviewsCount = $product->reviews()->count();
        $reviewsAvg = $reviewsCount > 0 ? round((float) $product->reviews()->avg('rating'), 1) : 0;
        $reviews = $product->reviews()->with('user')->latest()->paginate(5, ['*'], 'reviews_page')->withQueryString();

        $canReview = false;
        $userReview = null;
        $hasCompletedPurchase = false;

        if (auth()->check()) {
            /** @var \App\Models\User $user */
            $user = auth()->user();
            $canReview = $reviewService->canReview($user, $product);
            $userReview = $reviewService->getUserReview($user, $product);
            $hasCompletedPurchase = $reviewService->findEligibleOrderItem($user, $product) !== null;
        }

        $similarProducts = $recommendationService->getSimilarProducts($product, 4);
        $relatedProducts = $similarProducts;
        $frequentlyBoughtTogether = $recommendationService->getFrequentlyBoughtTogether($product, 4);
        $recentlyViewed = $recommendationService->getRecentlyViewed($product->id, 4);

        return view('products.show', compact(
            'product',
            'relatedProducts',
            'similarProducts',
            'frequentlyBoughtTogether',
            'recentlyViewed',
            'reviews',
            'reviewsCount',
            'reviewsAvg',
            'canReview',
            'userReview',
            'hasCompletedPurchase'
        ));
    }
}
