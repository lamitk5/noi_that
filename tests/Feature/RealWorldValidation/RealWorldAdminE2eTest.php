<?php

namespace Tests\Feature\RealWorldValidation;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealWorldAdminE2eTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create([
            'email' => 'admin@mocan.test',
            'name' => 'Quản trị viên Mộc An',
        ]);

        $this->customer = User::factory()->create([
            'email' => 'customer@mocan.test',
            'role' => 'customer',
        ]);
    }

    public function test_customer_cannot_access_any_admin_panel_section(): void
    {
        $this->actingAs($this->customer);

        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('admin.products.index'))->assertForbidden();
        $this->get(route('admin.orders.index'))->assertForbidden();
        $this->get(route('admin.categories.index'))->assertForbidden();
        $this->get(route('admin.vouchers.index'))->assertForbidden();
        $this->get(route('admin.inventory.index'))->assertForbidden();
        $this->get(route('admin.reports.index'))->assertForbidden();
        $this->get(route('admin.ai.index'))->assertForbidden();
    }

    public function test_admin_can_access_all_management_modules(): void
    {
        $this->actingAs($this->admin);

        // Core Management Modules
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Bảng điều khiển');
        $this->get(route('admin.products.index'))->assertOk();
        $this->get(route('admin.categories.index'))->assertOk();
        $this->get(route('admin.orders.index'))->assertOk();
        $this->get(route('admin.vouchers.index'))->assertOk();
        $this->get(route('admin.inventory.index'))->assertOk();
        $this->get(route('admin.reports.index'))->assertOk();
        $this->get(route('admin.tickets.index'))->assertOk();
        $this->get(route('admin.faqs.index'))->assertOk();
        $this->get(route('admin.pages.index'))->assertOk();
        $this->get(route('admin.posts.index'))->assertOk();
        $this->get(route('admin.appearance.index'))->assertOk();
        $this->get(route('admin.ai.index'))->assertOk();
    }

    public function test_admin_mutating_action_creates_sanitized_audit_log(): void
    {
        $this->actingAs($this->admin);

        $category = Category::create([
            'name' => 'Bàn làm việc',
            'slug' => 'ban-lam-viec',
            'is_active' => true,
        ]);

        // Mutating action: create a new voucher with sensitive payload attempt
        $res = $this->post(route('admin.vouchers.store'), [
            'code' => 'ADMINVIP2026',
            'name' => 'Giảm 100k',
            'type' => 'fixed',
            'value' => 100000,
            'min_order_amount' => 1000000,
            'is_active' => 1,
            'api_key' => 'SHOULD_BE_REDACTED',
            'secret' => 'TOP_SECRET_VALUE',
        ]);
        $res->assertRedirect(route('admin.vouchers.index'));

        // Verify voucher created
        $this->assertDatabaseHas('vouchers', ['code' => 'ADMINVIP2026']);

        // Verify audit log created with sensitive keys redacted
        $this->assertDatabaseHas('admin_audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'admin.vouchers.store',
            'method' => 'POST',
        ]);

        $log = \App\Models\AdminAuditLog::where('user_id', $this->admin->id)->latest('id')->first();
        $this->assertNotNull($log);
        $payload = $log->payload;
        $this->assertArrayNotHasKey('api_key', $payload);
        $this->assertArrayNotHasKey('secret', $payload);
        $this->assertEquals('ADMINVIP2026', $payload['code']);
    }
}
