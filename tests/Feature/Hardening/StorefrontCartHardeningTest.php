<?php

namespace Tests\Feature\Hardening;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\UserCartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontCartHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function createProductWithVariant(int $price = 1000000, int $stock = 10, bool $isActive = true, array $variantAttributes = []): array
    {
        $category = Category::create([
            'name' => 'Phòng khách',
            'slug' => 'phong-khach-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Sofa Gỗ Hiện Đại ' . uniqid(),
            'slug' => 'sofa-go-' . uniqid(),
            'sku' => 'SF-' . strtoupper(uniqid()),
            'base_price' => $price,
            'is_active' => $isActive,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'https://example.com/sofa.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $variant = ProductVariant::create(array_merge([
            'product_id' => $product->id,
            'color' => 'Gỗ Sồi',
            'size' => 'Tiêu chuẩn',
            'material' => 'Gỗ sồi tự nhiên',
            'price' => $price,
            'stock' => $stock,
            'sku' => 'SF-SOI-' . strtoupper(uniqid()),
        ], $variantAttributes));

        return [$product, $variant];
    }

    public function test_inactive_product_cannot_be_added_to_cart(): void
    {
        [$product, $variant] = $this->createProductWithVariant(2000000, 5, false);

        $response = $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors(['variant_id']);
        $this->assertEmpty(session('cart', []));

        // Also test quickAdd JSON endpoint
        $jsonResponse = $this->postJson(route('cart.quick-add'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $jsonResponse->assertStatus(422);
    }

    public function test_adding_quantity_exceeding_variant_stock_returns_localized_error(): void
    {
        [$product, $variant] = $this->createProductWithVariant(1500000, 3);

        $response = $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 5,
        ]);

        $response->assertSessionHasErrors(['quantity']);
        $errors = session('errors')->get('quantity');
        $this->assertStringContainsString('vượt quá tồn kho', $errors[0]);

        // Quick add JSON endpoint
        $jsonResponse = $this->postJson(route('cart.quick-add'), [
            'variant_id' => $variant->id,
            'quantity' => 10,
        ]);

        $jsonResponse->assertStatus(422);
        $jsonResponse->assertJsonValidationErrors(['quantity']);
    }

    public function test_cart_page_recalculates_and_flags_items_when_stock_drops(): void
    {
        [$product, $variant] = $this->createProductWithVariant(1000000, 10);

        // Add 5 items to cart
        $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 5,
        ]);

        // Stock in database drops to 2 (e.g. another user bought 8 items)
        $variant->update(['stock' => 2]);

        $response = $this->get(route('cart.index'));
        $response->assertStatus(200);

        // Warning message displayed
        $response->assertSee('chỉ còn 2 sản phẩm trong kho');

        // Cart session automatically adjusted to 2
        $this->assertEquals(2, session('cart')[$variant->id]);
    }

    public function test_cart_page_removes_and_flags_items_when_variant_becomes_out_of_stock(): void
    {
        [$product, $variant] = $this->createProductWithVariant(1000000, 5);

        $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        // Stock drops to 0
        $variant->update(['stock' => 0]);

        $response = $this->get(route('cart.index'));
        $response->assertStatus(200);
        $response->assertSee('hiện đã hết hàng');
        $this->assertArrayNotHasKey($variant->id, session('cart', []));
    }

    public function test_pdp_disables_cta_and_shows_out_of_stock_badge_when_stock_is_zero(): void
    {
        [$product, $variant] = $this->createProductWithVariant(2000000, 0);

        $response = $this->get(route('products.show', $product->slug));
        $response->assertStatus(200);
        $response->assertSee('Hết hàng');
        $response->assertSee(':disabled="isOutOfStock || !selectedVariantId"', false);
    }

    public function test_views_do_not_contain_dead_hash_links(): void
    {
        [$product, $variant] = $this->createProductWithVariant(2000000, 5);

        $cartResponse = $this->get(route('cart.index'));
        $cartResponse->assertStatus(200);
        $cartContent = $cartResponse->getContent();
        $this->assertStringNotContainsString('href="#"', $cartContent);

        $pdpResponse = $this->get(route('products.show', $product->slug));
        $pdpResponse->assertStatus(200);
        $pdpContent = $pdpResponse->getContent();
        $this->assertStringNotContainsString('href="#"', $pdpContent);
    }
}
