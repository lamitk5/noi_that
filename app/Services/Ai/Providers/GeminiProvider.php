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
        $this->model = $model ?? (string) config('ai.model', 'gemini-3.8-flash');
        $this->timeout = $timeout > 0 ? $timeout : (int) config('ai.timeout', 30);
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function chat(array $messages, array $tools = [], array $options = []): array
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }

        $model = $options['model'] ?? $this->model;
        // Pass API key via x-goog-api-key header to prevent exposing key in URL query params or access logs
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

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
                // In Gemini v1beta, functionResponse parts must be sent with role 'user'
                $responseObject = (is_array($content) && !array_is_list($content))
                    ? $content
                    : ['result' => $content];

                $fcResponse = [
                    'name' => $msg['name'] ?? 'tool_result',
                    'response' => $responseObject,
                ];
                if (!empty($msg['call_id'])) {
                    $fcResponse['id'] = $msg['call_id'];
                } elseif (!empty($msg['id'])) {
                    $fcResponse['id'] = $msg['id'];
                }

                $toolPart = [
                    'functionResponse' => $fcResponse,
                ];

                $lastIndex = count($contents) - 1;
                if ($lastIndex >= 0 && $contents[$lastIndex]['role'] === 'user') {
                    $contents[$lastIndex]['parts'][] = $toolPart;
                } else {
                    $contents[] = [
                        'role' => 'user',
                        'parts' => [$toolPart],
                    ];
                }
                continue;
            }

            $geminiRole = ($role === 'assistant') ? 'model' : 'user';

            // Check if this message was an assistant message containing function calls
            if ($geminiRole === 'model' && (!empty($msg['tool_calls']) || !empty($msg['raw_parts']))) {
                if (!empty($msg['raw_parts']) && is_array($msg['raw_parts'])) {
                    $contents[] = [
                        'role' => 'model',
                        'parts' => $msg['raw_parts'],
                    ];
                    continue;
                }

                $parts = [];
                if (!empty($content)) {
                    $parts[] = ['text' => $content];
                }
                foreach ($msg['tool_calls'] as $tc) {
                    if (!empty($tc['raw_part']) && is_array($tc['raw_part'])) {
                        $parts[] = $tc['raw_part'];
                    } else {
                        $fcData = [
                            'name' => $tc['name'],
                            'args' => !empty($tc['arguments']) ? (object)$tc['arguments'] : (object)[],
                        ];
                        if (!empty($tc['id'])) {
                            $fcData['id'] = $tc['id'];
                        }
                        $fcPart = [
                            'functionCall' => $fcData,
                        ];
                        if (!empty($tc['thoughtSignature'])) {
                            $fcPart['thoughtSignature'] = $tc['thoughtSignature'];
                        } elseif (!empty($tc['thought_signature'])) {
                            $fcPart['thoughtSignature'] = $tc['thought_signature'];
                        }
                        $parts[] = $fcPart;
                    }
                }
                $contents[] = [
                    'role' => 'model',
                    'parts' => $parts,
                ];
                continue;
            }

            $textPart = ['text' => (string) $content];
            $lastIndex = count($contents) - 1;
            if ($lastIndex >= 0 && $contents[$lastIndex]['role'] === $geminiRole) {
                $contents[$lastIndex]['parts'][] = $textPart;
            } else {
                $contents[] = [
                    'role' => $geminiRole,
                    'parts' => [$textPart],
                ];
            }
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

        $maxRetries = 3;
        $attempt = 0;
        $response = null;

        while ($attempt < $maxRetries) {
            $attempt++;
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $this->apiKey,
                ])
                ->post($url, $payload);

            if ($response->successful()) {
                break;
            }

            $status = $response->status();
            // Retry on transient 503 (Model Overloaded / Service Unavailable) or 429 (Rate Limit)
            if (in_array($status, [429, 503, 500, 504]) && $attempt < $maxRetries) {
                $sleepSeconds = 2 * $attempt;
                $body = $response ? $response->json() : null;
                $msg = $body['error']['message'] ?? ($response ? $response->body() : '');
                if (preg_match('/retry in ([0-9.]+)s/i', (string)$msg, $m)) {
                    $needed = (int) ceil((float)$m[1]);
                    if ($needed <= 60) {
                        $sleepSeconds = max($sleepSeconds, $needed + 1);
                    }
                }
                sleep($sleepSeconds);
                continue;
            }

            break;
        }

        if (!$response || !$response->successful()) {
            $status = $response ? $response->status() : 500;
            $body = $response ? $response->json() : null;
            $msg = $body['error']['message'] ?? ($response ? $response->body() : 'No response');
            $safeMsg = str_replace($this->apiKey, '[REDACTED_API_KEY]', (string) $msg);
            Log::error("Gemini API Error ({$status}): {$safeMsg}");
            throw new RuntimeException("Lỗi giao tiếp với AI Provider ({$status}): {$safeMsg}");
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
                $toolCallItem = [
                    'id' => uniqid('call_'),
                    'name' => $fc['name'],
                    'arguments' => $fc['args'] ?? [],
                    'raw_part' => $part,
                ];
                if (isset($part['thoughtSignature'])) {
                    $toolCallItem['thoughtSignature'] = $part['thoughtSignature'];
                } elseif (isset($part['thought_signature'])) {
                    $toolCallItem['thoughtSignature'] = $part['thought_signature'];
                }
                $toolCalls[] = $toolCallItem;
            }
        }

        $usageMeta = $data['usageMetadata'] ?? [];

        return [
            'content' => $text ?: null,
            'tool_calls' => $toolCalls,
            'raw_parts' => $candidate['content']['parts'] ?? [],
            'usage' => [
                'prompt_tokens' => $usageMeta['promptTokenCount'] ?? 0,
                'completion_tokens' => $usageMeta['candidatesTokenCount'] ?? 0,
                'total_tokens' => $usageMeta['totalTokenCount'] ?? 0,
            ],
            'finish_reason' => $candidate['finishReason'] ?? 'stop',
        ];
    }
}
