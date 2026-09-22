<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function __construct(
        protected ReviewService $reviewService
    ) {}

    /**
     * Store a newly created review.
     */
    public function store(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ], [
            'rating.required' => 'Vui lòng chọn số sao đánh giá.',
            'rating.between' => 'Đánh giá phải từ 1 đến 5 sao.',
            'comment.max' => 'Nội dung nhận xét không được vượt quá 2000 ký tự.',
        ]);

        try {
            $this->reviewService->createReview($request->user(), $product, $validated);

            return redirect(route('products.show', $product->slug) . '#reviews')
                ->with('success', 'Cảm ơn bạn đã đánh giá sản phẩm.');
        } catch (ValidationException $e) {
            return redirect(route('products.show', $product->slug) . '#reviews')
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    /**
     * Update the specified review.
     */
    public function update(Request $request, Review $review): RedirectResponse
    {
        if ($request->user()->id !== $review->user_id) {
            abort(403, 'Bạn không có quyền chỉnh sửa đánh giá này.');
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ], [
            'rating.required' => 'Vui lòng chọn số sao đánh giá.',
            'rating.between' => 'Đánh giá phải từ 1 đến 5 sao.',
            'comment.max' => 'Nội dung nhận xét không được vượt quá 2000 ký tự.',
        ]);

        $this->reviewService->updateReview($review, $validated);

        $productSlug = $review->product?->slug;
        $url = $productSlug ? route('products.show', $productSlug) . '#reviews' : url()->previous();

        return redirect($url)->with('success', 'Đã cập nhật đánh giá.');
    }

    /**
     * Remove the specified review.
     */
    public function destroy(Request $request, Review $review): RedirectResponse
    {
        if ($request->user()->id !== $review->user_id && $request->user()->role !== 'admin') {
            abort(403, 'Bạn không có quyền xóa đánh giá này.');
        }

        $productSlug = $review->product?->slug;
        $this->reviewService->deleteReview($review);

        $url = $productSlug ? route('products.show', $productSlug) . '#reviews' : url()->previous();

        return redirect($url)->with('success', 'Đã xóa đánh giá.');
    }
}
