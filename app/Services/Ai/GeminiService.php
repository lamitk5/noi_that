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

        $model = config('services.gemini.model');
        $url = rtrim((string) config('services.gemini.endpoint'), '/')."/models/{$model}:generateContent";

        $payload = [
            'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => array_map(fn (array $turn) => [
                'role' => $turn['role'],
                'parts' => [['text' => $turn['text']]],
            ], $history),
            'generationConfig' => [
                'temperature' => 0.6,
                'maxOutputTokens' => 800,
                'responseMimeType' => 'application/json',
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

    protected function stripCodeFence(string $text): string
    {
        $text = trim($text);
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text) ?? $text;
        }

        return $text;
    }
}
