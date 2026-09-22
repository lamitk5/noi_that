<?php

namespace Tests\Feature\Ai;

use App\Models\CmsPage;
use App\Models\Faq;
use App\Models\Post;
use App\Services\Ai\KnowledgeRetriever;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiKnowledgeRetrieverTest extends TestCase
{
    use RefreshDatabase;

    protected KnowledgeRetriever $retriever;

    protected function setUp(): void
    {
        parent::setUp();
        $this->retriever = app(KnowledgeRetriever::class);

        // Seed FAQ
        Faq::create([
            'question' => 'Chính sách bảo hành đồ gỗ tại Mộc An thế nào?',
            'answer' => 'Tất cả sản phẩm nội thất gỗ sồi và óc chó tại Mộc An được bảo hành kết cấu 36 tháng và bảo trì trọn đời.',
            'category' => 'Bảo hành',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Inactive FAQ (should never be retrieved)
        Faq::create([
            'question' => 'Khuyến mãi cũ đã hết hạn?',
            'answer' => 'Mã giảm giá cũ bí mật không còn sử dụng.',
            'category' => 'Ưu đãi',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        // Seed CMS Page
        CmsPage::create([
            'title' => 'Chính sách đổi trả sản phẩm',
            'slug' => 'chinh-sach-doi-tra',
            'content' => 'Mộc An hỗ trợ đổi trả miễn phí trong vòng 7 ngày nếu có lỗi kỹ thuật từ nhà sản xuất hoặc giao sai mẫu mã.',
            'is_active' => true,
        ]);

        // Seed Post
        Post::create([
            'title' => 'Bí quyết bảo quản sofa gỗ sồi luôn sáng bóng',
            'slug' => 'bi-quyet-bao-quan-sofa-go-soi',
            'content' => 'Gỗ sồi tự nhiên cần tránh ánh nắng gắt trực tiếp và lau chùi định kỳ bằng khăn mềm ẩm.',
            'is_published' => true,
        ]);
    }

    public function test_retrieves_relevant_faq_for_warranty_query(): void
    {
        $chunks = $this->retriever->retrieve('Chính sách bảo hành đồ gỗ ra sao?', 4);

        $this->assertNotEmpty($chunks);
        $first = $chunks[0];
        $this->assertEquals('faq', $first['source']);
        $this->assertStringContainsString('36 tháng', $first['content']);
        $this->assertGreaterThan(0, $first['score']);
    }

    public function test_retrieves_cms_page_for_return_policy_query(): void
    {
        $chunks = $this->retriever->retrieve('Tôi muốn đổi trả sản phẩm thì mất bao lâu?', 4);

        $this->assertNotEmpty($chunks);
        $cmsChunk = collect($chunks)->firstWhere('source', 'cms');
        $this->assertNotNull($cmsChunk);
        $this->assertStringContainsString('7 ngày', $cmsChunk['content']);
    }

    public function test_retrieves_blog_post_for_wood_care_query(): void
    {
        $chunks = $this->retriever->retrieve('Cách bảo quản sofa gỗ sồi phòng khách', 4);

        $this->assertNotEmpty($chunks);
        $postChunk = collect($chunks)->firstWhere('source', 'post');
        $this->assertNotNull($postChunk);
        $this->assertStringContainsString('ánh nắng gắt', $postChunk['content']);
    }

    public function test_does_not_retrieve_inactive_faq(): void
    {
        $chunks = $this->retriever->retrieve('Khuyến mãi cũ đã hết hạn bí mật', 4);

        $contents = collect($chunks)->pluck('content')->implode(' ');
        $this->assertStringNotContainsString('bí mật không còn sử dụng', $contents);
    }

    public function test_format_for_prompt_wraps_in_safe_anti_injection_header(): void
    {
        $chunks = $this->retriever->retrieve('bảo hành sofa', 2);
        $formatted = $this->retriever->formatForPrompt($chunks);

        $this->assertStringContainsString('=== DỮ LIỆU KIẾN THỨC MỘC AN', $formatted);
        $this->assertStringContainsString('=== KẾT THÚC DỮ LIỆU KIẾN THỨC ===', $formatted);
    }

    public function test_empty_query_returns_empty_results(): void
    {
        $chunks = $this->retriever->retrieve('', 4);
        $this->assertEmpty($chunks);

        $formatted = $this->retriever->formatForPrompt([]);
        $this->assertEmpty($formatted);
    }
}
