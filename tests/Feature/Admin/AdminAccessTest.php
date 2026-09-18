<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_admin(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect(route('login'));
    }

    public function test_customer_receives_403_forbidden_when_accessing_admin(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $response = $this->actingAs($customer)->get('/admin');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Quản trị viên',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Quản trị Mộc An');
        $response->assertSee('Bảng điều khiển');
        $response->assertSee('Quản trị viên');
    }

    public function test_admin_dashboard_shows_module_cards_without_broken_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Quản lý Sản phẩm');
        $response->assertSee('Quản lý Danh mục');
        $response->assertSee('Quản lý Kho hàng');
        $response->assertSee('Quản lý Đơn hàng');
        $response->assertSee('Khách hàng');
        $response->assertSee('Báo cáo & Phân tích', false);
    }

    public function test_customer_does_not_see_admin_link_in_header_dropdown(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $response = $this->actingAs($customer)->get(route('home'));

        $response->assertStatus(200);
        $response->assertDontSee('Trang quản trị');
    }

    public function test_admin_sees_admin_link_in_header_dropdown(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('Trang quản trị');
        $response->assertSee(route('admin.dashboard'));
    }
}
