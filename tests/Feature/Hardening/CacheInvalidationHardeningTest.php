<?php

namespace Tests\Feature\Hardening;

use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheInvalidationHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitesetting_invalidation_hook_updates_cache_immediately(): void
    {
        SiteSetting::set('store_phone', '0901111111');
        $this->assertEquals('0901111111', SiteSetting::get('store_phone'));

        // Direct model update or set() must invalidate cache
        $setting = SiteSetting::where('key', 'store_phone')->first();
        $setting->update(['value' => '0909999999']);

        // Cache must be cleared, returning new value immediately
        $this->assertEquals('0909999999', SiteSetting::get('store_phone'));
    }

    public function test_cmspage_mutation_invalidates_page_cache_immediately(): void
    {
        $page = CmsPage::create([
            'slug' => 'chinh-sach-giao-hang',
            'title' => 'Chính sách giao hàng ban đầu',
            'content' => 'Nội dung giao hàng phiên bản 1',
            'is_active' => true,
        ]);

        $response1 = $this->get(route('pages.show', $page->slug));
        $response1->assertStatus(200);
        $response1->assertSee('Nội dung giao hàng phiên bản 1');

        // Update page
        $page->update([
            'content' => 'Nội dung giao hàng đã được cập nhật phiên bản 2',
        ]);

        // Subsequent request must serve fresh content
        $response2 = $this->get(route('pages.show', $page->slug));
        $response2->assertStatus(200);
        $response2->assertSee('Nội dung giao hàng đã được cập nhật phiên bản 2');
        $response2->assertDontSee('Nội dung giao hàng phiên bản 1');
    }

    public function test_pdp_and_catalog_markup_have_proper_loading_attributes(): void
    {
        $category = Category::create(['name' => 'Bàn', 'slug' => 'ban-' . uniqid(), 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bàn Trà Sang Trọng',
            'slug' => 'ban-tra-sang-trong-' . uniqid(),
            'sku' => 'BT-' . strtoupper(uniqid()),
            'base_price' => 2000000,
            'is_active' => true,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'https://example.com/main.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'https://example.com/thumb.jpg',
            'is_primary' => false,
            'sort_order' => 1,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'price' => 2000000,
            'stock' => 5,
            'sku' => 'VAR-' . strtoupper(uniqid()),
        ]);

        // Catalog page
        $catalogResponse = $this->get(route('products.index'));
        $catalogResponse->assertStatus(200);
        $catalogResponse->assertSee('loading="lazy"', false);

        // PDP
        $pdpResponse = $this->get(route('products.show', $product->slug));
        $pdpResponse->assertStatus(200);
        $pdpResponse->assertSee('loading="eager"', false);
        $pdpResponse->assertSee('loading="lazy"', false);
    }
}
