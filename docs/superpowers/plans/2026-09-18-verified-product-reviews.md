# Implementation Plan - Phase 7: Verified Product Reviews + Ratings + Comments

## Goal
Implement a verified purchase review system for the "Mộc An" ecommerce platform where only authenticated customers who have purchased and received a product (order completed and paid) can submit a single 1-to-5 star rating and comment. Customers can update or delete their own review. Product detail displays aggregated ratings, review counts, verified purchase badges, and individual customer feedback.

## Existing Schema Audit
- `users`: ID, name, email, role (`customer`, `admin`).
- `products`: ID, category_id, name, slug, base_price, is_active.
- `product_variants`: ID, product_id, color, stock, price, sku.
- `orders`: ID, user_id, order_code, order_status, payment_status, total_price.
- `order_items`: ID, order_id, product_variant_id, product_name, variant_info, quantity, price.
- `reviews` / `product_reviews`: None existing.

## Proposed Review Schema
Table: `reviews`
- `id`: primary key
- `user_id`: foreignId -> `users.id` (cascade on delete)
- `product_id`: foreignId -> `products.id` (cascade on delete)
- `order_item_id`: foreignId -> `order_items.id` (cascade on delete)
- `rating`: unsignedTinyInteger (1 to 5)
- `comment`: text, nullable
- `timestamps`: created_at, updated_at
- Constraints:
  - `unique(['user_id', 'product_id'])` (one active review per customer per product)
  - `index(['product_id', 'created_at'])` (fast listing and pagination)

## Verified Purchase Rule
A user is eligible to review Product `P` if and only if there exists:
```sql
SELECT oi.* 
FROM order_items oi
JOIN orders o ON o.id = oi.order_id
JOIN product_variants pv ON pv.id = oi.product_variant_id
WHERE o.user_id = :current_user_id
  AND o.order_status = 'completed'
  AND o.payment_status = 'paid'
  AND pv.product_id = :target_product_id
ORDER BY oi.id DESC
LIMIT 1
```
Any other state (`pending`, `confirmed`, `packed`, `shipping`, `canceled`, or unpaid/payment-failed) strictly disqualifies the customer from reviewing.

## Architecture & Service Layer
- **Model**: `App\Models\Review`
  - BelongsTo: `User`, `Product`, `OrderItem`
- **Model Updates**:
  - `Product`: `hasMany(Review::class)`
  - `User`: `hasMany(Review::class)`
- **Domain Service**: `App\Services\ReviewService`
  - `findEligibleOrderItem(User $user, Product $product): ?OrderItem`
  - `canReview(User $user, Product $product): bool`
  - `getUserReview(User $user, Product $product): ?Review`
  - `createReview(User $user, Product $product, array $data): Review`
  - `updateReview(Review $review, array $data): Review`
  - `deleteReview(Review $review): void`

## Routes
```php
Route::middleware('auth')->group(function () {
    Route::post('/san-pham/{product:slug}/danh-gia', [ReviewController::class, 'store'])->name('reviews.store');
    Route::patch('/danh-gia/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/danh-gia/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
});
```

## Authorization & Validation
- **Store**:
  - Requires authenticated user.
  - Verifies eligible completed+paid order item via `ReviewService`.
  - Blocks duplicate reviews (handles concurrent race conditions gracefully).
  - Validates:
    - `rating`: `['required', 'integer', 'between:1,5']`
    - `comment`: `['nullable', 'string', 'max:2000']`
  - Server automatically binds `user_id`, `product_id`, and `order_item_id`.
- **Update**:
  - Checks authorization: `auth()->id() === $review->user_id` (403 if mismatch).
  - Validates `rating` (1-5) and `comment` (max 2000).
- **Destroy**:
  - Checks authorization: `auth()->id() === $review->user_id` (403 if mismatch).
  - Deletes review, allowing re-review if desired.

## Product Detail Integration
- In `ProductController::show`:
  - Eager load or aggregate reviews:
    - Average rating: `(float) $product->reviews()->avg('rating')`
    - Reviews count: `(int) $product->reviews()->count()`
    - Reviews paginated list: `$product->reviews()->with('user')->latest()->paginate(5, ['*'], 'reviews_page')`
  - For authenticated user:
    - Check eligibility via `ReviewService::findEligibleOrderItem`
    - Check existing review via `ReviewService::getUserReview`
- In `resources/views/products/show.blade.php`:
  - Section `#reviews` styled with semantic theme tokens (supporting 5 themes).
  - Summary: Star rating distribution/average, total count.
  - Review form (if eligible & not reviewed yet), or "Đánh giá của bạn" with Edit/Delete modal/form if already reviewed.
  - Message for guests ("Đăng nhập để đánh giá") or non-eligible users ("Bạn có thể đánh giá sau khi đơn hàng được giao thành công.").
  - Review list: Customer name, rating stars, formatted date, badge "Đã mua hàng", comment (escaped, no `{!! !!}`).
- In `resources/views/orders/show.blade.php`:
  - When order is `completed` and `paid`, render CTA "Đánh giá sản phẩm" linking to `route('products.show', $product->slug) . '#reviews'`.

## Test Plan (`tests/Feature/ProductReviewTest.php`)
1. Guest cannot submit review (redirect to login).
2. Non-purchaser cannot review (403/validation error).
3. Orders in `pending`, `confirmed`, `packed`, `shipping`, `canceled` cannot review.
4. Completed but unpaid order cannot review.
5. Completed + payment failed cannot review.
6. Completed + paid + correct product can review successfully.
7. Completed + paid for Product A cannot review Product B.
8. One review per customer per product; second review attempt rejected.
9. Rating validation (0 rejected, 6 rejected, non-integer rejected, 1-5 accepted).
10. Comment length validation (>2000 characters rejected).
11. Owner can update their own review.
12. Stranger cannot update another user's review (403).
13. Owner can delete their own review.
14. Stranger cannot delete another user's review (403).
15. Mass assignment attack ignored/protected (user_id/product_id/order_item_id cannot be spoofed).
16. XSS safety (raw HTML/scripts escaped in display).
17. Product detail shows average rating and reviews count.
18. Order detail displays "Đánh giá sản phẩm" CTA for completed+paid items.
