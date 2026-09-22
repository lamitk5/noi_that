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

    public function test_storefront_contains_only_one_floating_support_launcher(): void
    {
        $response = $this->get(route('home'));
        $response->assertOk();

        $content = $response->getContent();

        // Must contain AI assistant launcher
        $this->assertStringContainsString('Mở Trợ lý AI Mộc An', $content);
        $this->assertStringContainsString('ai-assistant-root', $content);

        // Must NOT contain duplicate legacy floating live support launcher
        $this->assertStringNotContainsString('live-support-widget', $content);
        $this->assertStringNotContainsString('support-launcher-btn', $content);
        $this->assertStringNotContainsString('Hỗ trợ trực tuyến Mộc An', $content);
    }

    public function test_gemini_provider_parses_text_and_function_calls_correctly(): void
    {
        $secretKey = 'MOCAN_KEY_XYZ';
        $provider = new GeminiProvider($secretKey, 'gemini-3.8-flash', 10);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Dạ, em tìm thấy sofa phù hợp:'],
                                [
                                    'functionCall' => [
                                        'name' => 'search_products',
                                        'args' => ['query' => 'sofa', 'max_price' => 15000000],
                                    ],
                                ],
                            ],
                        ],
                        'finishReason' => 'STOP',
                    ],
                ],
                'usageMetadata' => [
                    'promptTokenCount' => 120,
                    'candidatesTokenCount' => 45,
                    'totalTokenCount' => 165,
                ],
            ], 200),
        ]);

        $result = $provider->chat([
            ['role' => 'user', 'content' => 'Tìm sofa dưới 15 triệu'],
        ]);

        $this->assertEquals('Dạ, em tìm thấy sofa phù hợp:', $result['content']);
        $this->assertCount(1, $result['tool_calls']);
        $this->assertEquals('search_products', $result['tool_calls'][0]['name']);
        $this->assertEquals('sofa', $result['tool_calls'][0]['arguments']['query']);
        $this->assertEquals(15000000, $result['tool_calls'][0]['arguments']['max_price']);
        $this->assertEquals(165, $result['usage']['total_tokens']);
    }

    public function test_gemini_provider_formats_tool_response_with_role_user_and_preserves_thought_signature(): void
    {
        $provider = new GeminiProvider('test-api-key', 'gemini-3.6-flash', 10);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Bàn trà gỗ Tần Bì rất hợp phòng khách hiện đại.'],
                            ],
                        ],
                        'finishReason' => 'STOP',
                    ],
                ],
                'usageMetadata' => ['totalTokenCount' => 150],
            ], 200),
        ]);

        $messages = [
            ['role' => 'user', 'content' => 'Tìm bàn phòng khách'],
            [
                'role' => 'assistant',
                'content' => '',
                'tool_calls' => [
                    [
                        'name' => 'search_products',
                        'arguments' => ['query' => 'bàn'],
                        'thoughtSignature' => 'sig_abc_123',
                    ],
                ],
            ],
            [
                'role' => 'tool',
                'name' => 'search_products',
                'content' => 'Tìm thấy Bàn trà gỗ Tần Bì',
            ],
        ];

        $res = $provider->chat($messages);

        $this->assertEquals('Bàn trà gỗ Tần Bì rất hợp phòng khách hiện đại.', $res['content']);

        Http::assertSent(function ($request) {
            $data = $request->data();
            $contents = $data['contents'] ?? [];

            // Must have 3 turns: user -> model -> user (tool response)
            $this->assertCount(3, $contents);

            // Turn 1: user
            $this->assertEquals('user', $contents[0]['role']);

            // Turn 2: model with thoughtSignature preserved
            $this->assertEquals('model', $contents[1]['role']);
            $this->assertEquals('sig_abc_123', $contents[1]['parts'][0]['thoughtSignature']);

            // Turn 3: tool result MUST have role 'user' for Gemini v1beta, NOT 'function'
            $this->assertEquals('user', $contents[2]['role']);
            $this->assertArrayHasKey('functionResponse', $contents[2]['parts'][0]);
            $this->assertEquals('search_products', $contents[2]['parts'][0]['functionResponse']['name']);

            return true;
        });
    }

    public function test_gemini_provider_merges_multiple_tool_responses_into_single_user_turn(): void
    {
        $provider = new GeminiProvider('test-api-key', 'gemini-3.6-flash', 10);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [['text' => 'Đã so sánh 2 sản phẩm']],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $messages = [
            ['role' => 'user', 'content' => 'So sánh 2 sản phẩm'],
            [
                'role' => 'assistant',
                'content' => '',
                'tool_calls' => [
                    ['name' => 'search_products', 'arguments' => []],
                    ['name' => 'check_inventory', 'arguments' => []],
                ],
            ],
            ['role' => 'tool', 'name' => 'search_products', 'content' => 'Kết quả 1'],
            ['role' => 'tool', 'name' => 'check_inventory', 'content' => 'Kết quả 2'],
        ];

        $provider->chat($messages);

        Http::assertSent(function ($request) {
            $data = $request->data();
            $contents = $data['contents'] ?? [];

            // Turns must alternate: user -> model -> user (holding both tool responses)
            $this->assertCount(3, $contents);
            $this->assertEquals('user', $contents[2]['role']);
            $this->assertCount(2, $contents[2]['parts']);
            $this->assertEquals('search_products', $contents[2]['parts'][0]['functionResponse']['name']);
            $this->assertEquals('check_inventory', $contents[2]['parts'][1]['functionResponse']['name']);

            return true;
        });
    }
}
