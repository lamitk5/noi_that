<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BestSellerTest extends TestCase
{
    use RefreshDatabase;

    private function createProductWithVariant(string $name, int $price = 1000000, bool $isActive = true): array
    {
        $category = Category::create([
            'name' => 'Phòng khách ' . uniqid(),
            'slug' => 'phong-khach-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name) . '-' . uniqid(),
            'sku' => 'SKU-' . strtoupper(uniqid()),
            'base_price' => $price,
            'is_active' => $isActive,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'color' => 'Mặc định',
            'price' => $price,
            'stock' => 50,
            'sku' => 'SKU-VAR-' . strtoupper(uniqid()),
        ]);

        return [$product, $variant];
    }

    private function createOrderWithItem(ProductVariant $variant, int $quantity, string $orderStatus = 'completed', string $paymentStatus = 'paid'): Order
    {
        $order = Order::create([
            'order_code' => 'ORD-' . strtoupper(uniqid()),
            'customer_name' => 'Khách Hàng',
            'customer_phone' => '0912345678',
            'customer_email' => 'khach@example.com',
            'shipping_address' => 'Hà Nội',
            'total_price' => $variant->price * $quantity,
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
            'quantity' => $quantity,
            'price' => $variant->price,
        ]);

        return $order;
    }

    public function test_product_with_higher_sold_quantity_ranks_above_lower(): void
    {
        [$productA, $variantA] = $this->createProductWithVariant('Bàn Ăn Bán Chạy A');
        [$productB, $variantB] = $this->createProductWithVariant('Ghế Bán Chạy B');

        $this->createOrderWithItem($variantA, 10);
        $this->createOrderWithItem($variantB, 5);

        $bestSellers = Product::bestSelling(4)->get();

        $this->assertCount(2, $bestSellers);
        $this->assertEquals($productA->id, $bestSellers->first()->id);
        $this->assertEquals($productB->id, $bestSellers->last()->id);
    }

    public function test_quantity_sold_determines_ranking_not_order_count(): void
    {
        [$productHighQty, $variantHighQty] = $this->createProductWithVariant('Sofa Bán Số Lượng Lớn');
        [$productManyOrders, $variantManyOrders] = $this->createProductWithVariant('Đèn Bán Nhiều Lượt Nhỏ');

        // 1 order with 15 items
        $this->createOrderWithItem($variantHighQty, 15);

        // 3 separate orders with 2 items each = 6 items total
        $this->createOrderWithItem($variantManyOrders, 2);
        $this->createOrderWithItem($variantManyOrders, 2);
        $this->createOrderWithItem($variantManyOrders, 2);

        $bestSellers = Product::bestSelling(4)->get();

        $this->assertEquals($productHighQty->id, $bestSellers->first()->id);
        $this->assertEquals($productManyOrders->id, $bestSellers->last()->id);
    }

    public function test_cancelled_orders_are_excluded_from_best_seller(): void
    {
        [$productCancelled, $variantCancelled] = $this->createProductWithVariant('Sản Phẩm Đơn Huỷ');
        [$productValid, $variantValid] = $this->createProductWithVariant('Sản Phẩm Đơn Thành Công');

        // Cancelled order of 20 items
        $this->createOrderWithItem($variantCancelled, 20, 'canceled', 'pending');

        // Valid order of 3 items
        $this->createOrderWithItem($variantValid, 3, 'completed', 'paid');

        $bestSellers = Product::bestSelling(4)->get();

        $this->assertCount(1, $bestSellers);
        $this->assertEquals($productValid->id, $bestSellers->first()->id);
    }

    public function test_failed_payment_orders_are_excluded_from_best_seller(): void
    {
        [$productFailed, $variantFailed] = $this->createProductWithVariant('Sản Phẩm Thanh Toán Lỗi');
        [$productValid, $variantValid] = $this->createProductWithVariant('Sản Phẩm Hợp Lệ');

        // Failed payment
        $this->createOrderWithItem($variantFailed, 30, 'completed', 'failed');
        $this->createOrderWithItem($variantValid, 2, 'completed', 'paid');

        $bestSellers = Product::bestSelling(4)->get();

        $this->assertCount(1, $bestSellers);
        $this->assertEquals($productValid->id, $bestSellers->first()->id);
    }

    public function test_inactive_products_are_excluded_from_best_seller(): void
    {
        [$inactiveProduct, $inactiveVariant] = $this->createProductWithVariant('Sản Phẩm Ngừng Bán', 1000000, false);
        [$activeProduct, $activeVariant] = $this->createProductWithVariant('Sản Phẩm Đang Bán', 1000000, true);

        $this->createOrderWithItem($inactiveVariant, 50);
        $this->createOrderWithItem($activeVariant, 5);

        $bestSellers = Product::bestSelling(4)->get();

        $this->assertCount(1, $bestSellers);
        $this->assertEquals($activeProduct->id, $bestSellers->first()->id);
    }

    public function test_best_seller_respects_limit(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            [$prod, $variant] = $this->createProductWithVariant("Sản Phẩm {$i}");
            $this->createOrderWithItem($variant, $i * 2);
        }

        $bestSellers = Product::bestSelling(3)->get();

        $this->assertCount(3, $bestSellers);
    }

    public function test_home_page_displays_best_seller_section_when_sales_exist(): void
    {
        [$product, $variant] = $this->createProductWithVariant('Sofa Hoàng Gia Mộc An');
        $this->createOrderWithItem($variant, 12);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('Sản phẩm bán chạy');
        $response->assertSee('Sofa Hoàng Gia Mộc An');
    }

    public function test_home_page_hides_best_seller_section_when_no_sales_exist(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertDontSee('Sản phẩm bán chạy');
    }
}
