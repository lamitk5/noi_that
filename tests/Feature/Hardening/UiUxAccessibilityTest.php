<?php

namespace Tests\Feature\Hardening;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class UiUxAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_html_document_has_vietnamese_language_and_responsive_viewport(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('<html lang="vi">', false);
        $response->assertSee('name="viewport" content="width=device-width, initial-scale=1"', false);
    }

    public function test_stylesheet_defines_reduced_motion_accessibility_rules(): void
    {
        $cssPath = resource_path('css/app.css');
        $this->assertFileExists($cssPath);

        $cssContent = File::get($cssPath);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $cssContent);
        $this->assertStringContainsString('animation-duration: 0.01ms', $cssContent);
        $this->assertStringContainsString('transition-duration: 0.01ms', $cssContent);
    }

    public function test_theme_system_defines_all_five_accessible_color_palettes(): void
    {
        $cssContent = File::get(resource_path('css/app.css'));

        $themes = ['moss', 'wood', 'cream', 'blue', 'black'];
        foreach ($themes as $theme) {
            $this->assertStringContainsString("data-theme='{$theme}'", $cssContent, "Theme {$theme} must be defined in app.css");
        }

        // Verify tokens are mapped
        $this->assertStringContainsString('--color-page: var(--theme-page);', $cssContent);
        $this->assertStringContainsString('--color-surface: var(--theme-surface);', $cssContent);
        $this->assertStringContainsString('--color-heading: var(--theme-heading);', $cssContent);
        $this->assertStringContainsString('--color-body: var(--theme-body);', $cssContent);
    }

    public function test_catalog_and_pdp_render_with_semantic_theme_tokens_and_aria(): void
    {
        $category = Category::create(['name' => 'Bàn Gỗ', 'slug' => 'ban-go-' . uniqid(), 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bàn Trà Tự Nhiên',
            'slug' => 'ban-tra-tu-nhien-' . uniqid(),
            'sku' => 'BTTN-' . strtoupper(uniqid()),
            'base_price' => 2500000,
            'is_active' => true,
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $product->sku . '-V1',
            'price' => 2500000,
            'stock' => 5,
            'is_default' => true,
        ]);

        // Catalog view
        $catalogRes = $this->get(route('products.index'));
        $catalogRes->assertStatus(200);
        $catalogRes->assertSee('aria-label="Điều hướng chính"', false);

        // PDP view
        $pdpRes = $this->get(route('products.show', $product->slug));
        $pdpRes->assertStatus(200);
        $pdpRes->assertSee('bg-page');
    }
}
