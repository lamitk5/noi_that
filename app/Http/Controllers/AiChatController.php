<?php

namespace App\Http\Controllers;

use App\Services\Ai\ProductRecommender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiChatController extends Controller
{
    private const SESSION_KEY = 'ai_chat_history';

    private const MAX_TURNS = 6;

    public function __construct(protected ProductRecommender $recommender)
    {
    }

    public function history(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'messages' => $request->session()->get(self::SESSION_KEY.'_display', []),
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ], [
            'message.required' => 'Vui lòng nhập câu hỏi.',
            'message.max' => 'Câu hỏi tối đa 500 ký tự.',
        ]);

        $message = trim(strip_tags($validated['message']));
        $history = $request->session()->get(self::SESSION_KEY, []);

        $result = $this->recommender->recommend($message, $history);

        $history[] = ['role' => 'user', 'text' => $message];
        $history[] = ['role' => 'model', 'text' => $result['reply']];
        $request->session()->put(self::SESSION_KEY, array_slice($history, -self::MAX_TURNS * 2));

        $display = $request->session()->get(self::SESSION_KEY.'_display', []);
        $now = now()->format('H:i');
        $userEntry = ['id' => (string) Str::uuid(), 'role' => 'user', 'content' => $message, 'created_at' => $now, 'products' => []];
        $botEntry = ['id' => (string) Str::uuid(), 'role' => 'assistant', 'content' => $result['reply'], 'created_at' => $now, 'products' => $result['products']];
        $display = array_slice([...$display, $userEntry, $botEntry], -self::MAX_TURNS * 2);
        $request->session()->put(self::SESSION_KEY.'_display', $display);

        return response()->json([
            'success' => true,
            'ai' => $result['ai'],
            'message' => $botEntry,
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $request->session()->forget([self::SESSION_KEY, self::SESSION_KEY.'_display']);

        return response()->json(['success' => true]);
    }
}
