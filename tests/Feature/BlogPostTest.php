<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_blog_index_and_post_detail(): void
    {
        $category = PostCategory::create([
            'name' => 'Xu hướng nội thất',
            'slug' => 'xu-huong-noi-that',
        ]);

        $post = Post::create([
            'title' => 'Top 5 mẫu ghế thư giãn phong cách Wabi Sabi 2026',
            'slug' => 'top-5-mau-ghe-thu-gian-wabi-sabi',
            'post_category_id' => $category->id,
            'excerpt' => 'Khám phá sự tối giản và mộc mạc từ phong cách Wabi Sabi.',
            'content' => 'Chi tiết từng mẫu ghế thư giãn bằng mây tre và gỗ sồi tự nhiên...',
            'view_count' => 5,
            'is_published' => true,
        ]);

        // Index page
        $indexResponse = $this->get(route('posts.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Top 5 mẫu ghế thư giãn phong cách Wabi Sabi 2026');

        // Show page & check view_count increment
        $showResponse = $this->get(route('posts.show', $post->slug));
        $showResponse->assertOk();
        $showResponse->assertSee('Chi tiết từng mẫu ghế thư giãn bằng mây tre');

        $post->refresh();
        $this->assertEquals(6, $post->view_count);
    }

    public function test_admin_can_manage_blog_posts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.posts.store'), [
            'title' => 'Bí quyết bảo dưỡng bàn ăn gỗ sồi Nga',
            'slug' => 'bi-quyet-bao-duong-ban-an-go-soi-nga',
            'excerpt' => 'Cách lau chùi bảo vệ lớp sơn PU láng mịn.',
            'content' => 'Nội dung chi tiết về bảo quản đồ gỗ gia đình.',
            'is_published' => 1,
        ]);

        $response->assertRedirect(route('admin.posts.index'));
        $this->assertDatabaseHas('posts', [
            'title' => 'Bí quyết bảo dưỡng bàn ăn gỗ sồi Nga',
            'slug' => 'bi-quyet-bao-duong-ban-an-go-soi-nga',
        ]);

        $post = Post::where('slug', 'bi-quyet-bao-duong-ban-an-go-soi-nga')->first();

        $updateResponse = $this->actingAs($admin)->put(route('admin.posts.update', $post), [
            'title' => 'Bí quyết bảo dưỡng bàn ăn gỗ sồi Mỹ cao cấp',
            'slug' => 'bi-quyet-bao-duong-ban-an-go-soi-my',
            'content' => 'Nội dung cập nhật mới.',
            'is_published' => 1,
        ]);

        $updateResponse->assertRedirect(route('admin.posts.index'));
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Bí quyết bảo dưỡng bàn ăn gỗ sồi Mỹ cao cấp',
        ]);

        $deleteResponse = $this->actingAs($admin)->delete(route('admin.posts.destroy', $post));
        $deleteResponse->assertRedirect(route('admin.posts.index'));
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }
}
