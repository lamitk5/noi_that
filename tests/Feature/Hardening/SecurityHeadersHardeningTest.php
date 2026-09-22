<?php

namespace Tests\Feature\Hardening;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHeadersHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_responses_contain_required_security_headers(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_spoofed_or_executable_file_upload_is_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $category = Category::create(['name' => 'Bàn', 'slug' => 'ban-' . uniqid(), 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Bàn Gỗ Trắc',
            'slug' => 'ban-go-trac-' . uniqid(),
            'sku' => 'BGT-' . strtoupper(uniqid()),
            'base_price' => 5000000,
            'is_active' => true,
        ]);

        // 1. PHP script disguised as .jpg
        $fakeScript = UploadedFile::fake()->create('malicious.php.jpg', 100, 'text/x-php');

        $response = $this->actingAs($admin)
            ->post(route('admin.products.images.store', $product->id), [
                'image' => $fakeScript,
            ]);

        $response->assertSessionHasErrors(['image']);

        // 2. Executable .exe file
        $fakeExe = UploadedFile::fake()->create('virus.exe', 200, 'application/x-msdownload');
        $responseExe = $this->actingAs($admin)
            ->post(route('admin.products.images.store', $product->id), [
                'image' => $fakeExe,
            ]);

        $responseExe->assertSessionHasErrors(['image']);
    }

    public function test_user_generated_content_escapes_xss_payloads(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        // 1. Support Ticket XSS
        $xssPayload = '<script>alert("xss-attack")</script>';

        $ticket = SupportTicket::create([
            'user_id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'subject' => 'Hỗ trợ ' . $xssPayload,
            'category' => 'general',
            'priority' => 'normal',
            'message' => 'Nội dung chứa ' . $xssPayload,
            'status' => SupportTicket::STATUS_OPEN,
            'last_reply_at' => now(),
        ]);

        $ticketResponse = $this->actingAs($customer)->get(route('account.tickets.show', $ticket));
        $ticketResponse->assertStatus(200);
        $ticketContent = $ticketResponse->getContent();

        // Must not contain raw executable unescaped <script>alert("xss-attack")</script>
        $this->assertStringNotContainsString('<script>alert("xss-attack")</script>', $ticketContent);
        // Must contain escaped HTML entities
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;xss-attack&quot;)&lt;/script&gt;', $ticketContent);
    }
}
