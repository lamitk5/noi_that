<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAppearancePreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_appearance_settings(): void
    {
        $response = $this->get(route('account.appearance'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_appearance_settings(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('account.appearance'));

        $response->assertStatus(200);
        $response->assertSee('Cài đặt giao diện cá nhân');
        $response->assertSee('Bảng màu chủ đề (Theme Presets)');
        $response->assertSee('Kích thước chữ hiển thị (Font Scaling)');
        $response->assertSee('Mật độ khoảng cách (Layout Density)');
        $response->assertSee('Giảm hiệu ứng chuyển động');
    }

    public function test_authenticated_user_can_save_appearance_preferences(): void
    {
        $user = User::factory()->create();

        $payload = [
            'theme' => 'blue',
            'font_scale' => 'lg',
            'density' => 'compact',
            'reduced_motion' => '1',
        ];

        $response = $this->actingAs($user)->post(route('account.appearance.update'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $user->refresh();
        $this->assertEquals('blue', $user->getAppearanceSetting('theme'));
        $this->assertEquals('lg', $user->getAppearanceSetting('font_scale'));
        $this->assertEquals('compact', $user->getAppearanceSetting('density'));
        $this->assertEquals('1', $user->getAppearanceSetting('reduced_motion'));
    }

    public function test_invalid_theme_is_rejected(): void
    {
        $user = User::factory()->create();

        $payload = [
            'theme' => 'invalid-neon-color',
            'font_scale' => 'base',
            'density' => 'comfortable',
            'reduced_motion' => '0',
        ];

        $response = $this->actingAs($user)->post(route('account.appearance.update'), $payload);

        $response->assertSessionHasErrors(['theme']);
    }

    public function test_user_can_reset_appearance_settings_to_default(): void
    {
        $user = User::factory()->create([
            'appearance_settings' => [
                'theme' => 'black',
                'font_scale' => 'sm',
                'density' => 'compact',
                'reduced_motion' => '1',
            ],
        ]);

        $response = $this->actingAs($user)->post(route('account.appearance.reset'));

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $user->refresh();
        $this->assertNull($user->appearance_settings);
        $this->assertNull($user->getAppearanceSetting('theme'));
    }
}
