<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToastNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_layout_contains_toast_root_and_toast_script(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('id="toast-root"', false);
        $response->assertSee('window.Toast', false);
    }

    public function test_login_redirect_flashes_success_message_for_toast(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertSessionHas('success', 'Đăng nhập thành công!');
    }
}
