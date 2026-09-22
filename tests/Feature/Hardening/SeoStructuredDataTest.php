<?php

namespace Tests\Feature\Hardening;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoStructuredDataTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(string $name, int $price, int $stock): Product
    {
        $category = Category::create([
            'name' => 'Phòng Khách',
            'slug' => 'phong-khach-' . uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => str()->slug($name) . '-' . uniqid(),
            'sku' => 'SKU-' . strtoupper(uniqid()),
            'base_price' => $price,
            'short_description' => 'Bàn trà gỗ sồi cao cấp phong cách Bắc Âu.',
            'is_active' => true,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'https://example.com/bantra.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'color' => 'Gỗ Tự Nhiên',
            'size' => '120x60cm',
            'price' => $price,
            'stock' => $stock,
            'sku' => 'VAR-' . strtoupper(uniqid()),
        ]);

        return $product;
    }

    public function test_homepage_renders_website_and_organization_json_ld(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Canonical & OpenGraph
        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('property="og:title"', false);
        $response->assertSee('property="og:description"', false);
        $response->assertSee('property="og:type"', false);

        // JSON-LD Schema
        $response->assertSee('application/ld+json', false);
        $this->assertStringContainsString('"@type": "WebSite"', $content);
        $this->assertStringContainsString('"@type": "Organization"', $content);
        $this->assertStringContainsString('SearchAction', $content);
        $this->assertStringContainsString('Mộc An', $content);
    }

    public function test_pdp_renders_product_json_ld_schema_with_offers_and_availability(): void
    {
        $inStockProduct = $this->createProduct('Bàn Trà Bắc Âu', 3500000, 5);

        $response = $this->get(route('products.show', $inStockProduct->slug));
        $response->assertStatus(200);
        $content = $response->getContent();

        // Product JSON-LD Schema
        $response->assertSee('application/ld+json', false);
        $this->assertStringContainsString('"@type": "Product"', $content);
        $this->assertStringContainsString($inStockProduct->name, $content);
        $this->assertStringContainsString('"priceCurrency": "VND"', $content);
        $this->assertStringContainsString('3500000', $content);
        $this->assertStringContainsString('schema.org/InStock', $content);

        // Out of stock product
        $outOfStockProduct = $this->createProduct('Kệ Tivi Gỗ Hương', 5000000, 0);
        $oosResponse = $this->get(route('products.show', $outOfStockProduct->slug));
        $oosContent = $oosResponse->getContent();
        $this->assertStringContainsString('schema.org/OutOfStock', $oosContent);
    }

    public function test_blog_post_renders_blog_posting_json_ld_schema(): void
    {
        $author = User::factory()->create(['name' => 'Kiến Trúc Sư Mộc An']);

        $category = Category::create([
            'name' => 'Mẹo Nội Thất',
            'slug' => 'meo-noi-that-' . uniqid(),
            'is_active' => true,
        ]);

        $post = Post::create([
            'title' => 'Cách chọn bàn ăn gỗ phù hợp cho căn hộ nhỏ',
            'slug' => 'cach-chon-ban-an-' . uniqid(),
            'author_id' => $author->id,
            'excerpt' => 'Kinh nghiệm bố trí nội thất phòng ăn thông minh.',
            'content' => 'Nội dung bài viết chi tiết...',
            'featured_image' => 'https://example.com/post.jpg',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->get(route('posts.show', $post->slug));
        $response->assertStatus(200);
        $content = $response->getContent();

        // OpenGraph
        $this->assertStringContainsString('property="og:type" content="article"', $content);
        $this->assertStringContainsString($post->title, $content);

        // BlogPosting JSON-LD Schema
        $this->assertStringContainsString('"@type": "BlogPosting"', $content);
        $this->assertStringContainsString($post->title, $content);
        $this->assertStringContainsString('Kiến Trúc Sư Mộc An', $content);
        $this->assertStringContainsString('datePublished', $content);
    }
}
