<?php

namespace Tests\Feature\Ai;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiAssistantCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_create_and_view_conversation(): void
    {
        $session = ['guest_test_id' => 'guest_abc_123'];
        $response = $this->withSession($session)->postJson(route('ai.conversations.store'));
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'conversation' => ['uuid', 'title']
            ]);

        $uuid = $response->json('conversation.uuid');
        $this->assertDatabaseHas('ai_conversations', ['uuid' => $uuid]);

        $showResponse = $this->withSession($session)->getJson(route('ai.conversations.show', $uuid));
        $showResponse->assertOk()
            ->assertJson([
                'success' => true,
                'conversation' => [
                    'uuid' => $uuid,
                ]
            ]);
    }

    public function test_guest_can_send_chat_message_and_receive_assistant_reply(): void
    {
        $response = $this->postJson(route('ai.chat'), [
            'message' => 'Xin chào Mộc An!',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'conversation_uuid',
                'conversation_title',
                'message' => ['id', 'role', 'content']
            ]);

        $this->assertEquals('assistant', $response->json('message.role'));
        $this->assertNotEmpty($response->json('message.content'));

        // Assert database records
        $this->assertDatabaseHas('ai_messages', [
            'role' => 'user',
            'content' => 'Xin chào Mộc An!',
        ]);
        $this->assertDatabaseHas('ai_messages', [
            'role' => 'assistant',
        ]);
    }

    public function test_authenticated_user_conversation_is_associated_with_user(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->postJson(route('ai.chat'), [
            'message' => 'Tôi muốn hỏi về sản phẩm',
        ]);

        $response->assertOk();
        $uuid = $response->json('conversation_uuid');

        $conversation = AiConversation::where('uuid', $uuid)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals($customer->id, $conversation->user_id);
    }

    public function test_stranger_cannot_access_another_users_conversation(): void
    {
        $customerA = User::factory()->create(['role' => 'customer']);
        $customerB = User::factory()->create(['role' => 'customer']);

        $convA = AiConversation::create([
            'uuid' => 'conv-a-1234',
            'user_id' => $customerA->id,
            'title' => 'Trò chuyện của A',
            'status' => 'active',
        ]);

        // Customer B tries to view Customer A's conversation
        $response = $this->actingAs($customerB)->getJson(route('ai.conversations.show', $convA->uuid));
        $response->assertStatus(404);
    }

    public function test_user_can_clear_messages_in_conversation(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $conv = AiConversation::create([
            'uuid' => 'conv-clear-test',
            'user_id' => $customer->id,
            'title' => 'Test Clear',
            'status' => 'active',
        ]);

        AiMessage::create([
            'conversation_id' => $conv->id,
            'role' => 'user',
            'content' => 'Tin nhắn cũ',
        ]);

        $this->assertEquals(1, $conv->messages()->count());

        $response = $this->actingAs($customer)->postJson(route('ai.conversations.clear', $conv->uuid));
        $response->assertOk()->assertJson(['success' => true]);

        $this->assertEquals(0, $conv->fresh()->messages()->count());
    }

    public function test_user_can_delete_conversation(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $conv = AiConversation::create([
            'uuid' => 'conv-delete-test',
            'user_id' => $customer->id,
            'title' => 'Test Delete',
            'status' => 'active',
        ]);

        $response = $this->actingAs($customer)->deleteJson(route('ai.conversations.destroy', $conv->uuid));
        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseMissing('ai_conversations', ['uuid' => 'conv-delete-test']);
    }

    public function test_empty_or_oversized_message_is_rejected(): void
    {
        $emptyRes = $this->postJson(route('ai.chat'), ['message' => '   ']);
        $emptyRes->assertStatus(422);

        $tooLongRes = $this->postJson(route('ai.chat'), ['message' => str_repeat('a', 1500)]);
        $tooLongRes->assertStatus(422);
    }

    public function test_home_page_contains_ai_chat_widget(): void
    {
        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('Trợ lý Mộc An');
        $response->assertSee('ai-assistant-root', false);
    }
}
