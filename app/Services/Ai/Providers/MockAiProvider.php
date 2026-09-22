<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Contracts\AiProviderInterface;

class MockAiProvider implements AiProviderInterface
{
    protected static array $queue = [];

    public static function queueResponse(string|array $response): void
    {
        if (is_string($response)) {
            self::$queue[] = [
                'content' => $response,
                'tool_calls' => [],
                'usage' => [
                    'prompt_tokens' => 15,
                    'completion_tokens' => 25,
                    'total_tokens' => 40,
                ],
                'finish_reason' => 'stop',
            ];
        } else {
            self::$queue[] = $response;
        }
    }

    public static function queueToolCall(string $toolName, array $args, string $content = ''): void
    {
        self::$queue[] = [
            'content' => $content,
            'tool_calls' => [
                [
                    'name' => $toolName,
                    'arguments' => $args,
                ]
            ],
            'usage' => [
                'prompt_tokens' => 20,
                'completion_tokens' => 10,
                'total_tokens' => 30,
            ],
            'finish_reason' => 'tool_calls',
        ];
    }

    public static function clearQueue(): void
    {
        self::$queue = [];
    }

    public function chat(array $messages, array $tools = [], array $options = []): array
    {
        if (!empty(self::$queue)) {
            return array_shift(self::$queue);
        }

        // Default smart fallback reply based on last message
        $lastMsg = end($messages)['content'] ?? '';

        return [
            'content' => "Dạ Mộc An xin chào! Em có thể hỗ trợ quý khách về: '{$lastMsg}' ạ.",
            'tool_calls' => [],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 20,
                'total_tokens' => 30,
            ],
            'finish_reason' => 'stop',
        ];
    }
}
