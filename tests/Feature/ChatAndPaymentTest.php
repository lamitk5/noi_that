<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Payments\MomoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatAndPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_customer_chat_messages_are_preserved_across_sessions_and_closed_chats(): void
    {
        $user = User::factory()->create();
        $staff = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

        // 1. First chat session
        $chat1 = Chat::create([
            'user_id' => $user->id,
            'status' => Chat::STATUS_OPEN,
            'last_message_at' => now()->subDay(),
        ]);

        ChatMessage::create([
            'chat_id' => $chat1->id,
            'sender_id' => $user->id,
            'message' => 'Xin chào cửa hàng, mình cần tư vấn bàn ăn',
            'is_read' => true,
        ]);

        ChatMessage::create([
            'chat_id' => $chat1->id,
            'sender_id' => $staff->id,
            'message' => 'Chào bạn, Mộc An có mẫu bàn ăn gỗ sồi 6 ghế rất đẹp ạ',
            'is_read' => false,
        ]);

        // Close the first chat (staff resolved it)
        $chat1->update(['status' => Chat::STATUS_CLOSED]);

        // 2. User checks chat session later
        $this->actingAs($user);
        $sessionRes = $this->getJson('/api/chat/session');
        $sessionRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'chat_id' => $chat1->id,
                'unread' => 1,
            ]);

        // 3. User loads messages - old messages from closed chat MUST be visible
        $messagesRes = $this->getJson('/api/chat/' . $chat1->id . '/messages');
        $messagesRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'messages')
            ->assertJsonPath('messages.0.content', 'Xin chào cửa hàng, mình cần tư vấn bàn ăn')
            ->assertJsonPath('messages.1.content', 'Chào bạn, Mộc An có mẫu bàn ăn gỗ sồi 6 ghế rất đẹp ạ');

        // Staff message marked as read
        $sessionAfterRead = $this->getJson('/api/chat/session');
        $sessionAfterRead->assertJsonPath('unread', 0);

        // 4. User sends a new message to the chat - chat should automatically reopen
        $sendRes = $this->postJson('/api/chat/' . $chat1->id . '/message', [
            'message' => 'Bàn này giá bao nhiêu vậy shop?',
        ]);
        $sendRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message.content', 'Bàn này giá bao nhiêu vậy shop?');

        $this->assertDatabaseHas('chats', [
            'id' => $chat1->id,
            'status' => Chat::STATUS_OPEN,
        ]);

        // 5. Total 3 messages now visible to user
        $allMessages = $this->getJson('/api/chat/' . $chat1->id . '/messages');
        $allMessages->assertJsonCount(3, 'messages');
    }

    public function test_momo_service_uses_pay_with_atm_and_creates_valid_signature(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_code' => 'TESTMOMO123',
            'user_id' => $user->id,
            'customer_name' => 'Nguyen Van A',
            'customer_phone' => '0912345678',
            'customer_email' => 'test@example.com',
            'shipping_address' => '123 Pho Hue',
            'city_name' => 'Ha Noi',
            'district_name' => 'Hai Ba Trung',
            'ward_name' => 'Pho Hue',
            'payment_method' => 'momo',
            'payment_status' => Order::PAYMENT_PENDING,
            'order_status' => Order::STATUS_PENDING,
            'total_price' => 500000,
        ]);

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'momo',
            'provider_reference' => 'TESTMOMO123_TXN',
            'amount' => 500000,
            'status' => 'pending',
        ]);

        $momoService = app(MomoService::class);

        // Verify config is payWithATM
        $this->assertEquals('payWithATM', config('services.momo.request_type'));

        // Mock MoMo gateway response
        Http::fake([
            'https://test-payment.momo.vn/v2/gateway/api/create' => Http::response([
                'resultCode' => 0,
                'message' => 'Successful.',
                'payUrl' => 'https://test-payment.momo.vn/v2/gateway/pay?token=TEST_TOKEN',
            ], 200),
        ]);

        $payUrl = $momoService->createPayment($order, $transaction);
        $this->assertStringContainsString('https://test-payment.momo.vn', $payUrl);

        // Verify payload sent had requestType = 'payWithATM'
        Http::assertSent(function ($request) {
            $data = $request->data();
            return ($data['requestType'] ?? '') === 'payWithATM'
                && !empty($data['signature'])
                && $data['amount'] === 500000;
        });
    }

    public function test_user_has_only_one_chat_thread_and_admin_sees_single_entry(): void
    {
        $user = User::factory()->create();
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

        // First session
        $this->actingAs($user);
        $res1 = $this->getJson('/api/chat/session');
        $chatId = $res1->json('chat_id');

        $this->postJson("/api/chat/{$chatId}/message", ['message' => 'Tin nhắn 1']);

        // Admin closes chat
        $this->actingAs($admin);
        $chat = Chat::find($chatId);
        $this->post("/admin/chats/{$chat->id}/close");
        $this->assertEquals(Chat::STATUS_CLOSED, $chat->fresh()->status);

        // User connects again later
        $this->actingAs($user);
        $res2 = $this->getJson('/api/chat/session');
        $this->assertEquals($chatId, $res2->json('chat_id'));

        // User sends another message
        $this->postJson("/api/chat/{$chatId}/message", ['message' => 'Tin nhắn 2']);

        // Verify only 1 chat exists in the database for this user
        $this->assertEquals(1, Chat::where('user_id', $user->id)->count());

        // Admin index only lists 1 entry for this user
        $this->actingAs($admin);
        $adminRes = $this->getJson('/admin/chats');
        $userRows = collect($adminRes->json('data.data'))->filter(fn ($item) => $item['user_id'] === $user->id);
        $this->assertCount(1, $userRows);
    }

    public function test_admin_chat_list_only_shows_users_who_sent_messages(): void
    {
        $activeUser = User::factory()->create(['name' => 'Active Chatter']);
        $idleUser = User::factory()->create(['name' => 'Idle Visitor']);
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

        // Idle user merely opens session but sends NO messages
        $this->actingAs($idleUser);
        $this->getJson('/api/chat/session');

        // Active user sends a message
        $this->actingAs($activeUser);
        $res = $this->getJson('/api/chat/session');
        $chatId = $res->json('chat_id');
        $this->postJson("/api/chat/{$chatId}/message", ['message' => 'Xin chào!']);

        // Admin checks chat index
        $this->actingAs($admin);
        $adminRes = $this->getJson('/admin/chats');
        $userIds = collect($adminRes->json('data.data'))->pluck('user_id')->all();

        $this->assertContains($activeUser->id, $userIds);
        $this->assertNotContains($idleUser->id, $userIds);
    }
}
