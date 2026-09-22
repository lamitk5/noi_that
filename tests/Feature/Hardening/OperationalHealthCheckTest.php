<?php

namespace Tests\Feature\Hardening;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_returns_healthy_with_subsystem_statuses(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertJsonStructure([
                'status',
                'database',
                'cache',
                'storage',
                'environment',
                'timestamp',
            ])
            ->assertJson([
                'status' => 'healthy',
                'database' => 'connected',
                'cache' => 'connected',
                'storage' => 'writable',
            ]);
    }

    public function test_health_check_handles_dependency_failure_safely_without_leaking_details(): void
    {
        \Illuminate\Support\Facades\Cache::shouldReceive('put')->andThrow(new \RuntimeException('Sensitive DB Credentials root:secret@127.0.0.1'));

        $response = $this->getJson('/health');
        $response->assertStatus(503);
        $response->assertJson([
            'status' => 'unhealthy',
            'cache' => 'unreachable',
        ]);
        $this->assertStringNotContainsString('Sensitive DB Credentials', (string) $response->getContent());
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_branded_error_views_exist_and_render_user_friendly_vietnamese_content(): void
    {
        $view403 = view('errors.403')->render();
        $this->assertStringContainsString('Truy cập bị từ chối', $view403);
        $this->assertStringContainsString('Quay về trang chủ', $view403);

        $view404 = view('errors.404')->render();
        $this->assertStringContainsString('Không tìm thấy trang', $view404);
        $this->assertStringContainsString('Về trang chủ', $view404);

        $view500 = view('errors.500')->render();
        $this->assertStringContainsString('Đã xảy ra sự cố', $view500);
        $this->assertStringContainsString('Thử lại', $view500);

        $view503 = view('errors.503')->render();
        $this->assertStringContainsString('Bảo trì hệ thống', $view503);
        $this->assertStringContainsString('Hotline', $view503);
    }

    public function test_non_existent_route_returns_branded_404_page(): void
    {
        $response = $this->get('/duong-dan-khong-ton-tai-404-test');

        $response->assertStatus(404);
        $response->assertSee('Không tìm thấy trang');
        $response->assertSee('Lỗi 404');
    }
}
