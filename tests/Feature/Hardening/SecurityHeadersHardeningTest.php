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
        $response->assertHeader('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", (string) $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline' 'unsafe-eval'", (string) $response->headers->get('Content-Security-Policy'));
    }

    public function test_hsts_is_sent_on_secure_requests(): void
    {
        $response = $this->get('https://localhost/');
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
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

    public function test_csp_without_hot_file_contains_no_vite_dev_origins(): void
    {
        $hotFile = public_path('hot');
        if (file_exists($hotFile)) {
            unlink($hotFile);
        }

        $response = $this->get(route('home'));
        $response->assertStatus(200);

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringNotContainsString('[::1]', $csp);
        $this->assertStringNotContainsString('localhost', $csp);
        $this->assertStringNotContainsString('5173', $csp);
        $this->assertStringNotContainsString('5174', $csp);
        $this->assertStringNotContainsString('ws:', $csp);
    }

    public function test_csp_with_dynamic_vite_hot_url_allows_exact_http_and_ws_origins(): void
    {
        $hotFile = public_path('hot');
        try {
            file_put_contents($hotFile, "http://[::1]:5174\n");

            $response = $this->get(route('home'));
            $response->assertStatus(200);

            $csp = (string) $response->headers->get('Content-Security-Policy');

            // script-src contains hot origin
            $this->assertMatchesRegularExpression("/script-src[^;]*http:\/\/\[::1\]:5174/", $csp);

            // style-src contains hot origin
            $this->assertMatchesRegularExpression("/style-src[^;]*http:\/\/\[::1\]:5174/", $csp);

            // connect-src contains both HTTP and WS origins
            $this->assertMatchesRegularExpression("/connect-src[^;]*http:\/\/\[::1\]:5174/", $csp);
            $this->assertMatchesRegularExpression("/connect-src[^;]*ws:\/\/\[::1\]:5174/", $csp);

            // font-src & img-src contain hot origin
            $this->assertMatchesRegularExpression("/font-src[^;]*http:\/\/\[::1\]:5174/", $csp);
            $this->assertMatchesRegularExpression("/img-src[^;]*http:\/\/\[::1\]:5174/", $csp);

            // existing security headers remain intact
            $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
            $response->assertHeader('X-Content-Type-Options', 'nosniff');
            $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        } finally {
            if (file_exists($hotFile)) {
                unlink($hotFile);
            }
        }
    }

    public function test_csp_supports_arbitrary_dynamic_vite_ports(): void
    {
        $hotFile = public_path('hot');
        try {
            file_put_contents($hotFile, "http://localhost:5199");

            $response = $this->get(route('home'));
            $response->assertStatus(200);

            $csp = (string) $response->headers->get('Content-Security-Policy');

            $this->assertStringContainsString('http://localhost:5199', $csp);
            $this->assertStringContainsString('ws://localhost:5199', $csp);
            $this->assertStringNotContainsString('5173', $csp);
            $this->assertStringNotContainsString('5174', $csp);
        } finally {
            if (file_exists($hotFile)) {
                unlink($hotFile);
            }
        }
    }

    public function test_production_environment_ignores_hot_file_and_maintains_strict_csp(): void
    {
        $hotFile = public_path('hot');
        $originalEnv = $this->app['env'];
        try {
            file_put_contents($hotFile, "http://[::1]:5174\n");
            $this->app['env'] = 'production';

            $response = $this->get(route('home'));
            $response->assertStatus(200);

            $csp = (string) $response->headers->get('Content-Security-Policy');
            $this->assertStringNotContainsString('[::1]', $csp);
            $this->assertStringNotContainsString('localhost', $csp);
            $this->assertStringNotContainsString('5174', $csp);
            $this->assertStringNotContainsString('ws:', $csp);

            // In production, HSTS is also added
            $response->assertHeader('Strict-Transport-Security');
        } finally {
            $this->app['env'] = $originalEnv;
            if (file_exists($hotFile)) {
                unlink($hotFile);
            }
        }
    }
}
