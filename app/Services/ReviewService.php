<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    /**
     * Find an eligible completed and paid OrderItem for a given user and product.
     */
    public function findEligibleOrderItem(User $user, Product $product): ?OrderItem
    {
        return OrderItem::query()
            ->whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where('order_status', 'completed')
                    ->where('payment_status', 'paid');
            })
            ->whereHas('variant', function ($q) use ($product) {
                $q->where('product_id', $product->id);
            })
            ->latest('id')
            ->first();
    }

    /**
     * Get the existing review of a user for a specific product, if any.
     */
    public function getUserReview(User $user, Product $product): ?Review
    {
        return Review::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();
    }

    /**
     * Determine if a user can review a product.
     */
    public function canReview(User $user, Product $product): bool
    {
        if (! $product->is_active) {
            return false;
        }

        if ($this->getUserReview($user, $product) !== null) {
            return false;
        }

        return $this->findEligibleOrderItem($user, $product) !== null;
    }

    /**
     * Create a verified review for a product.
     *
     * @throws ValidationException
     */
    public function createReview(User $user, Product $product, array $data): Review
    {
        if (! $product->is_active) {
            throw ValidationException::withMessages([
                'product' => 'Sản phẩm này hiện không còn kinh doanh hoặc đang tạm ngừng.',
            ]);
        }

        $orderItem = $this->findEligibleOrderItem($user, $product);

        if (! $orderItem) {
            throw ValidationException::withMessages([
                'purchase' => 'Bạn chỉ có thể đánh giá sản phẩm sau khi đơn hàng được giao và thanh toán thành công.',
            ]);
        }

        if ($this->getUserReview($user, $product)) {
            throw ValidationException::withMessages([
                'review' => 'Bạn đã đánh giá sản phẩm này.',
            ]);
        }

        try {
            return DB::transaction(function () use ($user, $product, $orderItem, $data) {
                return Review::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'order_item_id' => $orderItem->id,
                    'rating' => (int) $data['rating'],
                    'comment' => isset($data['comment']) && trim($data['comment']) !== '' ? trim($data['comment']) : null,
                ]);
            });
        } catch (QueryException $e) {
            // Catch concurrent duplicate attempt
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'Duplicate')) {
                throw ValidationException::withMessages([
                    'review' => 'Bạn đã đánh giá sản phẩm này.',
                ]);
            }

            throw $e;
        }
    }

    /**
     * Update an existing review.
     */
    public function updateReview(Review $review, array $data): Review
    {
        $review->update([
            'rating' => (int) $data['rating'],
            'comment' => isset($data['comment']) && trim($data['comment']) !== '' ? trim($data['comment']) : null,
        ]);

        return $review;
    }

    /**
     * Delete an existing review.
     */
    public function deleteReview(Review $review): void
    {
        $review->delete();
    }
}
