<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * Generate dynamic sitemap.xml for search engines.
     */
    public function sitemap(): Response
    {
        $urls = [];

        // 1. Static Core Pages
        $staticRoutes = [
            'home' => ['priority' => '1.0', 'changefreq' => 'daily'],
            'products.index' => ['priority' => '0.9', 'changefreq' => 'daily'],
            'posts.index' => ['priority' => '0.8', 'changefreq' => 'weekly'],
            'faq.index' => ['priority' => '0.6', 'changefreq' => 'monthly'],
            'pages.about' => ['priority' => '0.7', 'changefreq' => 'monthly'],
            'pages.contact' => ['priority' => '0.7', 'changefreq' => 'monthly'],
            'pages.purchase-policy' => ['priority' => '0.5', 'changefreq' => 'monthly'],
            'pages.warranty-policy' => ['priority' => '0.5', 'changefreq' => 'monthly'],
            'pages.return-policy' => ['priority' => '0.5', 'changefreq' => 'monthly'],
        ];

        foreach ($staticRoutes as $routeName => $meta) {
            if (\Illuminate\Support\Facades\Route::has($routeName)) {
                $urls[] = [
                    'loc' => route($routeName),
                    'lastmod' => now()->toAtomString(),
                    'changefreq' => $meta['changefreq'],
                    'priority' => $meta['priority'],
                ];
            }
        }

        // 2. Active Categories
        $categories = Category::where('is_active', true)->get();
        foreach ($categories as $cat) {
            $urls[] = [
                'loc' => route('products.index', ['category' => $cat->slug]),
                'lastmod' => $cat->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        // 3. Active Products
        $products = Product::where('is_active', true)->latest('updated_at')->get();
        foreach ($products as $product) {
            $urls[] = [
                'loc' => route('products.show', $product->slug),
                'lastmod' => $product->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        // 4. Published Blog Posts
        $posts = Post::where('is_published', true)->latest('updated_at')->get();
        foreach ($posts as $post) {
            $urls[] = [
                'loc' => route('posts.show', $post->slug),
                'lastmod' => $post->updated_at->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $item) {
            $xml .= "    <url>\n";
            $xml .= "        <loc>" . htmlspecialchars($item['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
            $xml .= "        <lastmod>{$item['lastmod']}</lastmod>\n";
            $xml .= "        <changefreq>{$item['changefreq']}</changefreq>\n";
            $xml .= "        <priority>{$item['priority']}</priority>\n";
            $xml .= "    </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'text/xml; charset=UTF-8',
        ]);
    }

    /**
     * Generate dynamic robots.txt.
     */
    public function robots(): Response
    {
        $sitemapUrl = url('/sitemap.xml');

        $content = "User-agent: *\n";
        $content .= "Disallow: /admin/\n";
        $content .= "Disallow: /admin\n";
        $content .= "Disallow: /checkout/\n";
        $content .= "Disallow: /thanh-toan/\n";
        $content .= "Disallow: /thanh-toan\n";
        $content .= "Disallow: /gio-hang/\n";
        $content .= "Disallow: /gio-hang\n";
        $content .= "Disallow: /tai-khoan/\n";
        $content .= "Disallow: /tai-khoan\n";
        $content .= "Disallow: /dang-nhap\n";
        $content .= "Disallow: /dang-ky\n";
        $content .= "Disallow: /dat-hang-thanh-cong/\n";
        $content .= "Disallow: /health\n";
        $content .= "Disallow: /api/\n";
        $content .= "Disallow: /api\n\n";
        $content .= "Sitemap: {$sitemapUrl}\n";

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
