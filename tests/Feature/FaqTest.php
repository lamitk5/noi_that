<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_faqs_page(): void
    {
        Faq::create([
            'category' => 'delivery',
            'question' => 'Thời gian giao hàng mất bao lâu?',
            'answer' => 'Giao hàng nội thành Hà Nội trong 24 giờ.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->get(route('faq.index'));

        $response->assertOk();
        $response->assertSee('Thời gian giao hàng mất bao lâu?');
        $response->assertSee('Giao hàng nội thành Hà Nội trong 24 giờ.');
    }

    public function test_customer_can_filter_faqs_by_category_and_search(): void
    {
        Faq::create([
            'category' => 'warranty',
            'question' => 'Chính sách bảo hành bàn ăn sồi?',
            'answer' => 'Bảo hành 24 tháng cho mọi lỗi cong vênh mối mọt.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Faq::create([
            'category' => 'payment',
            'question' => 'Tôi có thể thanh toán qua MoMo không?',
            'answer' => 'Có, chúng tôi hỗ trợ MoMo và VNPAY.',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $searchResponse = $this->get(route('faq.index', ['q' => 'MoMo']));
        $searchResponse->assertOk();
        $searchResponse->assertSee('Tôi có thể thanh toán qua MoMo không?');
        $searchResponse->assertDontSee('Chính sách bảo hành bàn ăn sồi?');

        $filterResponse = $this->get(route('faq.index', ['category' => 'warranty']));
        $filterResponse->assertOk();
        $filterResponse->assertSee('Chính sách bảo hành bàn ăn sồi?');
        $filterResponse->assertDontSee('Tôi có thể thanh toán qua MoMo không?');
    }

    public function test_admin_can_manage_faqs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.faqs.store'), [
            'category' => 'custom_order',
            'question' => 'Mộc An có nhận đặt làm theo kích thước riêng không?',
            'answer' => 'Có, xưởng mộc nhận thiết kế và thi công theo số đo thực tế.',
            'sort_order' => 10,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.faqs.index'));
        $this->assertDatabaseHas('faqs', [
            'question' => 'Mộc An có nhận đặt làm theo kích thước riêng không?',
            'category' => 'custom_order',
        ]);

        $faq = Faq::where('category', 'custom_order')->first();

        $updateResponse = $this->actingAs($admin)->put(route('admin.faqs.update', $faq), [
            'category' => 'custom_order',
            'question' => 'Mộc An có nhận may đo theo yêu cầu không?',
            'answer' => 'Có, chúng tôi phục vụ may đo trọn gói.',
            'sort_order' => 5,
            'is_active' => 1,
        ]);

        $updateResponse->assertRedirect(route('admin.faqs.index'));
        $this->assertDatabaseHas('faqs', [
            'id' => $faq->id,
            'question' => 'Mộc An có nhận may đo theo yêu cầu không?',
        ]);

        $deleteResponse = $this->actingAs($admin)->delete(route('admin.faqs.destroy', $faq));
        $deleteResponse->assertRedirect(route('admin.faqs.index'));
        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }
}
