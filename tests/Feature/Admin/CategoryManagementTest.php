<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_categories(): void
    {
        $response = $this->get(route('admin.categories.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_admin_categories(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $response = $this->actingAs($customer)->get(route('admin.categories.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_categories_list(): void
    {
        $admin = User::factory()->admin()->create();
        $cat1 = Category::create(['name' => 'Phòng Khách VIP', 'slug' => 'phong-khach-vip', 'is_active' => true]);
        $cat2 = Category::create(['name' => 'Phòng Ngủ VIP', 'slug' => 'phong-ngu-vip', 'is_active' => false]);

        $response = $this->actingAs($admin)->get(route('admin.categories.index'));

        $response->assertStatus(200);
        $response->assertSee('Quản lý Danh mục');
        $response->assertSee('Phòng Khách VIP');
        $response->assertSee('Phòng Ngủ VIP');
        $response->assertSee('Hiển thị');
        $response->assertSee('Đang ẩn');
    }

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Nội Thất Bếp Hiện Đại',
            'description' => 'Các sản phẩm tủ bếp, bàn ăn cao cấp.',
            'is_active' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Nội Thất Bếp Hiện Đại',
            'slug' => 'noi-that-bep-hien-dai',
            'is_active' => true,
        ]);
    }

    public function test_duplicate_category_slug_is_handled(): void
    {
        $admin = User::factory()->admin()->create();

        Category::create([
            'name' => 'Bàn Làm Việc',
            'slug' => 'ban-lam-viec',
            'is_active' => true,
        ]);

        // Creating another category with same name generates deterministic unique slug
        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Bàn Làm Việc',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('categories', [
            'name' => 'Bàn Làm Việc',
            'slug' => 'ban-lam-viec-1',
        ]);
    }

    public function test_admin_can_edit_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'name' => 'Đèn Chiếu Sáng',
            'slug' => 'den-chieu-sang',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.categories.update', $category->id), [
            'name' => 'Đèn Trang Trí Cao Cấp',
            'slug' => 'den-chieu-sang',
            'is_active' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('Đèn Trang Trí Cao Cấp', $category->fresh()->name);
        $this->assertEquals('den-chieu-sang', $category->fresh()->slug); // Slug preserved
    }

    public function test_admin_can_toggle_category_active_state(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'name' => 'Kệ Sách',
            'slug' => 'ke-sach',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->patch(route('admin.categories.update', $category->id), [
            'name' => 'Kệ Sách',
            'is_active' => 0,
        ]);

        $this->assertFalse($category->fresh()->is_active);
    }

    public function test_cannot_delete_category_with_products(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'name' => 'Sofa Phòng Khách',
            'slug' => 'sofa-phong-khach',
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Sofa Băng Đôi',
            'slug' => 'sofa-bang-doi',
            'sku' => 'SKU-SOFA-01',
            'base_price' => 5000000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.categories.destroy', $category->id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_empty_category_can_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'name' => 'Danh Mục Trống',
            'slug' => 'danh-muc-trong',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.categories.destroy', $category->id));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_storefront_category_filter_still_works(): void
    {
        $cat = Category::create(['name' => 'Bàn Trà Mới', 'slug' => 'ban-tra-moi', 'is_active' => true]);
        $prod = Product::create([
            'category_id' => $cat->id,
            'name' => 'Bàn Trà Nhật',
            'slug' => 'ban-tra-nhat',
            'sku' => 'SKU-BTN-01',
            'base_price' => 2000000,
            'is_active' => true,
        ]);

        $response = $this->get(route('products.index', ['category' => 'ban-tra-moi']));

        $response->assertStatus(200);
        $response->assertSee('Bàn Trà Nhật');
    }
}
