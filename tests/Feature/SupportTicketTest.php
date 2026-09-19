<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_support_ticket(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('account.tickets.store'), [
            'subject' => 'Hỏi về thời gian giao hàng đơn hàng bàn trà',
            'category' => 'delivery',
            'priority' => 'high',
            'message' => 'Tôi đặt đơn cách đây 3 ngày, xin hỏi khi nào giao tới Quận 1?',
        ]);

        $ticket = SupportTicket::first();
        $this->assertNotNull($ticket);
        $this->assertEquals($user->id, $ticket->user_id);
        $this->assertEquals('open', $ticket->status);
        $this->assertStringStartsWith('TK-', $ticket->ticket_code);

        $response->assertRedirect(route('account.tickets.show', $ticket));
        $this->assertDatabaseHas('ticket_replies', [
            'support_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => 'Tôi đặt đơn cách đây 3 ngày, xin hỏi khi nào giao tới Quận 1?',
        ]);
    }

    public function test_customer_and_admin_can_reply_to_ticket(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $ticket = SupportTicket::create([
            'ticket_code' => 'TK-TEST1234',
            'user_id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'subject' => 'Hỗ trợ lắp đặt tủ quần áo',
            'category' => 'technical',
            'priority' => 'normal',
            'status' => 'open',
        ]);

        // Admin replies
        $adminReplyResponse = $this->actingAs($admin)->post(route('admin.tickets.reply', $ticket), [
            'message' => 'Chào bạn, đội ngũ kỹ thuật sẽ đến vào chiều mai lúc 14:00.',
        ]);

        $adminReplyResponse->assertRedirect(route('admin.tickets.show', $ticket));
        $this->assertDatabaseHas('ticket_replies', [
            'support_ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'message' => 'Chào bạn, đội ngũ kỹ thuật sẽ đến vào chiều mai lúc 14:00.',
        ]);

        $ticket->refresh();
        $this->assertEquals('in_progress', $ticket->status);

        // Customer replies
        $customerReplyResponse = $this->actingAs($customer)->post(route('account.tickets.reply', $ticket), [
            'message' => 'Dạ vâng cảm ơn shop, tôi sẽ đợi ở nhà.',
        ]);

        $customerReplyResponse->assertRedirect(route('account.tickets.show', $ticket));
        $this->assertDatabaseHas('ticket_replies', [
            'support_ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'message' => 'Dạ vâng cảm ơn shop, tôi sẽ đợi ở nhà.',
        ]);
    }

    public function test_admin_can_update_ticket_status(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $ticket = SupportTicket::create([
            'ticket_code' => 'TK-STATUS99',
            'user_id' => $customer->id,
            'subject' => 'Đổi màu ghế sofa',
            'category' => 'order',
            'priority' => 'normal',
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.tickets.update-status', $ticket), [
            'status' => 'resolved',
        ]);

        $response->assertRedirect(route('admin.tickets.show', $ticket));
        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'status' => 'resolved',
        ]);
    }
}
