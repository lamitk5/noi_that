<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeminiService
{
    public function isConfigured(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /**
     * Ask Gemini for a JSON answer.
     *
     * @param  array<int, array{role: 'user'|'model', text: string}>  $history
     * @return array<string, mixed>|null Decoded JSON object, or null on any failure.
     */
    public function generateJson(string $systemPrompt, array $history): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $model = $this->model();
        $url = rtrim((string) config('services.gemini.endpoint'), '/')."/models/{$model}:generateContent";

        $payload = [
            'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => array_map(fn (array $turn) => [
                'role' => $turn['role'],
                'parts' => [['text' => $turn['text']]],
            ], $history),
            'generationConfig' => [
                'temperature' => 0.6,
                'maxOutputTokens' => 2048,
                'responseMimeType' => 'application/json',
                'thinkingConfig' => ['thinkingBudget' => 0],
            ],
        ];

        try {
            $response = Http::timeout((int) config('services.gemini.timeout', 20))
                ->withHeaders(['x-goog-api-key' => config('services.gemini.key')])
                ->acceptJson()
                ->post($url, $payload);

            if (! $response->successful()) {
                Log::warning('Gemini request failed', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);

                return null;
            }

            $text = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
            $decoded = json_decode($this->stripCodeFence($text), true);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable $e) {
            Log::warning('Gemini request error: '.$e->getMessage());

            return null;
        }
    }

    public function generateJsonFromImage(string $instruction, string $mime, string $base64): ?array
    {
        if (! $this->isConfigured() || $base64 === '') {
            return null;
        }

        $model = $this->model();
        $url = rtrim((string) config('services.gemini.endpoint'), '/')."/models/{$model}:generateContent";

        $payload = [
            'contents' => [[
                'role' => 'user',
                'parts' => [
                    ['text' => $instruction],
                    ['inlineData' => ['mimeType' => $mime, 'data' => $base64]],
                ],
            ]],
            'generationConfig' => [
                'temperature' => 0.2,
                'maxOutputTokens' => 2048,
                'responseMimeType' => 'application/json',
                'thinkingConfig' => ['thinkingBudget' => 0],
            ],
        ];

        try {
            $response = null;
            for ($attempt = 1; $attempt <= 2; $attempt++) {
                $response = Http::timeout((int) config('services.gemini.timeout', 20))
                    ->withHeaders(['x-goog-api-key' => config('services.gemini.key')])
                    ->acceptJson()
                    ->post($url, $payload);

                if ($response->successful() || ! in_array($response->status(), [429, 503], true) || $attempt === 2) {
                    break;
                }

                usleep(800000);
            }

            if (! $response->successful()) {
                Log::warning('Gemini image request failed', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);

                return null;
            }

            $text = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
            $decoded = json_decode($this->stripCodeFence($text), true);
            if (! is_array($decoded)) {
                Log::warning('Gemini image response was not JSON', ['text' => mb_substr($text, 0, 300)]);

                return null;
            }

            return $decoded;
        } catch (Throwable $e) {
            Log::warning('Gemini image request error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Google has retired gemini-2.0-flash. Keep working when Render still has that name set.
     */
    protected function model(): string
    {
        $model = (string) config('services.gemini.model');
        $retired = [
            '',
            'gemini-1.5-flash',
            'gemini-1.5-flash-latest',
            'gemini-1.5-pro',
            'gemini-2.0-flash',
            'gemini-2.0-flash-001',
            'gemini-2.0-flash-lite',
        ];

        return in_array($model, $retired, true) ? 'gemini-3.8-flash' : $model;
    }

    protected function stripCodeFence(string $text): string
    {
        $text = trim($text);
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text) ?? $text;
        }

        return $text;
    }
}
