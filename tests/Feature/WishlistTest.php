<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(string $name = 'Bàn Trà Mộc An'): Product
    {
        $category = Category::create([
            'name' => 'Phòng khách',
            'slug' => 'phong-khach-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => str($name)->slug()->value() . '-' . uniqid(),
            'sku' => 'PRD-' . strtoupper(uniqid()),
            'base_price' => 3500000,
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $product->sku . '-VAR',
            'color' => 'Gỗ sồi',
            'price' => 3500000,
            'stock' => 10,
            'is_active' => true,
        ]);

        return $product;
    }

    public function test_guest_cannot_access_wishlist_page(): void
    {
        $response = $this->get(route('wishlist.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_toggle_wishlist(): void
    {
        $product = $this->createProduct();

        $response = $this->post(route('wishlist.toggle', $product));
        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount('wishlists', 0);
    }

    public function test_user_can_add_product_to_wishlist(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct('Sofa Da Bò');

        $response = $this->actingAs($user)->post(route('wishlist.toggle', $product));

        $response->assertRedirect();
        $this->assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
        $this->assertTrue($product->isWishlistedBy($user));
    }

    public function test_user_can_remove_product_from_wishlist(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct('Ghế Thư Giãn');

        Wishlist::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($user)->post(route('wishlist.toggle', $product));

        $response->assertRedirect();
        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
        $this->assertFalse($product->isWishlistedBy($user));
    }

    public function test_wishlist_page_displays_saved_products_for_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $myProduct = $this->createProduct('Kệ Tivi Cao Cấp');
        $otherProduct = $this->createProduct('Tủ Quần Áo Đơn');

        Wishlist::create(['user_id' => $user->id, 'product_id' => $myProduct->id]);
        Wishlist::create(['user_id' => $otherUser->id, 'product_id' => $otherProduct->id]);

        $response = $this->actingAs($user)->get(route('wishlist.index'));

        $response->assertStatus(200);
        $response->assertSee('Kệ Tivi Cao Cấp');
        $response->assertDontSee('Tủ Quần Áo Đơn');
    }
}
