<?php

namespace Tests\Feature\Hardening;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabasePerformanceIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_performance_composite_indexes_exist(): void
    {
        // 1. products indexes
        $this->assertTrue(
            Schema::hasIndex('products', ['is_active', 'category_id', 'created_at']) ||
            Schema::hasIndex('products', 'products_is_active_category_id_created_at_index'),
            'Index on products [is_active, category_id, created_at] is missing'
        );

        $this->assertTrue(
            Schema::hasIndex('products', ['is_active', 'base_price']) ||
            Schema::hasIndex('products', 'products_is_active_base_price_index'),
            'Index on products [is_active, base_price] is missing'
        );

        // 2. product_variants index
        $this->assertTrue(
            Schema::hasIndex('product_variants', ['product_id', 'stock']) ||
            Schema::hasIndex('product_variants', 'product_variants_product_id_stock_index'),
            'Index on product_variants [product_id, stock] is missing'
        );

        // 3. orders indexes
        $this->assertTrue(
            Schema::hasIndex('orders', ['user_id', 'created_at']) ||
            Schema::hasIndex('orders', 'orders_user_id_created_at_index'),
            'Index on orders [user_id, created_at] is missing'
        );

        $this->assertTrue(
            Schema::hasIndex('orders', ['order_status', 'payment_status']) ||
            Schema::hasIndex('orders', 'orders_order_status_payment_status_index'),
            'Index on orders [order_status, payment_status] is missing'
        );

        // 4. user_events indexes
        $this->assertTrue(
            Schema::hasIndex('user_events', ['event_type', 'created_at']) ||
            Schema::hasIndex('user_events', 'user_events_event_type_created_at_index'),
            'Index on user_events [event_type, created_at] is missing'
        );

        $this->assertTrue(
            Schema::hasIndex('user_events', ['user_id', 'created_at']) ||
            Schema::hasIndex('user_events', 'user_events_user_id_created_at_index'),
            'Index on user_events [user_id, created_at] is missing'
        );

        // 5. reviews index
        $this->assertTrue(
            Schema::hasIndex('reviews', ['product_id', 'rating']) ||
            Schema::hasIndex('reviews', 'reviews_product_id_rating_index'),
            'Index on reviews [product_id, rating] is missing'
        );
    }

    public function test_catalog_and_homepage_queries_are_bounded_without_n_plus_one(): void
    {
        $category = Category::create(['name' => 'Phòng Khách', 'slug' => 'phong-khach', 'is_active' => true]);

        // Seed 15 products with images and variants
        for ($i = 1; $i <= 15; $i++) {
            $product = Product::create([
                'category_id' => $category->id,
                'name' => "Sản Phẩm Mẫu {$i}",
                'slug' => "san-pham-mau-{$i}",
                'sku' => "SP-{$i}",
                'base_price' => 1000000 * $i,
                'is_active' => true,
            ]);

            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => "https://example.com/p{$i}.jpg",
                'is_primary' => true,
                'sort_order' => 0,
            ]);

            ProductVariant::create([
                'product_id' => $product->id,
                'color' => 'Gỗ',
                'size' => 'M',
                'price' => 1000000 * $i,
                'stock' => 5,
                'sku' => "VAR-{$i}",
            ]);
        }

        // Test catalog page query count
        DB::flushQueryLog();
        DB::enableQueryLog();

        $catalogResponse = $this->get(route('products.index'));
        $catalogResponse->assertStatus(200);

        $catalogQueries = count(DB::getQueryLog());
        $this->assertLessThan(10, $catalogQueries, "Catalog query count ($catalogQueries) exceeded 10 queries");

        // Test homepage query count
        DB::flushQueryLog();
        DB::enableQueryLog();

        $homeResponse = $this->get(route('home'));
        $homeResponse->assertStatus(200);

        $homeQueries = count(DB::getQueryLog());
        $this->assertLessThan(15, $homeQueries, "Homepage query count ($homeQueries) exceeded 15 queries");
    }
}
