<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => 'customer']);

        $category = Category::create([
            'name' => 'Phòng Khách',
            'slug' => 'phong-khach',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Sofa Băng Mộc An',
            'slug' => 'sofa-bang-moc-an',
            'sku' => 'SOFA-MA-01',
            'base_price' => 8500000,
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_wishlist_page(): void
    {
        $response = $this->get(route('account.wishlist'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_toggle_wishlist(): void
    {
        $response = $this->post(route('wishlist.toggle', $this->product->slug));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseEmpty('wishlists');
    }

    public function test_customer_can_add_product_to_wishlist(): void
    {
        $response = $this->actingAs($this->customer)
            ->post(route('wishlist.toggle', $this->product->slug));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $this->customer->id,
            'product_id' => $this->product->id,
        ]);
    }

    public function test_customer_can_toggle_remove_product_from_wishlist(): void
    {
        Wishlist::create([
            'user_id' => $this->customer->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->post(route('wishlist.toggle', $this->product->slug));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $this->customer->id,
            'product_id' => $this->product->id,
        ]);
    }

    public function test_customer_can_view_their_wishlist_page(): void
    {
        Wishlist::create([
            'user_id' => $this->customer->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->get(route('account.wishlist'));

        $response->assertOk();
        $response->assertSee('Sản phẩm yêu thích');
        $response->assertSee('Sofa Băng Mộc An');
    }

    public function test_customer_can_delete_item_from_wishlist_page(): void
    {
        Wishlist::create([
            'user_id' => $this->customer->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->delete(route('wishlist.destroy', $this->product->slug));

        $response->assertRedirect(route('account.wishlist'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $this->customer->id,
            'product_id' => $this->product->id,
        ]);
    }
}
