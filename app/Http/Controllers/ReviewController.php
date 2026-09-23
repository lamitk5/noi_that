<?php

namespace App\Http\Controllers;

use App\Models\Order;
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
        ]);

        // Verify that user actually ordered this product and order is completed
        $completedOrder = Order::where('user_id', $user->id)
            ->where('order_status', 'completed')
            ->whereHas('items.variant', function ($q) use ($product) {
                $q->where('product_id', $product->id);
            })
            ->latest()
            ->first();

        // Also check direct product_id fallback if legacy order
        if (! $completedOrder) {
            $completedOrder = Order::where('user_id', $user->id)
                ->where('order_status', 'completed')
                ->whereHas('items', function ($q) use ($product) {
                    $q->where('product_name', $product->name);
                })
                ->latest()
                ->first();
        }

        if (! $completedOrder) {
            $message = 'Bạn chỉ có thể đánh giá sản phẩm sau khi đơn hàng chứa sản phẩm này đã được giao thành công.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }
            return back()->withErrors(['review' => $message]);
        }

        $review = Review::updateOrCreate(
            [
                'user_id' => $user->id,
                'product_id' => $product->id,
            ],
            [
                'order_id' => $completedOrder->id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
                'is_approved' => true,
            ]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cảm ơn bạn đã đánh giá sản phẩm!',
                'data' => $review->load('user'),
            ], 201);
        }

        return back()->with('success', 'Cảm ơn bạn đã đánh giá sản phẩm!');
    }
}
