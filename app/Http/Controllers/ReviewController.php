<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'rating.required' => 'Vui lòng chọn số sao đánh giá.',
            'rating.min' => 'Điểm đánh giá tối thiểu là 1 sao.',
            'rating.max' => 'Điểm đánh giá tối đa là 5 sao.',
            'comment.required' => 'Vui lòng nhập nội dung đánh giá của bạn.',
            'comment.min' => 'Nội dung đánh giá cần tối thiểu 5 ký tự.',
            'comment.max' => 'Nội dung đánh giá tối đa 1000 ký tự.',
        ]);

        $completedOrder = $product->completedOrderFor($user);

        if (! $completedOrder) {
            $message = 'Bạn chỉ có thể đánh giá sản phẩm sau khi đơn hàng chứa sản phẩm này đã được giao thành công.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }

            return redirect()->to(route('products.show', $product->slug).'#reviews')->withErrors(['review' => $message]);
        }

        $existing = Review::where('user_id', $user->id)->where('product_id', $product->id)->first();
        if ($existing && $existing->order_id !== $completedOrder->id) {
            $message = 'Bạn đã đánh giá sản phẩm này từ đơn hàng trước đó.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }

            return redirect()->to(route('products.show', $product->slug).'#reviews')->withErrors(['review' => $message]);
        }

        $review = Review::updateOrCreate(
            [
                'user_id' => $user->id,
                'product_id' => $product->id,
            ],
            [
                'order_id' => $completedOrder->id,
                'rating' => $validated['rating'],
                'comment' => strip_tags($validated['comment']),
                'is_approved' => true,
            ]
        );

        $message = $review->wasRecentlyCreated
            ? 'Cảm ơn bạn đã đánh giá sản phẩm!'
            : 'Đánh giá của bạn đã được cập nhật.';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $review->load('user:id,name'),
            ], 201);
        }

        return redirect()->to(route('products.show', $product->slug).'#reviews')->with('success', $message);
    }
}
