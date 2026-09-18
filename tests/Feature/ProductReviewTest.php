<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    private function createProductWithVariant(string $name = 'Bàn Trà Mộc An', bool $isActive = true): array
    {
        $category = Category::create([
            'name' => 'Phòng Khách ' . uniqid(),
            'slug' => 'phong-khach-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name) . '-' . uniqid(),
            'sku' => 'SKU-' . strtoupper(uniqid()),
            'base_price' => 3500000,
            'is_active' => $isActive,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'color' => 'Gỗ Sồi',
            'price' => 3500000,
            'stock' => 20,
            'sku' => 'SKU-VAR-' . strtoupper(uniqid()),
        ]);

        return [$product, $variant];
    }

    private function createOrderForUser(User $user, ProductVariant $variant, string $orderStatus = 'completed', string $paymentStatus = 'paid'): Order
    {
        $order = Order::create([
            'user_id' => $user->id,
            'order_code' => 'ORD-' . strtoupper(uniqid()),
            'customer_name' => $user->name,
            'customer_phone' => '0912345678',
            'customer_email' => $user->email,
            'shipping_address' => 'Hà Nội',
            'total_price' => $variant->price,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => $paymentStatus,
            'order_status' => $orderStatus,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'product_name' => $variant->product->name,
            'variant_info' => $variant->color,
            'quantity' => 1,
            'price' => $variant->price,
        ]);

        return $order;
    }

    public function test_guest_cannot_submit_review(): void
    {
        [$product] = $this->createProductWithVariant();

        $response = $this->post(route('reviews.store', $product->slug), [
            'rating' => 5,
            'comment' => 'Sản phẩm rất tốt!',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_user_without_purchase_cannot_review(): void
    {
        $user = User::factory()->create();
        [$product] = $this->createProductWithVariant();

        $response = $this->actingAs($user)->post(route('reviews.store', $product->slug), [
            'rating' => 5,
            'comment' => 'Chưa mua nhưng đánh giá thử.',
        ]);

        $response->assertSessionHasErrors('purchase');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_user_with_pending_order_cannot_review(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant();
        $this->createOrderForUser($user, $variant, 'pending', 'pending');

        $response = $this->actingAs($user)->post(route('reviews.store', $product->slug), [
            'rating' => 5,
            'comment' => 'Đơn đang pending.',
        ]);

        $response->assertSessionHasErrors('purchase');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_user_with_confirmed_order_cannot_review(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant();
        $this->createOrderForUser($user, $variant, 'confirmed', 'paid');

        $response = $this->actingAs($user)->post(route('reviews.store', $product->slug), [
            'rating' => 4,
            'comment' => 'Đơn mới confirmed.',
        ]);

        $response->assertSessionHasErrors('purchase');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_user_with_packed_order_cannot_review(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant();
        $this->createOrderForUser($user, $variant, 'packed', 'paid');

        $response = $this->actingAs($user)->post(route('reviews.store', $product->slug), [
            'rating' => 4,
            'comment' => 'Đơn mới đóng gói.',
        ]);

        $response->assertSessionHasErrors('purchase');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_user_with_shipping_order_cannot_review(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant();
        $this->createOrderForUser($user, $variant, 'shipping', 'paid');

        $response = $this->actingAs($user)->post(route('reviews.store', $product->slug), [
            'rating' => 4,
            'comment' => 'Đơn đang giao.',
        ]);

        $response->assertSessionHasErrors('purchase');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_user_with_canceled_order_cannot_review(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant();
        $this->createOrderForUser($user, $variant, 'canceled', 'pending');

        $response = $this->actingAs($user)->post(route('reviews.store', $product->slug), [
            'rating' => 1,
            'comment' => 'Đơn đã hủy.',
        ]);

        $response->assertSessionHasErrors('purchase');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_user_with_completed_but_unpaid_order_cannot_review(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant();
        $this->createOrderForUser($user, $variant, 'completed', 'pending');

        $response = $this->actingAs($user)->post(route('reviews.store', $product->slug), [
            'rating' => 5,
            'comment' => 'Đơn chưa thanh toán.',
        ]);

        $response->assertSessionHasErrors('purchase');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_user_with_completed_and_payment_failed_order_cannot_review(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant();
        $this->createOrderForUser($user, $variant, 'completed', 'failed');

        $response = $this->actingAs($user)->post(route('reviews.store', $product->slug), [
            'rating' => 5,
            'comment' => 'Thanh toán lỗi.',
        ]);

        $response->assertSessionHasErrors('purchase');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_user_with_completed_and_paid_order_can_review(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant();
        $this->createOrderForUser($user, $variant, 'completed', 'paid');

        $response = $this->actingAs($user)->post(route('reviews.store', $product->slug), [
            'rating' => 5,
            'comment' => 'Chất lượng hoàn thiện rất tinh xảo, gỗ thơm tự nhiên.',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('products.show', $product->slug) . '#reviews');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'comment' => 'Chất lượng hoàn thiện rất tinh xảo, gỗ thơm tự nhiên.',
        ]);
    }

    public function test_user_with_completed_order_for_different_product_cannot_review(): void
    {
        $user = User::factory()->create();
        [$productA, $variantA] = $this->createProductWithVariant('Bàn A');
        [$productB] = $this->createProductWithVariant('Ghế B');

        $this->createOrderForUser($user, $variantA, 'completed', 'paid');

        // Attempting to review Product B
        $response = $this->actingAs($user)->post(route('reviews.store', $productB->slug), [
            'rating' => 5,
            'comment' => 'Đánh giá nhầm sản phẩm.',
        ]);

        $response->assertSessionHasErrors('purchase');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_user_cannot_submit_duplicate_review_for_same_product(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant();
        $this->createOrderForUser($user, $variant, 'completed', 'paid');

        // First review succeeds
        $resp1 = $this->actingAs($user)->post(route('reviews.store', $product->slug), [
            'rating' => 5,
            'comment' => 'Đánh giá lần 1.',
        ]);
        $resp1->assertSessionHasNoErrors();

        // Second review attempt fails
        $resp2 = $this->actingAs($user)->post(route('reviews.store', $product->slug), [
            'rating' => 4,
            'comment' => 'Đánh giá lần 2.',
        ]);
        $resp2->assertSessionHasErrors('review');
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_rating_must_be_between_1_and_5(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant();
        $this->createOrderForUser($user, $variant, 'completed', 'paid');

        // 0 rating rejected
        $resp0 = $this->actingAs($user)->post(route('reviews.store', $product->slug), ['rating' => 0]);
        $resp0->assertSessionHasErrors('rating');

        // 6 rating rejected
        $resp6 = $this->actingAs($user)->post(route('reviews.store', $product->slug), ['rating' => 6]);
        $resp6->assertSessionHasErrors('rating');

        // String rating rejected
        $respStr = $this->actingAs($user)->post(route('reviews.store', $product->slug), ['rating' => 'tuyệt vời']);
        $respStr->assertSessionHasErrors('rating');
    }

    public function test_comment_cannot_exceed_max_characters(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant();
        $this->createOrderForUser($user, $variant, 'completed', 'paid');

        $longComment = str_repeat('A', 2001);
        $response = $this->actingAs($user)->post(route('reviews.store', $product->slug), [
            'rating' => 5,
            'comment' => $longComment,
        ]);

        $response->assertSessionHasErrors('comment');
    }

    public function test_owner_can_update_their_review(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant();
        $this->createOrderForUser($user, $variant, 'completed', 'paid');

        $this->actingAs($user)->post(route('reviews.store', $product->slug), [
            'rating' => 4,
            'comment' => 'Nội dung ban đầu.',
        ]);

        $review = Review::where('user_id', $user->id)->firstOrFail();

        $response = $this->actingAs($user)->patch(route('reviews.update', $review->id), [
            'rating' => 5,
            'comment' => 'Dùng 1 tháng thấy rất bền, tăng lên 5 sao!',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(5, $review->fresh()->rating);
        $this->assertEquals('Dùng 1 tháng thấy rất bền, tăng lên 5 sao!', $review->fresh()->comment);
    }

    public function test_stranger_cannot_update_another_users_review(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant();
        $this->createOrderForUser($owner, $variant, 'completed', 'paid');

        $this->actingAs($owner)->post(route('reviews.store', $product->slug), [
            'rating' => 5,
            'comment' => 'Review của chủ sở hữu.',
        ]);

        $review = Review::where('user_id', $owner->id)->firstOrFail();

        $response = $this->actingAs($stranger)->patch(route('reviews.update', $review->id), [
            'rating' => 1,
            'comment' => 'Hacker phá hoại.',
        ]);

        $response->assertStatus(403);
        $this->assertEquals(5, $review->fresh()->rating);
    }

    public function test_owner_can_delete_their_review(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant();
        $this->createOrderForUser($user, $variant, 'completed', 'paid');

        $this->actingAs($user)->post(route('reviews.store', $product->slug), [
            'rating' => 5,
            'comment' => 'Review muốn xóa.',
        ]);

        $review = Review::where('user_id', $user->id)->firstOrFail();

        $response = $this->actingAs($user)->delete(route('reviews.destroy', $review->id));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_stranger_cannot_delete_another_users_review(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant();
        $this->createOrderForUser($owner, $variant, 'completed', 'paid');

        $this->actingAs($owner)->post(route('reviews.store', $product->slug), [
            'rating' => 5,
            'comment' => 'Review của chủ sở hữu.',
        ]);

        $review = Review::where('user_id', $owner->id)->firstOrFail();

        $response = $this->actingAs($stranger)->delete(route('reviews.destroy', $review->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    public function test_mass_assignment_cannot_spoof_ids(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        [$productA, $variantA] = $this->createProductWithVariant('Bàn A');
        [$productB] = $this->createProductWithVariant('Ghế B');
        $this->createOrderForUser($user, $variantA, 'completed', 'paid');

        $response = $this->actingAs($user)->post(route('reviews.store', $productA->slug), [
            'rating' => 5,
            'comment' => 'Đánh giá bình thường.',
            'user_id' => $otherUser->id,
            'product_id' => $productB->id,
            'order_item_id' => 99999,
        ]);

        $response->assertSessionHasNoErrors();
        $review = Review::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals($user->id, $review->user_id);
        $this->assertEquals($productA->id, $review->product_id);
        $this->assertNotEquals(99999, $review->order_item_id);
    }

    public function test_inactive_product_rejects_new_review(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant('Sản phẩm ngừng bán', false);
        $this->createOrderForUser($user, $variant, 'completed', 'paid');

        $response = $this->actingAs($user)->post(route('reviews.store', $product->slug), [
            'rating' => 5,
            'comment' => 'Sản phẩm không active.',
        ]);

        $response->assertSessionHasErrors('product');
    }

    public function test_xss_comment_is_properly_escaped_on_product_detail(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant();
        $this->createOrderForUser($user, $variant, 'completed', 'paid');

        $this->actingAs($user)->post(route('reviews.store', $product->slug), [
            'rating' => 5,
            'comment' => '<script>alert("xss")</script> Tuyệt vời!',
        ]);

        $response = $this->get(route('products.show', $product->slug));
        $response->assertStatus(200);
        $response->assertDontSee('<script>alert("xss")</script>', false);
        $response->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false);
    }

    public function test_product_detail_displays_reviews_and_average_rating(): void
    {
        $user1 = User::factory()->create(['name' => 'Nguyễn Thị Hoa']);
        $user2 = User::factory()->create(['name' => 'Trần Văn Bình']);
        [$product, $variant] = $this->createProductWithVariant();

        $this->createOrderForUser($user1, $variant, 'completed', 'paid');
        $this->createOrderForUser($user2, $variant, 'completed', 'paid');

        $this->actingAs($user1)->post(route('reviews.store', $product->slug), [
            'rating' => 5,
            'comment' => 'Chất lượng quá đỉnh cao!',
        ]);

        $this->actingAs($user2)->post(route('reviews.store', $product->slug), [
            'rating' => 4,
            'comment' => 'Màu sắc rất sang trọng.',
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('4.5'); // Average of 5 and 4
        $response->assertSee('2 đánh giá');
        $response->assertSee('Nguyễn Thị Hoa');
        $response->assertSee('Trần Văn Bình');
        $response->assertSee('Đã mua hàng');
        $response->assertSee('Chất lượng quá đỉnh cao!');
        $response->assertSee('Màu sắc rất sang trọng.');
    }

    public function test_order_detail_shows_review_cta_when_completed_and_paid(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant();
        $order = $this->createOrderForUser($user, $variant, 'completed', 'paid');

        $response = $this->actingAs($user)->get(route('orders.show', $order->order_code));

        $response->assertStatus(200);
        $response->assertSee('Đánh giá sản phẩm');
        $response->assertSee(route('products.show', $product->slug) . '#reviews');
    }

    public function test_order_detail_hides_review_cta_when_not_completed(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant();
        $order = $this->createOrderForUser($user, $variant, 'shipping', 'paid');

        $response = $this->actingAs($user)->get(route('orders.show', $order->order_code));

        $response->assertStatus(200);
        $response->assertDontSee('Đánh giá sản phẩm');
    }
}
