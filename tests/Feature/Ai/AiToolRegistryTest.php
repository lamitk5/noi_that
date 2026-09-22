<?php

namespace Tests\Feature\Ai;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Voucher;
use App\Services\Ai\AiToolRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiToolRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected AiToolRegistry $toolRegistry;
    protected User $customer;
    protected Product $sofa;
    protected ProductVariant $sofaVariant;
    protected Product $table;
    protected ProductVariant $tableVariant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->toolRegistry = app(AiToolRegistry::class);

        $this->customer = User::factory()->create(['role' => 'customer']);

        $livingRoom = Category::create([
            'name' => 'Phòng khách',
            'slug' => 'phong-khach',
            'is_active' => true,
        ]);

        $diningRoom = Category::create([
            'name' => 'Phòng ăn',
            'slug' => 'phong-an',
            'is_active' => true,
        ]);

        $this->sofa = Product::create([
            'category_id' => $livingRoom->id,
            'name' => 'Sofa Gỗ Sồi Bắc Âu',
            'slug' => 'sofa-go-soi-bac-au',
            'sku' => 'SF-SOI-01',
            'base_price' => 12500000,
            'is_active' => true,
            'material' => 'Gỗ sồi tự nhiên',
            'dimensions' => '200x85x75cm',
            'warranty_months' => 24,
        ]);

        $this->sofaVariant = ProductVariant::create([
            'product_id' => $this->sofa->id,
            'sku' => 'SF-SOI-01-V1',
            'price' => 12500000,
            'stock' => 8,
            'color' => 'Màu sồi mộc',
            'size' => '200x85x75cm',
            'material' => 'Gỗ sồi tự nhiên',
        ]);

        $this->table = Product::create([
            'category_id' => $diningRoom->id,
            'name' => 'Bàn Ăn Gỗ Óc Chó Zen',
            'slug' => 'ban-an-go-oc-cho-zen',
            'sku' => 'BA-OC-01',
            'base_price' => 18000000,
            'is_active' => true,
            'material' => 'Gỗ óc chó Bắc Mỹ',
            'dimensions' => '160x80x75cm',
            'warranty_months' => 36,
        ]);

        $this->tableVariant = ProductVariant::create([
            'product_id' => $this->table->id,
            'sku' => 'BA-OC-01-V1',
            'price' => 18000000,
            'stock' => 0, // Out of stock
            'color' => 'Màu nâu trầm',
        ]);
    }

    public function test_search_products_filters_by_query_and_budget(): void
    {
        $res = $this->toolRegistry->executeTool('search_products', [
            'query' => 'sofa',
            'max_price' => 15000000,
        ], ['user' => $this->customer]);

        $this->assertTrue($res['success']);
        $this->assertNotEmpty($res['card']);
        $this->assertEquals('product_list', $res['card']['type']);
        $this->assertCount(1, $res['card']['data']);
        $this->assertEquals('Sofa Gỗ Sồi Bắc Âu', $res['card']['data'][0]['name']);
    }

    public function test_get_product_detail_returns_rich_information(): void
    {
        $res = $this->toolRegistry->executeTool('get_product_detail', [
            'product_id' => $this->sofa->id,
        ], ['user' => $this->customer]);

        $this->assertTrue($res['success']);
        $this->assertEquals('product_detail', $res['card']['type']);
        $this->assertEquals('Sofa Gỗ Sồi Bắc Âu', $res['card']['data']['name']);
        $this->assertEquals('Gỗ sồi tự nhiên', $res['card']['data']['material']);
    }

    public function test_check_inventory_returns_accurate_stock_status(): void
    {
        // Check in-stock product
        $inStockRes = $this->toolRegistry->executeTool('check_inventory', [
            'product_id' => $this->sofa->id,
        ], ['user' => $this->customer]);

        $this->assertTrue($inStockRes['success']);
        $this->assertEquals('in_stock', $inStockRes['card']['data']['stock_status']);
        $this->assertEquals(8, $inStockRes['card']['data']['total_stock']);

        // Check out-of-stock product
        $outOfStockRes = $this->toolRegistry->executeTool('check_inventory', [
            'product_id' => $this->table->id,
        ], ['user' => $this->customer]);

        $this->assertTrue($outOfStockRes['success']);
        $this->assertEquals('out_of_stock', $outOfStockRes['card']['data']['stock_status']);
        $this->assertEquals(0, $outOfStockRes['card']['data']['total_stock']);
    }

    public function test_compare_products_returns_comparison_card(): void
    {
        $res = $this->toolRegistry->executeTool('compare_products', [
            'product_ids' => [$this->sofa->id, $this->table->id],
        ], ['user' => $this->customer]);

        $this->assertTrue($res['success']);
        $this->assertEquals('product_comparison', $res['card']['type']);
        $this->assertCount(2, $res['card']['data']);
    }

    public function test_get_active_vouchers_returns_valid_coupons(): void
    {
        Voucher::create([
            'code' => 'TET2026',
            'name' => 'Giảm 10% Tết',
            'type' => 'percent',
            'value' => 10,
            'min_order_amount' => 5000000,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ]);

        $res = $this->toolRegistry->executeTool('get_active_vouchers', [], ['user' => $this->customer]);

        $this->assertTrue($res['success']);
        $this->assertEquals('voucher_list', $res['card']['type']);
        $this->assertNotEmpty($res['card']['data']);
        $this->assertEquals('TET2026', $res['card']['data'][0]['code']);
    }

    public function test_get_order_status_enforces_strict_ownership(): void
    {
        $order = Order::create([
            'user_id' => $this->customer->id,
            'order_code' => 'ORD-AI-1001',
            'total_price' => 12500000,
            'order_status' => Order::STATUS_CONFIRMED,
            'payment_status' => Order::PAYMENT_PAID,
            'customer_name' => 'Nguyễn Văn A',
            'customer_phone' => '0901234567',
            'customer_email' => 'a@example.com',
            'shipping_address' => 'Hà Nội',
        ]);

        // 1. Guest request must be rejected
        $guestRes = $this->toolRegistry->executeTool('get_order_status', [
            'order_code' => 'ORD-AI-1001',
        ], ['user' => null]);
        $this->assertFalse($guestRes['success']);
        $this->assertEquals('AUTH_REQUIRED', $guestRes['error_code']);

        // 2. Stranger request must be rejected
        $stranger = User::factory()->create(['role' => 'customer']);
        $strangerRes = $this->toolRegistry->executeTool('get_order_status', [
            'order_code' => 'ORD-AI-1001',
        ], ['user' => $stranger]);
        $this->assertFalse($strangerRes['success']);
        $this->assertEquals('FORBIDDEN_ORDER', $strangerRes['error_code']);

        // 3. Legit owner request succeeds and returns order card
        $ownerRes = $this->toolRegistry->executeTool('get_order_status', [
            'order_code' => 'ORD-AI-1001',
        ], ['user' => $this->customer]);
        $this->assertTrue($ownerRes['success']);
        $this->assertEquals('order_status', $ownerRes['card']['type']);
        $this->assertEquals('ORD-AI-1001', $ownerRes['card']['data']['order_code']);
    }

    public function test_add_to_cart_tool_adds_item(): void
    {
        $res = $this->toolRegistry->executeTool('add_to_cart', [
            'variant_id' => $this->sofaVariant->id,
            'quantity' => 1,
        ], ['user' => $this->customer]);

        $this->assertTrue($res['success']);
        $this->assertEquals('cart_action_success', $res['card']['type']);
        $this->assertEquals(1, $res['card']['data']['quantity']);
    }

    public function test_create_support_ticket_tool(): void
    {
        $res = $this->toolRegistry->executeTool('create_support_ticket', [
            'subject' => 'Tư vấn đặt làm kích thước sofa riêng',
            'message' => 'Tôi muốn đặt kích thước 2m4 x 90cm gỗ sồi.',
        ], ['user' => $this->customer]);

        $this->assertTrue($res['success']);
        $this->assertNotEmpty($res['data']['ticket_code']);
        $this->assertEquals('ticket_created', $res['card']['type']);
    }

    public function test_search_products_includes_materials_and_colors(): void
    {
        $res = $this->toolRegistry->executeTool('search_products', [
            'query' => 'sofa',
        ], ['user' => $this->customer]);

        $this->assertTrue($res['success']);
        $this->assertNotEmpty($res['card']['data']);
        $card = $res['card']['data'][0];

        $this->assertArrayHasKey('materials', $card);
        $this->assertArrayHasKey('colors', $card);
        $this->assertStringContainsString('Gỗ sồi tự nhiên', $card['materials']);
        $this->assertStringContainsString('Màu sồi mộc', $card['colors']);
    }

    public function test_execute_handles_exceptions_gracefully(): void
    {
        // Testing unknown tool or unexpected condition never crashes the application
        $res = $this->toolRegistry->execute('non_existent_tool', [], ['user' => $this->customer]);
        $this->assertStringContainsString('không được hỗ trợ', $res['text']);
    }
}
