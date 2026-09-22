<?php

namespace Tests\Feature\Hardening;

use App\Models\AdminAuditLog;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditLogSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_user_cannot_access_admin_routes(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->get(route('admin.dashboard'));
        $response->assertStatus(403);

        $guestResponse = $this->get(route('admin.dashboard'));
        $guestResponse->assertStatus(403);
    }

    public function test_mutating_admin_actions_create_audit_log_entries(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Sofa Phòng Khách',
            'description' => 'Mô tả sofa',
            'is_active' => 1,
            '_token' => 'dummy_csrf_token',
            'password' => 'secret123',
        ]);

        $this->assertDatabaseHas('admin_audit_logs', [
            'user_id' => $admin->id,
            'method' => 'POST',
        ]);

        $log = AdminAuditLog::where('user_id', $admin->id)->first();
        $this->assertNotNull($log);
        $this->assertSame('POST', $log->method);
        $this->assertArrayNotHasKey('_token', $log->payload);
        $this->assertArrayNotHasKey('password', $log->payload);
        $this->assertSame('Sofa Phòng Khách', $log->payload['name']);
    }

    public function test_get_admin_requests_do_not_create_audit_logs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);

        $this->assertDatabaseCount('admin_audit_logs', 0);
    }

    public function test_audit_logging_failure_does_not_break_admin_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        \Illuminate\Support\Facades\Event::listen('eloquent.creating: App\Models\AdminAuditLog', function () {
            throw new \RuntimeException('Database lock on admin_audit_logs');
        });

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Bàn Tròn Gỗ Mun',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', ['name' => 'Bàn Tròn Gỗ Mun']);
    }

    public function test_checkout_rate_limiter_throttles_excessive_requests(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        // 10 allowed requests
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($user)->post(route('checkout.store'), []);
            $this->assertNotEquals(429, $response->status(), "Request $i should not be rate limited");
        }

        // 11th request throttled
        $response = $this->actingAs($user)->post(route('checkout.store'), []);
        $this->assertSame(429, $response->status(), '11th checkout request must be throttled with 429');
    }
}
