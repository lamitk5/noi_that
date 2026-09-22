<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppearanceCustomizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_appearance_customizer(): void
    {
        $response = $this->get(route('admin.appearance.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_appearance_customizer(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $response = $this->actingAs($customer)->get(route('admin.appearance.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_appearance_customizer_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.appearance.index'));

        $response->assertStatus(200);
        $response->assertSee('Tùy biến diện mạo website');
        $response->assertSee('Nhận diện thương hiệu');
        $response->assertSee('Thanh thông báo & Header', false);
        $response->assertSee('Xem trước trực tiếp (Live Storefront Preview)');
    }

    public function test_admin_can_update_appearance_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $payload = [
            'site_name' => 'Mộc An Studio',
            'site_tagline' => 'Tinh hoa gỗ quý',
            'site_hotline' => '0988 888 888',
            'promo_bar_enabled' => '1',
            'promo_bar_text' => 'Khuyến mãi đặc biệt mừng khai trương showroom',
            'default_theme' => 'wood',
            'hero_title' => 'Nội thất sang trọng cho căn hộ hiện đại',
            'hero_subtitle' => 'Bộ sưu tập độc quyền Mộc An 2026',
            'show_hero' => '1',
            'show_trust_badges' => '1',
            'show_categories' => '1',
            'show_best_sellers' => '1',
            'show_testimonials' => '0',
            'footer_copyright' => '© 2026 Mộc An Studio. Bản quyền thuộc về Mộc An.',
        ];

        $response = $this->actingAs($admin)->post(route('admin.appearance.update'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertEquals('Mộc An Studio', SiteSetting::get('site_name'));
        $this->assertEquals('Khuyến mãi đặc biệt mừng khai trương showroom', SiteSetting::get('promo_bar_text'));
        $this->assertEquals('0', SiteSetting::get('show_testimonials'));

        // Public storefront reflects the updated settings
        $publicResponse = $this->get(route('home'));
        $publicResponse->assertStatus(200);
        $publicResponse->assertSee('Khuyến mãi đặc biệt mừng khai trương showroom');
        $publicResponse->assertDontSee('Hơn 5.000 tổ ấm tin tưởng');
    }

    public function test_admin_can_reset_appearance_settings(): void
    {
        $admin = User::factory()->admin()->create();

        SiteSetting::set('site_name', 'Tên Đã Đổi', 'appearance');

        $response = $this->actingAs($admin)->post(route('admin.appearance.reset'));

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertEquals('Mộc An', SiteSetting::get('site_name'));
    }
}
