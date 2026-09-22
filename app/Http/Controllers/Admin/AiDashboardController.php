<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\CmsPage;
use App\Models\Faq;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Models\UserEvent;
use App\Services\Ai\KnowledgeRetriever;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiDashboardController extends Controller
{
    protected KnowledgeRetriever $knowledgeRetriever;

    public function __construct(KnowledgeRetriever $knowledgeRetriever)
    {
        $this->knowledgeRetriever = $knowledgeRetriever;
    }

    /**
     * Display the AI Dashboard with metrics, conversations, knowledge stats, and settings.
     */
    public function index(Request $request): View
    {
        // 1. Analytics & Metrics
        $totalConversations = AiConversation::count();
        $totalMessages = AiMessage::count();
        $totalTokens = (int) AiMessage::sum('tokens_used');

        $activeConversationsToday = AiConversation::where('created_at', '>=', now()->startOfDay())->count();
        $messagesToday = AiMessage::where('created_at', '>=', now()->startOfDay())->count();

        // Tool usage counts from user_events
        $toolEventsCount = UserEvent::where('event_type', 'ai_chat_message')->count();

        // 2. Recent Conversations
        $search = $request->input('q');
        $conversationsQuery = AiConversation::with(['user', 'messages' => function ($q) {
            $q->latest()->limit(1);
        }])->withCount('messages');

        if (!empty($search)) {
            $conversationsQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('uuid', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $conversations = $conversationsQuery->latest('last_message_at')->paginate(15)->withQueryString();

        // 3. Knowledge Base Source Counts
        $knowledgeCounts = [
            'faqs' => Faq::where('is_active', true)->count(),
            'pages' => CmsPage::where('is_active', true)->count(),
            'posts' => Post::where('is_published', true)->count(),
        ];

        // 4. Current Settings
        $aiEnabled = SiteSetting::get('ai_enabled', '1') === '1';
        $customInstructions = SiteSetting::get('ai_custom_instructions', '');
        $temperature = (float) SiteSetting::get('ai_temperature', config('ai.temperature', 0.7));
        $maxTokens = (int) SiteSetting::get('ai_max_tokens', config('ai.max_tokens', 1500));
        $provider = config('ai.provider', 'gemini');
        $model = config('ai.model', 'gemini-1.5-flash');
        $apiKeyMasked = config('ai.api_key') ? StrMask(config('ai.api_key')) : 'Chưa cấu hình (đang dùng Mock)';

        return view('admin.ai.index', [
            'metrics' => [
                'total_conversations' => $totalConversations,
                'total_messages' => $totalMessages,
                'total_tokens' => $totalTokens,
                'active_today' => $activeConversationsToday,
                'messages_today' => $messagesToday,
                'tool_events' => $toolEventsCount,
            ],
            'conversations' => $conversations,
            'knowledgeCounts' => $knowledgeCounts,
            'settings' => [
                'ai_enabled' => $aiEnabled,
                'custom_instructions' => $customInstructions,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
                'provider' => $provider,
                'model' => $model,
                'api_key_masked' => $apiKeyMasked,
            ],
            'activeTab' => $request->input('tab', 'overview'),
        ]);
    }

    /**
     * View conversation transcript dialog.
     */
    public function showConversation(string $uuid): View
    {
        $conversation = AiConversation::where('uuid', $uuid)
            ->with(['user', 'messages'])
            ->firstOrFail();

        return view('admin.ai.conversation', [
            'conversation' => $conversation,
        ]);
    }

    /**
     * Delete a conversation.
     */
    public function destroyConversation(string $uuid): RedirectResponse
    {
        $conversation = AiConversation::where('uuid', $uuid)->firstOrFail();
        $conversation->delete();

        return redirect()->route('admin.ai.index', ['tab' => 'conversations'])
            ->with('success', 'Đã xóa cuộc trò chuyện thành công.');
    }

    /**
     * Live test RAG knowledge retriever.
     */
    public function testRag(Request $request): View
    {
        $query = (string) $request->input('query', '');
        $chunks = [];

        if (!empty(trim($query))) {
            $chunks = $this->knowledgeRetriever->retrieve($query, 6);
        }

        return view('admin.ai.test-rag', [
            'query' => $query,
            'chunks' => $chunks,
        ]);
    }

    /**
     * Update AI settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ai_enabled' => 'nullable|in:0,1',
            'ai_custom_instructions' => 'nullable|string|max:2000',
            'ai_temperature' => 'nullable|numeric|min:0|max:1',
            'ai_max_tokens' => 'nullable|integer|min:200|max:4000',
        ]);

        SiteSetting::set('ai_enabled', $request->has('ai_enabled') ? '1' : '0', 'ai');
        SiteSetting::set('ai_custom_instructions', $validated['ai_custom_instructions'] ?? '', 'ai');
        SiteSetting::set('ai_temperature', (string) ($validated['ai_temperature'] ?? '0.7'), 'ai');
        SiteSetting::set('ai_max_tokens', (string) ($validated['ai_max_tokens'] ?? '1500'), 'ai');

        return redirect()->route('admin.ai.index', ['tab' => 'settings'])
            ->with('success', 'Cài đặt Trợ lý AI đã được cập nhật!');
    }
}

function StrMask(string $str): string
{
    if (strlen($str) <= 8) {
        return '********';
    }
    return substr($str, 0, 4) . '...' . substr($str, -4);
}
