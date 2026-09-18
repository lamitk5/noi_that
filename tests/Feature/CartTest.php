<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private function createProductWithVariant(int $price = 1000000, int $stock = 10, bool $isActive = true, array $variantAttributes = []): array
    {
        $category = Category::create([
            'name' => 'Phòng ăn',
            'slug' => 'phong-an-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bàn Ăn Mộc An ' . uniqid(),
            'slug' => 'ban-an-' . uniqid(),
            'sku' => 'BA-' . strtoupper(uniqid()),
            'base_price' => $price,
            'is_active' => $isActive,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'https://example.com/ban-an.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $variant = ProductVariant::create(array_merge([
            'product_id' => $product->id,
            'color' => 'Gỗ Sồi',
            'size' => '160x80cm',
            'material' => 'Gỗ sồi tự nhiên',
            'price' => $price,
            'stock' => $stock,
            'sku' => 'BA-SOI-' . strtoupper(uniqid()),
        ], $variantAttributes));

        return [$product, $variant];
    }

    public function test_cart_page_is_accessible_by_guest(): void
    {
        $response = $this->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertSee('Giỏ hàng');
    }

    public function test_cart_initially_shows_empty_state(): void
    {
        $response = $this->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertSee('Giỏ hàng của bạn đang trống');
        $response->assertSee(route('products.index'));
    }

    public function test_user_can_add_in_stock_variant_to_cart(): void
    {
        [$product, $variant] = $this->createProductWithVariant(2500000, 8);

        $response = $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('status', 'Đã thêm sản phẩm vào giỏ hàng.');

        $cartResponse = $this->get(route('cart.index'));
        $cartResponse->assertStatus(200);
        $cartResponse->assertSee($product->name);
        $cartResponse->assertSee('Gỗ Sồi');
        $cartResponse->assertSee('160x80cm');
    }

    public function test_cart_displays_correct_unit_price_line_total_and_subtotal(): void
    {
        [$product, $variant] = $this->createProductWithVariant(3000000, 10);

        $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response = $this->get(route('cart.index'));

        $response->assertStatus(200);
        // Unit price: 3.000.000₫
        $response->assertSee('3.000.000₫');
        // Line total and subtotal: 6.000.000₫
        $response->assertSee('6.000.000₫');
    }

    public function test_adding_same_variant_accumulates_quantity(): void
    {
        [$product, $variant] = $this->createProductWithVariant(1500000, 10);

        $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 3,
        ]);

        $cart = session('cart');
        $this->assertEquals(5, $cart[$variant->id]);

        $response = $this->get(route('cart.index'));
        $response->assertStatus(200);
        $response->assertSee('7.500.000₫'); // 5 * 1.500.000
    }

    public function test_adding_more_than_available_stock_is_rejected(): void
    {
        [$product, $variant] = $this->createProductWithVariant(1000000, 4);

        $response = $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 5,
        ]);

        $response->assertSessionHasErrors(['quantity']);
        $this->assertEmpty(session('cart', []));
    }

    public function test_accumulating_quantity_beyond_stock_is_rejected(): void
    {
        [$product, $variant] = $this->createProductWithVariant(1000000, 4);

        $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 3,
        ]);

        // Trying to add 2 more when only 1 remaining in stock
        $response = $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response->assertSessionHasErrors(['quantity']);
        $this->assertEquals(3, session('cart')[$variant->id]);
    }

    public function test_adding_out_of_stock_variant_is_rejected(): void
    {
        [$product, $variant] = $this->createProductWithVariant(1000000, 0);

        $response = $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors(['quantity']);
        $this->assertEmpty(session('cart', []));
    }

    public function test_adding_variant_of_inactive_product_is_rejected(): void
    {
        [$product, $variant] = $this->createProductWithVariant(1000000, 10, false);

        $response = $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors();
        $this->assertEmpty(session('cart', []));
    }

    public function test_client_cannot_inject_fake_price(): void
    {
        [$product, $variant] = $this->createProductWithVariant(5000000, 10);

        // Client attempts to send price=1000
        $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
            'price' => 1000,
        ]);

        $response = $this->get(route('cart.index'));
        $response->assertStatus(200);
        $response->assertSee('5.000.000₫');
        $response->assertDontSee('1.000₫');
    }

    public function test_user_can_update_cart_quantity(): void
    {
        [$product, $variant] = $this->createProductWithVariant(2000000, 10);

        $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response = $this->patch(route('cart.update', $variant->id), [
            'quantity' => 4,
        ]);

        $response->assertRedirect(route('cart.index'));
        $this->assertEquals(4, session('cart')[$variant->id]);

        $cartResponse = $this->get(route('cart.index'));
        $cartResponse->assertSee('8.000.000₫');
    }

    public function test_updating_cart_quantity_beyond_stock_is_rejected(): void
    {
        [$product, $variant] = $this->createProductWithVariant(2000000, 5);

        $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response = $this->patch(route('cart.update', $variant->id), [
            'quantity' => 6,
        ]);

        $response->assertSessionHasErrors(['quantity']);
        $this->assertEquals(2, session('cart')[$variant->id]);
    }

    public function test_user_can_remove_item_from_cart(): void
    {
        [$product, $variant] = $this->createProductWithVariant(2000000, 10);

        $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response = $this->delete(route('cart.destroy', $variant->id));

        $response->assertRedirect(route('cart.index'));
        $this->assertEmpty(session('cart', []));
    }

    public function test_user_can_clear_cart(): void
    {
        [$prod1, $var1] = $this->createProductWithVariant(1000000, 10);
        [$prod2, $var2] = $this->createProductWithVariant(2000000, 10);

        $this->post(route('cart.store'), ['variant_id' => $var1->id, 'quantity' => 1]);
        $this->post(route('cart.store'), ['variant_id' => $var2->id, 'quantity' => 2]);

        $this->assertCount(2, session('cart'));

        $response = $this->delete(route('cart.clear'));

        $response->assertRedirect(route('cart.index'));
        $this->assertEmpty(session('cart', []));
    }

    public function test_cart_actions_do_not_decrement_database_inventory(): void
    {
        [$product, $variant] = $this->createProductWithVariant(2000000, 10);

        $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 4,
        ]);

        $this->assertEquals(10, $variant->fresh()->stock);

        $this->patch(route('cart.update', $variant->id), [
            'quantity' => 6,
        ]);

        $this->assertEquals(10, $variant->fresh()->stock);

        $this->delete(route('cart.destroy', $variant->id));

        $this->assertEquals(10, $variant->fresh()->stock);
    }

    public function test_header_displays_correct_total_cart_quantity_badge(): void
    {
        [$prod1, $var1] = $this->createProductWithVariant(1000000, 10);
        [$prod2, $var2] = $this->createProductWithVariant(2000000, 10);

        $this->post(route('cart.store'), ['variant_id' => $var1->id, 'quantity' => 2]);
        $this->post(route('cart.store'), ['variant_id' => $var2->id, 'quantity' => 3]);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        // Total badge should be 5 (2 + 3)
        $response->assertSee('>5<', false);
    }

    public function test_cart_uses_semantic_theme_tokens(): void
    {
        [$product, $variant] = $this->createProductWithVariant(1000000, 10);
        $this->post(route('cart.store'), ['variant_id' => $variant->id, 'quantity' => 1]);

        $response = $this->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertSee('bg-page');
        $response->assertSee('bg-surface');
        $response->assertSee('text-heading');
        $response->assertSee('border-ui-border');
    }
}
