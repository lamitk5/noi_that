<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_account_page(): void
    {
        $response = $this->get(route('account.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_account_page(): void
    {
        $user = User::factory()->create([
            'name' => 'Trần Thị B',
            'email' => 'tranthib@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('account.index'));

        $response->assertStatus(200);
        $response->assertSee('Trần Thị B');
        $response->assertSee('tranthib@example.com');
    }

    public function test_header_renders_auth_navigation_when_authenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('account.index'));

        $response->assertStatus(200);
        $response->assertSee('Tài khoản của tôi');
        $response->assertSee('Lịch sử đơn hàng');
        $response->assertSee('Đăng xuất');
        $response->assertDontSee('Đăng nhập');
    }
}