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
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    private function createProductWithVariant(): array
    {
        $category = Category::create([
            'name' => 'Phòng khách',
            'slug' => 'phong-khach-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bàn Gỗ Sồi Rustic',
            'slug' => 'ban-go-soi-rustic-' . uniqid(),
            'sku' => 'BGSR-' . strtoupper(uniqid()),
            'base_price' => 5000000,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'BGSR-NAT-01',
            'color' => 'Tự nhiên',
            'price' => 5000000,
            'stock' => 10,
            'is_active' => true,
        ]);

        return [$product, $variant];
    }

    public function test_guest_cannot_submit_review(): void
    {
        [$product] = $this->createProductWithVariant();

        $response = $this->post(route('products.reviews.store', $product->slug), [
            'rating' => 5,
            'comment' => 'Sản phẩm rất đẹp và chắc chắn.',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_user_without_completed_order_cannot_submit_review(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant();

        // Order is still pending, not completed
        $order = Order::create([
            'user_id' => $user->id,
            'order_code' => 'ORD-' . strtoupper(Str::random(8)),
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '0901234567',
            'shipping_address' => '123 Đường ABC, Hà Nội',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'total_price' => 5000000,
            'shipping_fee' => 0,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_info' => 'Tự nhiên',
            'price' => 5000000,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)->post(route('products.reviews.store', $product->slug), [
            'rating' => 5,
            'comment' => 'Sản phẩm rất đẹp nhưng đơn chưa giao.',
        ]);

        $response->assertSessionHasErrors('review');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_user_with_completed_order_can_submit_review(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->createProductWithVariant();

        $order = Order::create([
            'user_id' => $user->id,
            'order_code' => 'ORD-' . strtoupper(Str::random(8)),
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '0901234567',
            'shipping_address' => '123 Đường ABC, Hà Nội',
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'order_status' => 'completed',
            'total_price' => 5000000,
            'shipping_fee' => 0,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_info' => 'Tự nhiên',
            'price' => 5000000,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)->post(route('products.reviews.store', $product->slug), [
            'rating' => 5,
            'comment' => 'Bàn rất chắc chắn, màu vân gỗ tự nhiên rất ưng ý!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => 5,
            'comment' => 'Bàn rất chắc chắn, màu vân gỗ tự nhiên rất ưng ý!',
            'is_approved' => true,
        ]);
    }

    public function test_review_is_displayed_on_product_page_with_rating(): void
    {
        $user = User::factory()->create(['name' => 'Nguyễn Văn An']);
        [$product] = $this->createProductWithVariant();

        Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 4,
            'comment' => 'Sản phẩm hoàn thiện tỉ mỉ, đóng gói cẩn thận.',
            'is_approved' => true,
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('Nguyễn Văn An');
        $response->assertSee('Sản phẩm hoàn thiện tỉ mỉ, đóng gói cẩn thận.');
        $response->assertSee('4.0');
        $response->assertSee('(1 đánh giá)');
    }

    public function test_review_validation_requires_valid_rating_and_comment(): void
    {
        $user = User::factory()->create();
        [$product] = $this->createProductWithVariant();

        $response = $this->actingAs($user)->post(route('products.reviews.store', $product->slug), [
            'rating' => 6,
            'comment' => 'hi',
        ]);

        $response->assertSessionHasErrors(['rating', 'comment']);
        $this->assertDatabaseCount('reviews', 0);
    }
}
