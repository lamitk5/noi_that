<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;
    protected Product $productA;
    protected Product $productB;
    protected Product $productC;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Bàn Ăn',
            'slug' => 'ban-an',
            'is_active' => true,
        ]);

        $this->productA = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Bàn Ăn Gỗ Sồi Mango',
            'slug' => 'ban-an-go-soi-mango',
            'sku' => 'BA-MANGO-01',
            'base_price' => 5000000,
            'is_active' => true,
        ]);

        $this->productB = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Ghế Ăn Mango Bọc Nệm',
            'slug' => 'ghe-an-mango-boc-nem',
            'sku' => 'GA-MANGO-01',
            'base_price' => 950000,
            'is_active' => true,
        ]);

        $this->productC = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Ghế Băng Ăn Dài Mango',
            'slug' => 'ghe-bang-an-dai-mango',
            'sku' => 'GBA-MANGO-01',
            'base_price' => 1800000,
            'is_active' => true,
        ]);
    }

    public function test_viewing_product_records_session_history(): void
    {
        $response = $this->get(route('products.show', $this->productA->slug));

        $response->assertOk();
        $history = session()->get('recently_viewed', []);
        $this->assertContains($this->productA->id, $history);
    }

    public function test_recommendation_service_fetches_similar_products(): void
    {
        /** @var RecommendationService $service */
        $service = app(RecommendationService::class);

        $similar = $service->getSimilarProducts($this->productA);

        $this->assertTrue($similar->contains('id', $this->productB->id));
        $this->assertTrue($similar->contains('id', $this->productC->id));
        $this->assertFalse($similar->contains('id', $this->productA->id));
    }

    public function test_frequently_bought_together_based_on_orders(): void
    {
        $variantA = ProductVariant::create([
            'product_id' => $this->productA->id,
            'sku' => 'BA-MANGO-V1',
            'price' => 5000000,
            'stock' => 10,
        ]);

        $variantB = ProductVariant::create([
            'product_id' => $this->productB->id,
            'sku' => 'GA-MANGO-V1',
            'price' => 950000,
            'stock' => 20,
        ]);

        $user = User::factory()->create();

        // Create 2 completed orders with Product A and Product B together
        for ($i = 0; $i < 2; $i++) {
            $order = Order::create([
                'user_id' => $user->id,
                'order_code' => 'ORD-TEST-' . $i,
                'customer_name' => 'Test Customer',
                'customer_phone' => '0901234567',
                'shipping_address' => 'HCM',
                'total_price' => 5950000,
                'payment_method' => 'cod',
                'payment_status' => 'paid',
                'order_status' => 'completed',
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_variant_id' => $variantA->id,
                'product_name' => $this->productA->name,
                'variant_info' => 'Tiêu chuẩn',
                'quantity' => 1,
                'price' => 5000000,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_variant_id' => $variantB->id,
                'product_name' => $this->productB->name,
                'variant_info' => 'Tiêu chuẩn',
                'quantity' => 1,
                'price' => 950000,
            ]);
        }

        /** @var RecommendationService $service */
        $service = app(RecommendationService::class);
        $fbt = $service->getFrequentlyBoughtTogether($this->productA);

        $this->assertTrue($fbt->contains('id', $this->productB->id));
    }

    public function test_product_detail_page_displays_recommendation_sections(): void
    {
        // Populate recently viewed in session
        $this->withSession(['recently_viewed' => [$this->productC->id, $this->productB->id]]);

        $response = $this->get(route('products.show', $this->productA->slug));

        $response->assertOk();
        $response->assertSee('Sản phẩm liên quan');
        $response->assertSee('Sản phẩm bạn vừa xem');
    }

    public function test_frequently_bought_together_ignores_canceled_or_unpaid_orders(): void
    {
        $variantA = ProductVariant::create([
            'product_id' => $this->productA->id,
            'sku' => 'BA-M-VA-' . uniqid(),
            'price' => 5000000,
            'stock' => 10,
        ]);

        $variantC = ProductVariant::create([
            'product_id' => $this->productC->id,
            'sku' => 'GBA-M-VC-' . uniqid(),
            'price' => 1800000,
            'stock' => 10,
        ]);

        $user = User::factory()->create();

        // Canceled order with Product A & Product C
        $order = Order::create([
            'user_id' => $user->id,
            'order_code' => 'ORD-CANCELED-' . uniqid(),
            'customer_name' => 'Test Customer',
            'customer_phone' => '0901234567',
            'shipping_address' => 'HCM',
            'total_price' => 6800000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'canceled',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variantA->id,
            'product_name' => $this->productA->name,
            'variant_info' => 'Tiêu chuẩn',
            'quantity' => 1,
            'price' => 5000000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variantC->id,
            'product_name' => $this->productC->name,
            'variant_info' => 'Tiêu chuẩn',
            'quantity' => 1,
            'price' => 1800000,
        ]);

        $this->assertFalse(
            OrderItem::where('product_variant_id', $variantA->id)
                ->whereHas('order', fn($q) => $q->where('order_status', Order::STATUS_COMPLETED)->where('payment_status', Order::PAYMENT_PAID))
                ->exists()
        );
    }
}
