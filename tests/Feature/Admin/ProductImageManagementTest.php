<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_first_image_and_it_becomes_primary(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Tủ', 'slug' => 'tu', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Tủ Bếp Acrylic',
            'slug' => 'tu-bep-acrylic',
            'sku' => 'TB-ACR',
            'base_price' => 25000000,
            'is_active' => true,
        ]);

        $jpegBytes = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');
        $file = UploadedFile::fake()->createWithContent('kitchen.jpg', $jpegBytes);

        $response = $this->actingAs($admin)->post(route('admin.products.images.store', $product), [
            'image' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertCount(1, $product->images);

        $image = $product->images()->first();
        $this->assertTrue($image->is_primary);
        Storage::disk('public')->assertExists($image->image_path);
    }

    public function test_admin_can_set_image_as_primary(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Bàn', 'slug' => 'ban', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bàn Làm Việc',
            'slug' => 'ban-lam-viec',
            'sku' => 'BLV-01',
            'base_price' => 2000000,
            'is_active' => true,
        ]);

        $img1 = ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/sample1.jpg',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $img2 = ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/sample2.jpg',
            'is_primary' => false,
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.products.images.primary', [$product, $img2->id]));

        $response->assertSessionHasNoErrors();
        $this->assertFalse($img1->fresh()->is_primary);
        $this->assertTrue($img2->fresh()->is_primary);
    }

    public function test_deleting_primary_image_promotes_next_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Sofa', 'slug' => 'sofa', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Sofa Bed',
            'slug' => 'sofa-bed',
            'sku' => 'SB-01',
            'base_price' => 5000000,
            'is_active' => true,
        ]);

        $img1 = ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/img1.jpg',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $img2 = ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/img2.jpg',
            'is_primary' => false,
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.products.images.destroy', [$product, $img1->id]));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('product_images', ['id' => $img1->id]);
        $this->assertTrue($img2->fresh()->is_primary);
    }
}
