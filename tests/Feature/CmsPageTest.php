<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_default_fallback_pages(): void
    {
        // About page with default content
        $aboutResponse = $this->get(route('pages.about'));
        $aboutResponse->assertOk();
        $aboutResponse->assertSee('Giới thiệu về Mộc An');
        $aboutResponse->assertSee('Không gian nội thất gỗ tinh tế, ấm cúng và bền vững');

        // Warranty policy page with default content
        $warrantyResponse = $this->get(route('pages.warranty-policy'));
        $warrantyResponse->assertOk();
        $warrantyResponse->assertSee('Chính sách bảo hành');
    }

    public function test_can_view_database_stored_cms_page(): void
    {
        CmsPage::create([
            'title' => 'Hướng Dẫn Vệ Sinh Gỗ Tự Nhiên',
            'slug' => 'huong-dan-ve-sinh-go-tu-nhien',
            'content' => 'Tránh để đồ gỗ dưới ánh nắng trực tiếp và sử dụng khăn mềm lau khô.',
            'is_active' => true,
        ]);

        $response = $this->get(route('pages.show', 'huong-dan-ve-sinh-go-tu-nhien'));
        $response->assertOk();
        $response->assertSee('Hướng Dẫn Vệ Sinh Gỗ Tự Nhiên');
        $response->assertSee('Tránh để đồ gỗ dưới ánh nắng trực tiếp');
    }

    public function test_customer_can_submit_contact_form(): void
    {
        $response = $this->post(route('pages.contact.store'), [
            'name' => 'Nguyễn Hải Đăng',
            'email' => 'haidang@example.com',
            'phone' => '0987654321',
            'subject' => 'Tư vấn combo phòng ngủ phong cách Bắc Âu',
            'message' => 'Shop vui lòng gửi báo giá combo giường và tủ áo sồi trắng.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_manage_cms_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.pages.store'), [
            'title' => 'Chính sách bảo mật thông tin',
            'slug' => 'chinh-sach-bao-mat',
            'content' => 'Mộc An cam kết bảo vệ dữ liệu khách hàng tuyệt đối.',
            'meta_title' => 'Bảo mật Mộc An',
            'meta_description' => 'Chính sách an toàn dữ liệu khách hàng',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.pages.index'));
        $this->assertDatabaseHas('cms_pages', [
            'slug' => 'chinh-sach-bao-mat',
            'title' => 'Chính sách bảo mật thông tin',
        ]);

        $page = CmsPage::where('slug', 'chinh-sach-bao-mat')->first();

        $updateResponse = $this->actingAs($admin)->put(route('admin.pages.update', $page), [
            'title' => 'Chính sách bảo mật thông tin & Quyền riêng tư',
            'slug' => 'chinh-sach-bao-mat-va-quyen-rieng-tu',
            'content' => 'Nội dung cập nhật mới.',
            'is_active' => 1,
        ]);

        $updateResponse->assertRedirect(route('admin.pages.index'));
        $this->assertDatabaseHas('cms_pages', [
            'id' => $page->id,
            'slug' => 'chinh-sach-bao-mat-va-quyen-rieng-tu',
        ]);
    }
}
