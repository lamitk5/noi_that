<?php

namespace Tests\Feature;

use Tests\TestCase;

class StaticPagesTest extends TestCase
{
    public function test_faq_page_loads_successfully(): void
    {
        $response = $this->get(route('pages.faq'));

        $response->assertStatus(200);
        $response->assertSee('Câu hỏi thường gặp');
        $response->assertSee('Mộc An sử dụng những loại gỗ tự nhiên nào?');
    }

    public function test_warranty_page_loads_successfully(): void
    {
        $response = $this->get(route('pages.warranty'));

        $response->assertStatus(200);
        $response->assertSee('Chính sách bảo hành & bảo trì');
        $response->assertSee('24 tháng');
    }

    public function test_return_policy_page_loads_successfully(): void
    {
        $response = $this->get(route('pages.return'));

        $response->assertStatus(200);
        $response->assertSee('Chính sách đổi trả & hoàn tiền');
        $response->assertSee('7 ngày');
    }

    public function test_contact_page_loads_successfully(): void
    {
        $response = $this->get(route('pages.contact'));

        $response->assertStatus(200);
        $response->assertSee('Showroom & Trụ sở Mộc An', false);
        $response->assertSee('0901 234 567');
    }
}
