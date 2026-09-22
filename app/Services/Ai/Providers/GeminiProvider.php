<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiProvider implements AiProviderInterface
{
    protected string $apiKey;
    protected string $model;
    protected int $timeout;

    public function __construct(?string $apiKey = null, ?string $model = null, int $timeout = 30)
    {
        $this->apiKey = $apiKey ?? (string) config('ai.api_key', '');
        $this->model = $model ?? (string) config('ai.model', 'gemini-1.5-flash');
        $this->timeout = $timeout > 0 ? $timeout : (int) config('ai.timeout', 30);
    }

    public function chat(array $messages, array $tools = [], array $options = []): array
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }

        $model = $options['model'] ?? $this->model;
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($this->apiKey);

        $systemPrompt = '';
        $contents = [];

        foreach ($messages as $msg) {
            $role = $msg['role'] ?? 'user';
            $content = $msg['content'] ?? '';

            if ($role === 'system') {
                $systemPrompt .= ($systemPrompt ? "\n\n" : "") . $content;
                continue;
            }

            if ($role === 'tool') {
                // Function Response from tool execution
                $contents[] = [
                    'role' => 'function',
                    'parts' => [
                        [
                            'functionResponse' => [
                                'name' => $msg['name'] ?? 'tool_result',
                                'response' => is_array($content) ? $content : ['result' => $content],
                            ],
                        ],
                    ],
                ];
                continue;
            }

            $geminiRole = ($role === 'assistant') ? 'model' : 'user';

            // Check if this message was an assistant message containing function calls
            if ($geminiRole === 'model' && !empty($msg['tool_calls'])) {
                $parts = [];
                if (!empty($content)) {
                    $parts[] = ['text' => $content];
                }
                foreach ($msg['tool_calls'] as $tc) {
                    $parts[] = [
                        'functionCall' => [
                            'name' => $tc['name'],
                            'args' => $tc['arguments'] ?? [],
                        ],
                    ];
                }
                $contents[] = [
                    'role' => 'model',
                    'parts' => $parts,
                ];
                continue;
            }

            $contents[] = [
                'role' => $geminiRole,
                'parts' => [
                    ['text' => (string) $content],
                ],
            ];
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => (float) ($options['temperature'] ?? config('ai.temperature', 0.7)),
                'maxOutputTokens' => (int) ($options['max_tokens'] ?? config('ai.max_tokens', 1500)),
            ],
        ];

        if (!empty($systemPrompt)) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemPrompt],
                ],
            ];
        }

        if (!empty($tools)) {
            $functionDeclarations = [];
            foreach ($tools as $t) {
                $functionDeclarations[] = [
                    'name' => $t['name'],
                    'description' => $t['description'] ?? '',
                    'parameters' => $t['parameters'] ?? [
                        'type' => 'OBJECT',
                        'properties' => new \stdClass(),
                    ],
                ];
            }
            $payload['tools'] = [
                ['functionDeclarations' => $functionDeclarations],
            ];
        }

        $response = Http::timeout($this->timeout)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $payload);

        if (!$response->successful()) {
            $status = $response->status();
            $body = $response->json();
            $msg = $body['error']['message'] ?? $response->body();
            Log::error("Gemini API Error ({$status}): {$msg}");
            throw new RuntimeException("Lỗi giao tiếp với AI Provider ({$status}): {$msg}");
        }

        $data = $response->json();
        $candidate = $data['candidates'][0] ?? null;

        if (!$candidate) {
            return [
                'content' => 'Xin lỗi, tôi chưa thể trả lời câu hỏi này. Vui lòng thử lại sau.',
                'tool_calls' => [],
                'usage' => [
                    'prompt_tokens' => 0,
                    'completion_tokens' => 0,
                    'total_tokens' => 0,
                ],
                'finish_reason' => 'empty',
            ];
        }

        $text = '';
        $toolCalls = [];

        foreach ($candidate['content']['parts'] ?? [] as $part) {
            if (isset($part['text'])) {
                $text .= $part['text'];
            }
            if (isset($part['functionCall'])) {
                $fc = $part['functionCall'];
                $toolCalls[] = [
                    'id' => uniqid('call_'),
                    'name' => $fc['name'],
                    'arguments' => $fc['args'] ?? [],
                ];
            }
        }

        $usageMeta = $data['usageMetadata'] ?? [];

        return [
            'content' => $text ?: null,
            'tool_calls' => $toolCalls,
            'usage' => [
                'prompt_tokens' => $usageMeta['promptTokenCount'] ?? 0,
                'completion_tokens' => $usageMeta['candidatesTokenCount'] ?? 0,
                'total_tokens' => $usageMeta['totalTokenCount'] ?? 0,
            ],
            'finish_reason' => $candidate['finishReason'] ?? 'stop',
        ];
    }
}
