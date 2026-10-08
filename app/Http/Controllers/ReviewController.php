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
            'order_id' => ['nullable', 'integer'],
            'return_to' => ['nullable', 'in:orders,order'],
        ], [
            'rating.required' => 'Vui lòng chọn số sao đánh giá.',
            'rating.min' => 'Điểm đánh giá tối thiểu là 1 sao.',
            'rating.max' => 'Điểm đánh giá tối đa là 5 sao.',
            'comment.required' => 'Vui lòng nhập nội dung đánh giá của bạn.',
            'comment.min' => 'Nội dung đánh giá cần tối thiểu 5 ký tự.',
            'comment.max' => 'Nội dung đánh giá tối đa 1000 ký tự.',
        ]);

        $completedOrder = $this->completedOrderForReview($request, $user, $product);

        if (! $completedOrder) {
            return $this->reviewFailure(
                $request,
                $product,
                null,
                'Bạn chỉ có thể đánh giá sản phẩm sau khi đơn hàng chứa sản phẩm này đã được giao thành công.'
            );
        }

        $existing = Review::where('user_id', $user->id)->where('product_id', $product->id)->first();
        if ($existing && (int) $existing->order_id !== (int) $completedOrder->id) {
            return $this->reviewFailure(
                $request,
                $product,
                $completedOrder,
                'Bạn đã đánh giá sản phẩm này từ đơn hàng trước đó.'
            );
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

        return $this->reviewRedirect($request, $product, $completedOrder)->with('success', $message);
    }

    private function completedOrderForReview(Request $request, $user, Product $product): ?Order
    {
        $query = Order::query()
            ->where('user_id', $user->id)
            ->where('order_status', Order::STATUS_COMPLETED)
            ->whereHas('items.variant', fn ($variants) => $variants->where('product_id', $product->id));

        if ($request->filled('order_id')) {
            return $query->whereKey($request->integer('order_id'))->first();
        }

        return $query->latest()->first();
    }

    private function reviewFailure(Request $request, Product $product, ?Order $order, string $message): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 403);
        }

        return $this->reviewRedirect($request, $product, $order)
            ->withErrors(['review' => $message])
            ->withInput();
    }

    private function reviewRedirect(Request $request, Product $product, ?Order $order): RedirectResponse
    {
        if ($request->input('return_to') === 'orders') {
            $status = (string) $request->input('status_filter', 'all');
            $allowed = ['all', 'pending', 'confirmed', 'shipping', 'completed', 'cancelled'];
            if (! in_array($status, $allowed, true)) {
                $status = 'all';
            }

            return redirect()
                ->route('orders.index', ['status' => $status])
                ->withFragment('order-'.($order?->order_code ?? ''));
        }

        if ($request->input('return_to') === 'order' && $order) {
            return redirect()->route('orders.show', $order->order_code);
        }

        return redirect()->to(route('products.show', $product->slug).'#reviews');
    }
}
