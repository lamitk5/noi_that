<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Services\Ai\AiAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    protected AiAssistantService $aiService;

    public function __construct(AiAssistantService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * List conversations for current user or guest session.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->session()->getId();

        $conversations = $this->aiService->listConversations($user, $sessionId);

        return response()->json([
            'success' => true,
            'conversations' => $conversations->map(fn ($c) => [
                'uuid' => $c->uuid,
                'title' => $c->title,
                'last_message_at' => $c->last_message_at?->diffForHumans(),
            ]),
        ]);
    }

    /**
     * Get details of a single conversation with message history.
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->session()->getId();

        $conversation = AiConversation::where('uuid', $uuid)->first();

        if (!$conversation || !$conversation->isOwnedBy($user, $sessionId)) {
            return response()->json(['success' => false, 'error' => 'Không tìm thấy cuộc trò chuyện.'], 404);
        }

        $messages = $conversation->messages()->orderBy('id', 'asc')->get()->map(function ($m) {
            return [
                'id' => $m->id,
                'role' => $m->role,
                'content' => $m->content,
                'cards' => $m->metadata['cards'] ?? [],
                'sources' => $m->metadata['sources'] ?? [],
                'created_at' => $m->created_at->format('H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'conversation' => [
                'uuid' => $conversation->uuid,
                'title' => $conversation->title,
                'messages' => $messages,
            ],
        ]);
    }

    /**
     * Start a new conversation.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->session()->getId();

        $conversation = $this->aiService->getOrCreateConversation($user, $sessionId);

        return response()->json([
            'success' => true,
            'conversation' => [
                'uuid' => $conversation->uuid,
                'title' => $conversation->title,
            ],
        ]);
    }

    /**
     * Send a user message to the AI assistant.
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'conversation_uuid' => 'nullable|string',
        ]);

        $user = $request->user();
        $sessionId = $request->session()->getId();

        $conversation = $this->aiService->getOrCreateConversation(
            $user,
            $sessionId,
            $validated['conversation_uuid'] ?? null
        );

        $result = $this->aiService->sendMessage($conversation, $validated['message'], [
            'user' => $user,
            'session_id' => $sessionId,
        ]);

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * Delete a conversation.
     */
    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->session()->getId();

        $conversation = AiConversation::where('uuid', $uuid)->first();

        if (!$conversation || !$conversation->isOwnedBy($user, $sessionId)) {
            return response()->json(['success' => false, 'error' => 'Không tìm thấy cuộc trò chuyện.'], 404);
        }

        $this->aiService->deleteConversation($conversation, $user, $sessionId);

        return response()->json(['success' => true]);
    }

    /**
     * Clear messages in a conversation.
     */
    public function clear(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->session()->getId();

        $conversation = AiConversation::where('uuid', $uuid)->first();

        if (!$conversation || !$conversation->isOwnedBy($user, $sessionId)) {
            return response()->json(['success' => false, 'error' => 'Không tìm thấy cuộc trò chuyện.'], 404);
        }

        $this->aiService->clearConversation($conversation, $user, $sessionId);

        return response()->json(['success' => true]);
    }
}
