<?php

namespace Tests\Feature\Hardening;

use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoRobotsSitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_xml_renders_active_resources_and_excludes_inactive(): void
    {
        // 1. Active Product
        $category = Category::create(['name' => 'Phòng Khách', 'slug' => 'phong-khach-' . uniqid(), 'is_active' => true]);
        $activeProduct = Product::create([
            'category_id' => $category->id,
            'name' => 'Sofa Nỉ Băng',
            'slug' => 'sofa-ni-bang-' . uniqid(),
            'sku' => 'SF-NI-' . strtoupper(uniqid()),
            'base_price' => 5000000,
            'is_active' => true,
        ]);
        ProductImage::create(['product_id' => $activeProduct->id, 'image_path' => 'https://example.com/sofa.jpg', 'is_primary' => true, 'sort_order' => 0]);
        ProductVariant::create(['product_id' => $activeProduct->id, 'color' => 'Xám', 'price' => 5000000, 'stock' => 5, 'sku' => 'SF-VAR-' . strtoupper(uniqid())]);

        // 2. Inactive Product
        $inactiveProduct = Product::create([
            'category_id' => $category->id,
            'name' => 'Ghế Cũ Ngừng Bán',
            'slug' => 'ghe-cu-' . uniqid(),
            'sku' => 'GHE-CU-' . strtoupper(uniqid()),
            'base_price' => 1000000,
            'is_active' => false,
        ]);

        // 3. Published Post
        $author = User::factory()->create();
        $publishedPost = Post::create([
            'title' => 'Kinh nghiệm trang trí nhà cửa đẹp',
            'slug' => 'kinh-nghiem-trang-tri-' . uniqid(),
            'author_id' => $author->id,
            'content' => 'Nội dung bài viết...',
            'is_published' => true,
            'published_at' => now(),
        ]);

        // 4. Draft Post
        $draftPost = Post::create([
            'title' => 'Bản thảo chưa xuất bản',
            'slug' => 'ban-thao-' . uniqid(),
            'author_id' => $author->id,
            'content' => 'Nội dung nháp...',
            'is_published' => false,
            'published_at' => null,
        ]);

        $response = $this->get('/sitemap.xml');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');

        $xml = $response->getContent();

        // Must include active resources
        $this->assertStringContainsString(route('home'), $xml);
        $this->assertStringContainsString(route('products.index'), $xml);
        $this->assertStringContainsString(route('products.show', $activeProduct->slug), $xml);
        $this->assertStringContainsString(route('posts.show', $publishedPost->slug), $xml);
        $this->assertStringContainsString(route('pages.purchase-policy'), $xml);
        $this->assertStringContainsString(route('faq.index'), $xml);

        // Must NOT include inactive products or drafts
        $this->assertStringNotContainsString(route('products.show', $inactiveProduct->slug), $xml);
        $this->assertStringNotContainsString(route('posts.show', $draftPost->slug), $xml);
    }

    public function test_robots_txt_disallows_private_routes_and_links_sitemap(): void
    {
        $response = $this->get('/robots.txt');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $content = $response->getContent();
        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Disallow: /thanh-toan', $content);
        $this->assertStringContainsString('Disallow: /gio-hang', $content);
        $this->assertStringContainsString('Disallow: /tai-khoan', $content);
        $this->assertStringContainsString('Disallow: /api', $content);
        $this->assertStringContainsString('Sitemap: ' . url('/sitemap.xml'), $content);
    }

    public function test_private_routes_render_noindex_nofollow_meta(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);

        // 1. Cart
        $cartResponse = $this->get(route('cart.index'));
        $cartResponse->assertStatus(200);
        $cartResponse->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        // 2. Checkout (with item in cart)
        $category = Category::create(['name' => 'Bàn', 'slug' => 'ban-' . uniqid(), 'is_active' => true]);
        $prod = Product::create(['category_id' => $category->id, 'name' => 'Bàn Ăn', 'slug' => 'ban-an-' . uniqid(), 'sku' => 'BA-' . uniqid(), 'base_price' => 1000000, 'is_active' => true]);
        $var = ProductVariant::create(['product_id' => $prod->id, 'price' => 1000000, 'stock' => 10, 'sku' => 'BA-V-' . uniqid()]);

        $checkoutResponse = $this->actingAs($user)
            ->withSession(['cart' => [$var->id => 1]])
            ->get(route('checkout.index'));
        $checkoutResponse->assertStatus(200);
        $checkoutResponse->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        // 3. User account profile
        $accountResponse = $this->actingAs($user)->get(route('account.index'));
        $accountResponse->assertStatus(200);
        $accountResponse->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        // 4. Admin dashboard
        $adminResponse = $this->actingAs($admin)->get(route('admin.dashboard'));
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }
}
