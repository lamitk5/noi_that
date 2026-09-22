<?php

namespace Tests\Feature\Hardening;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountResourceHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function createCustomer(): User
    {
        return User::factory()->create([
            'role' => 'customer',
        ]);
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
        ]);
    }

    public function test_updating_or_deleting_address_of_another_user_is_forbidden_403(): void
    {
        $userA = $this->createCustomer();
        $userB = $this->createCustomer();

        $addressA = UserAddress::create([
            'user_id' => $userA->id,
            'recipient_name' => 'User A',
            'phone' => '0901234567',
            'address_line' => '123 Đường A',
            'city' => 'TP.HCM',
            'is_default' => true,
        ]);

        // User B attempts to update User A's address
        $updateResponse = $this->actingAs($userB)
            ->put(route('account.addresses.update', $addressA->id), [
                'recipient_name' => 'Hacker B',
                'phone' => '0909999999',
                'address_line' => '999 Đường Hack',
                'city' => 'Hà Nội',
            ]);

        $updateResponse->assertStatus(403);
        $this->assertEquals('User A', $addressA->fresh()->recipient_name);

        // User B attempts to delete User A's address
        $deleteResponse = $this->actingAs($userB)
            ->delete(route('account.addresses.destroy', $addressA->id));

        $deleteResponse->assertStatus(403);
        $this->assertDatabaseHas('user_addresses', ['id' => $addressA->id]);

        // User B attempts to set User A's address as default
        $setDefaultResponse = $this->actingAs($userB)
            ->post(route('account.addresses.set-default', $addressA->id));

        $setDefaultResponse->assertStatus(403);
    }

    public function test_setting_default_address_unsets_only_own_addresses(): void
    {
        $userA = $this->createCustomer();
        $userB = $this->createCustomer();

        $addrA1 = UserAddress::create([
            'user_id' => $userA->id,
            'recipient_name' => 'User A 1',
            'phone' => '0901234567',
            'address_line' => '123 A1',
            'city' => 'TP.HCM',
            'is_default' => true,
        ]);

        $addrA2 = UserAddress::create([
            'user_id' => $userA->id,
            'recipient_name' => 'User A 2',
            'phone' => '0901234567',
            'address_line' => '123 A2',
            'city' => 'TP.HCM',
            'is_default' => false,
        ]);

        $addrB1 = UserAddress::create([
            'user_id' => $userB->id,
            'recipient_name' => 'User B 1',
            'phone' => '0908888888',
            'address_line' => '456 B1',
            'city' => 'Đà Nẵng',
            'is_default' => true,
        ]);

        // User A sets addrA2 as default
        $response = $this->actingAs($userA)
            ->post(route('account.addresses.set-default', $addrA2->id));

        $response->assertRedirect(route('account.addresses.index'));

        // addrA1 is no longer default, addrA2 is default
        $this->assertFalse((bool) $addrA1->fresh()->is_default);
        $this->assertTrue((bool) $addrA2->fresh()->is_default);

        // User B's address remains default (unaffected)
        $this->assertTrue((bool) $addrB1->fresh()->is_default);
    }

    public function test_submitting_ticket_reply_for_another_users_ticket_is_forbidden_403(): void
    {
        $userA = $this->createCustomer();
        $userB = $this->createCustomer();

        $ticket = SupportTicket::create([
            'user_id' => $userA->id,
            'name' => $userA->name,
            'email' => $userA->email,
            'subject' => 'Cần hỗ trợ về kích thước',
            'category' => 'product',
            'priority' => 'normal',
            'message' => 'Tư vấn giúp tôi kích thước bàn ăn',
            'status' => SupportTicket::STATUS_OPEN,
            'last_reply_at' => now(),
        ]);

        // User B attempts to view User A's ticket
        $viewResponse = $this->actingAs($userB)
            ->get(route('account.tickets.show', $ticket));

        $viewResponse->assertStatus(403);

        // User B attempts to reply to User A's ticket
        $replyResponse = $this->actingAs($userB)
            ->post(route('account.tickets.reply', $ticket), [
                'message' => 'Spam reply from stranger',
            ]);

        $replyResponse->assertStatus(403);
        $this->assertEquals(0, $ticket->replies()->where('user_id', $userB->id)->count());
    }

    public function test_review_update_and_delete_are_strictly_guarded(): void
    {
        $author = $this->createCustomer();
        $stranger = $this->createCustomer();
        $admin = $this->createAdmin();

        $category = Category::create(['name' => 'Ghế', 'slug' => 'ghe-' . uniqid(), 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Ghế Ăn Đan Mây ' . uniqid(),
            'slug' => 'ghe-an-' . uniqid(),
            'sku' => 'GA-' . strtoupper(uniqid()),
            'base_price' => 500000,
            'is_active' => true,
        ]);

        $order = \App\Models\Order::create([
            'user_id' => $author->id,
            'order_code' => 'ORD-REV-' . uniqid(),
            'customer_name' => $author->name,
            'customer_phone' => '0901234567',
            'shipping_address' => 'Hà Nội',
            'total_price' => 500000,
            'shipping_fee' => 0,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'order_status' => \App\Models\Order::STATUS_COMPLETED,
        ]);

        $orderItem = $order->items()->create([
            'product_name' => $product->name,
            'variant_info' => 'Tiêu chuẩn',
            'quantity' => 1,
            'price' => 500000,
        ]);

        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $author->id,
            'order_item_id' => $orderItem->id,
            'rating' => 5,
            'comment' => 'Sản phẩm hoàn thiện rất tỉ mỉ, rất ưng ý!',
        ]);

        // 1. Stranger cannot update review -> 403
        $strangerUpdate = $this->actingAs($stranger)
            ->patch(route('reviews.update', $review->id), [
                'rating' => 1,
                'comment' => 'Hacked review!',
            ]);
        $strangerUpdate->assertStatus(403);
        $this->assertEquals(5, $review->fresh()->rating);

        // 2. Stranger cannot delete review -> 403
        $strangerDelete = $this->actingAs($stranger)
            ->delete(route('reviews.destroy', $review->id));
        $strangerDelete->assertStatus(403);
        $this->assertDatabaseHas('reviews', ['id' => $review->id]);

        // 3. Author can update review
        $authorUpdate = $this->actingAs($author)
            ->patch(route('reviews.update', $review->id), [
                'rating' => 4,
                'comment' => 'Dùng 1 tháng vẫn rất ổn.',
            ]);
        $authorUpdate->assertRedirect();
        $this->assertEquals(4, $review->fresh()->rating);

        // 4. Admin can delete review
        $adminDelete = $this->actingAs($admin)
            ->delete(route('reviews.destroy', $review->id));
        $adminDelete->assertRedirect();
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }
}
