<?php

namespace Tests\Feature\Ai;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Ai\AiAssistantService;
use App\Services\Ai\Providers\MockAiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiShoppingExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected Product $diningTable;
    protected ProductVariant $tableVariant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => 'customer']);

        $category = Category::create([
            'name' => 'Bàn ăn',
            'slug' => 'ban-an',
            'is_active' => true,
        ]);

        $this->diningTable = Product::create([
            'category_id' => $category->id,
            'name' => 'Bàn ăn Gỗ Sồi 6 ghế Mộc An',
            'slug' => 'ban-an-go-soi-6-ghe',
            'sku' => 'BA-SOI-06',
            'base_price' => 12000000,
            'is_active' => true,
        ]);

        $this->tableVariant = ProductVariant::create([
            'product_id' => $this->diningTable->id,
            'sku' => 'BA-SOI-06-V1',
            'price' => 12000000,
            'stock' => 5,
            'color' => 'Sồi tự nhiên',
            'size' => '160x80cm',
        ]);
    }

    public function test_budget_consulting_scenario_triggers_search_and_returns_cards(): void
    {
        // Configure MockAiProvider to trigger search_products tool call
        $mockProvider = new MockAiProvider();
        $mockProvider->queueToolCall('search_products', [
            'query' => 'bàn ăn',
            'max_price' => 15000000,
        ]);
        $mockProvider->queueResponse('Em đã tìm thấy mẫu Bàn ăn Gỗ Sồi 6 ghế Mộc An có giá 12.000.000₫ rất phù hợp với ngân sách dưới 15 triệu của quý khách!');

        $aiService = app(AiAssistantService::class);
        $aiService->setProvider($mockProvider);
        $this->app->instance(AiAssistantService::class, $aiService);

        $response = $this->actingAs($this->customer)->postJson(route('ai.chat'), [
            'message' => 'Gợi ý bàn ăn gỗ tự nhiên giá dưới 15 triệu giúp mình',
        ]);

        $response->assertOk();
        $this->assertNotEmpty($response->json('message.content'));
        $cards = $response->json('message.cards');
        $this->assertNotEmpty($cards);
        $this->assertEquals('product_list', $cards[0]['type']);
    }

    public function test_inventory_check_scenario_triggers_tool_and_returns_realtime_stock(): void
    {
        $mockProvider = new MockAiProvider();
        $mockProvider->queueToolCall('check_inventory', [
            'product_id' => $this->diningTable->id,
        ]);
        $mockProvider->queueResponse('Mẫu bàn ăn này hiện đang còn 5 bộ trong kho tại showroom Mộc An ạ!');

        $aiService = app(AiAssistantService::class);
        $aiService->setProvider($mockProvider);
        $this->app->instance(AiAssistantService::class, $aiService);

        $response = $this->actingAs($this->customer)->postJson(route('ai.chat'), [
            'message' => 'Mẫu bàn ăn 6 ghế này còn hàng không em?',
        ]);

        $response->assertOk();
        $cards = $response->json('message.cards');
        $this->assertNotEmpty($cards);
        $this->assertEquals('inventory_status', $cards[0]['type']);
    }

    public function test_order_status_lookup_scenario_returns_order_card_for_owner(): void
    {
        $order = Order::create([
            'user_id' => $this->customer->id,
            'order_code' => 'ORD-TEST-999',
            'total_price' => 12000000,
            'order_status' => Order::STATUS_SHIPPING,
            'payment_status' => Order::PAYMENT_PAID,
            'customer_name' => $this->customer->name,
            'customer_phone' => '0901234567',
            'customer_email' => $this->customer->email,
            'shipping_address' => 'Hà Nội',
        ]);

        $mockProvider = new MockAiProvider();
        $mockProvider->queueToolCall('get_order_status', [
            'order_code' => 'ORD-TEST-999',
        ]);
        $mockProvider->queueResponse('Đơn hàng ORD-TEST-999 của quý khách đang trong quá trình vận chuyển giao tới Hà Nội ạ.');

        $aiService = app(AiAssistantService::class);
        $aiService->setProvider($mockProvider);
        $this->app->instance(AiAssistantService::class, $aiService);

        $response = $this->actingAs($this->customer)->postJson(route('ai.chat'), [
            'message' => 'Kiểm tra đơn hàng ORD-TEST-999 của tôi',
        ]);

        $response->assertOk();
        $cards = $response->json('message.cards');
        $this->assertNotEmpty($cards);
        $this->assertEquals('order_status', $cards[0]['type']);
        $this->assertEquals('ORD-TEST-999', $cards[0]['data']['order_code']);
    }

    public function test_quick_add_to_cart_from_product_card_variant(): void
    {
        $response = $this->actingAs($this->customer)->postJson(route('cart.quick-add'), [
            'variant_id' => $this->tableVariant->id,
            'quantity' => 1,
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertGreaterThanOrEqual(1, (int) $response->json('cart_count'));
    }
}
