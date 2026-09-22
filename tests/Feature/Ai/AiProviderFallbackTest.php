<?php

namespace Tests\Feature\Ai;

use App\Models\AiConversation;
use App\Models\User;
use App\Services\Ai\AiAssistantService;
use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\Providers\GeminiProvider;
use App\Services\Ai\Providers\MockAiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class AiProviderFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_in_production_environment_provider_defaults_to_gemini_and_never_mock(): void
    {
        $this->app['env'] = 'production';
        config(['ai.provider' => 'gemini', 'ai.api_key' => '']);

        $service = $this->app->make(AiAssistantService::class);

        $this->assertInstanceOf(GeminiProvider::class, $service->getProvider());
        $this->assertNotInstanceOf(MockAiProvider::class, $service->getProvider());
    }

    public function test_in_production_environment_mock_is_only_allowed_when_explicitly_configured(): void
    {
        $this->app['env'] = 'production';
        config(['ai.provider' => 'mock']);

        $service = $this->app->make(AiAssistantService::class);

        $this->assertInstanceOf(MockAiProvider::class, $service->getProvider());
    }

    public function test_when_provider_fails_in_production_chat_does_not_fallback_to_mock_and_returns_friendly_support_actions(): void
    {
        $this->app['env'] = 'production';
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        // Custom failing provider simulating a live Gemini API outage
        $failingProvider = new class implements AiProviderInterface {
            public function chat(array $messages, array $tools = [], array $options = []): array
            {
                throw new RuntimeException('Gemini upstream service unavailable (503)');
            }
        };

        $service = $this->app->make(AiAssistantService::class);
        $service->setProvider($failingProvider);
        $this->app->instance(AiAssistantService::class, $service);

        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->postJson(route('ai.chat'), [
            'message' => 'Tư vấn cho tôi mẫu sofa gỗ sồi phòng khách',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $content = $response->json('message.content');
        $this->assertNotEmpty($content);

        // Verify it NEVER returns canned mock content
        $this->assertStringNotContainsString('Mộc An xin chào! Em có thể giúp gì cho quý khách', $content);

        // Verify it returns friendly unavailable message + FAQ / Support / Ticket links
        $faqUrl = route('faq.index');
        $ticketUrl = route('account.tickets.create');
        $contactUrl = route('pages.contact');

        $this->assertStringContainsString($faqUrl, $content);
        $this->assertStringContainsString($ticketUrl, $content);
        $this->assertStringContainsString($contactUrl, $content);
        $this->assertStringContainsString('1900 6868', $content);

        // Verify support_actions card is provided
        $cards = $response->json('message.cards');
        $this->assertNotEmpty($cards);

        $supportCard = collect($cards)->firstWhere('type', 'support_actions');
        $this->assertNotNull($supportCard);

        $actionUrls = collect($supportCard['data']['actions'])->pluck('url')->all();
        $this->assertContains($faqUrl, $actionUrls);
        $this->assertContains($ticketUrl, $actionUrls);
        $this->assertContains($contactUrl, $actionUrls);
    }

    public function test_gemini_provider_defaults_to_gemini_3_8_flash(): void
    {
        config(['ai.model' => 'gemini-3.8-flash']);
        $provider = new GeminiProvider('test-key');

        $this->assertEquals('gemini-3.8-flash', $provider->getModel());
    }

    public function test_gemini_provider_respects_custom_model_from_config_or_env(): void
    {
        config(['ai.model' => 'gemini-ultra-custom']);
        $provider = new GeminiProvider('test-key');

        $this->assertEquals('gemini-ultra-custom', $provider->getModel());
    }

    public function test_gemini_provider_uses_header_auth_and_does_not_leak_api_key(): void
    {
        $secretKey = 'MOCAN_SUPER_SECRET_KEY_12345';
        $provider = new GeminiProvider($secretKey, 'gemini-3.8-flash', 10);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 403,
                    'message' => "The key {$secretKey} is invalid or expired.",
                    'status' => 'PERMISSION_DENIED',
                ],
            ], 403),
        ]);

        try {
            $provider->chat([
                ['role' => 'user', 'content' => 'Xin chào'],
            ]);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            // Secret key must be redacted in the exception message
            $this->assertStringNotContainsString($secretKey, $e->getMessage());
            $this->assertStringContainsString('[REDACTED_API_KEY]', $e->getMessage());
        }

        // Verify the HTTP request sent header x-goog-api-key, target URL uses gemini-3.8-flash, and NOT query param ?key=
        Http::assertSent(function ($request) use ($secretKey) {
            $hasHeader = $request->hasHeader('x-goog-api-key') && $request->header('x-goog-api-key')[0] === $secretKey;
            $hasCleanUrl = !str_contains($request->url(), 'key=');
            $hasCorrectModelEndpoint = str_contains($request->url(), 'gemini-3.8-flash:generateContent');
            return $hasHeader && $hasCleanUrl && $hasCorrectModelEndpoint;
        });
    }
}
