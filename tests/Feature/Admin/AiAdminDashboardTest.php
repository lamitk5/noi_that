<?php

namespace Tests\Feature\Admin;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Faq;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->customer = User::factory()->create(['role' => 'customer']);
    }

    public function test_guest_cannot_access_ai_admin_dashboard(): void
    {
        $response = $this->get(route('admin.ai.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_ai_admin_dashboard(): void
    {
        $response = $this->actingAs($this->customer)->get(route('admin.ai.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_access_ai_admin_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.ai.index'));
        $response->assertOk();
        $response->assertSee('Trợ lý Tư vấn Mua sắm & RAG', false);
        $response->assertSee('search_products');
        $response->assertSee('check_inventory');
    }

    public function test_admin_can_view_conversation_dialog(): void
    {
        $conv = AiConversation::create([
            'uuid' => 'conv-admin-view-01',
            'user_id' => $this->customer->id,
            'title' => 'Tư vấn bàn sồi',
            'status' => 'active',
        ]);

        AiMessage::create([
            'conversation_id' => $conv->id,
            'role' => 'user',
            'content' => 'Bàn này rộng bao nhiêu?',
        ]);

        AiMessage::create([
            'conversation_id' => $conv->id,
            'role' => 'assistant',
            'content' => 'Bàn rộng 1m6 quý khách nhé.',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.ai.conversation', $conv->uuid));
        $response->assertOk();
        $response->assertSee('Tư vấn bàn sồi');
        $response->assertSee('Bàn này rộng bao nhiêu?');
        $response->assertSee('Bàn rộng 1m6 quý khách nhé.');
    }

    public function test_admin_can_delete_conversation(): void
    {
        $conv = AiConversation::create([
            'uuid' => 'conv-admin-delete-01',
            'user_id' => $this->customer->id,
            'title' => 'Cần xóa',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.ai.destroy-conversation', $conv->uuid));
        $response->assertRedirect(route('admin.ai.index', ['tab' => 'conversations']));

        $this->assertDatabaseMissing('ai_conversations', ['uuid' => 'conv-admin-delete-01']);
    }

    public function test_admin_can_test_rag_retrieval(): void
    {
        Faq::create([
            'question' => 'Thời gian bảo hành nội thất?',
            'answer' => 'Nội thất Mộc An được bảo hành 36 tháng đối với toàn bộ phần khung gỗ.',
            'category' => 'Bảo hành',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.ai.test-rag'), [
            'query' => 'Thời gian bảo hành khung gỗ',
        ]);

        $response->assertOk();
        $response->assertSee('Thời gian bảo hành nội thất?');
        $response->assertSee('36 tháng');
    }

    public function test_admin_can_update_ai_settings(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.ai.update-settings'), [
            'ai_enabled' => '1',
            'ai_custom_instructions' => 'Luôn nhắc khách về chương trình miễn phí vận chuyển Tết.',
            'ai_temperature' => '0.6',
            'ai_max_tokens' => '1200',
        ]);

        $response->assertRedirect(route('admin.ai.index', ['tab' => 'settings']));

        $this->assertEquals('1', SiteSetting::get('ai_enabled'));
        $this->assertEquals('Luôn nhắc khách về chương trình miễn phí vận chuyển Tết.', SiteSetting::get('ai_custom_instructions'));
        $this->assertEquals('0.6', SiteSetting::get('ai_temperature'));
        $this->assertEquals('1200', SiteSetting::get('ai_max_tokens'));
    }
}
