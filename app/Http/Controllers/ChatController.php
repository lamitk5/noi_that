<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * Get or create the current user's open chat.
     */
    public function session(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Vui lòng đăng nhập để trò chuyện.'], 401);
        }

        $chat = Chat::where('user_id', Auth::id())
            ->where('status', Chat::STATUS_OPEN)
            ->latest()
            ->first();

        if (! $chat) {
            $chat = Chat::create([
                'user_id' => Auth::id(),
                'status' => Chat::STATUS_OPEN,
                'last_message_at' => now(),
            ]);
        }

        $unread = $chat->messages()
            ->where('sender_id', '!=', Auth::id())
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'chat_id' => $chat->id,
            'status' => $chat->status,
            'unread' => $unread,
        ]);
    }

    /**
     * List messages for a chat (only own chats for customers).
     */
    public function messages(Request $request, int $chatId): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $chat = Chat::where('id', $chatId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $after = $request->integer('after', 0);

        $messages = $chat->messages()
            ->with('sender:id,name,role')
            ->when($after > 0, fn ($q) => $q->where('id', '>', $after))
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'role' => $m->sender_id === Auth::id() ? 'user' : 'staff',
                'sender' => $m->sender?->name,
                'content' => $m->message,
                'created_at' => $m->created_at->format('H:i'),
            ]);

        // Mark staff messages as read
        $chat->messages()
            ->where('sender_id', '!=', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true, 'messages' => $messages]);
    }

    /**
     * Send a message from customer.
     */
    public function send(Request $request, int $chatId): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $chat = Chat::where('id', $chatId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($chat->status !== Chat::STATUS_OPEN) {
            return response()->json(['success' => false, 'message' => 'Cuộc trò chuyện đã đóng.'], 422);
        }

        $msg = DB::transaction(function () use ($chat, $request) {
            $msg = $chat->messages()->create([
                'sender_id' => Auth::id(),
                'message' => $request->input('message'),
                'is_read' => false,
            ]);
            $chat->update(['last_message_at' => now()]);

            return $msg;
        });

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $msg->id,
                'role' => 'user',
                'sender' => Auth::user()->name,
                'content' => $msg->message,
                'created_at' => $msg->created_at->format('H:i'),
            ],
        ]);
    }
}
