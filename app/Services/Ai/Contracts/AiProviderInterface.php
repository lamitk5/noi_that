<?php

namespace App\Services\Ai\Contracts;

interface AiProviderInterface
{
    /**
     * Send a chat completion request to the LLM.
     *
     * @param array $messages [{role: string, content: string, tool_call_id?: string, name?: string}]
     * @param array $tools Array of tool specifications (OpenAI/Gemini compatible format)
     * @param array $options Model options (temperature, max_tokens, model, etc.)
     * @return array [
     *     'content' => ?string,
     *     'tool_calls' => array, // [{id: string, name: string, arguments: array}]
     *     'usage' => array, // ['prompt_tokens' => int, 'completion_tokens' => int, 'total_tokens' => int]
     *     'finish_reason' => string
     * ]
     */
    public function chat(array $messages, array $tools = [], array $options = []): array;
}
