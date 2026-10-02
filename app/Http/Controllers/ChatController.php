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
     * Get or create the current user's chat.
     */
    public function session(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Vui lòng đăng nhập để trò chuyện.'], 401);
        }

        // Mỗi tài khoản chỉ duy trì 1 cuộc trò chuyện duy nhất
        $chat = Chat::firstOrCreate(
            ['user_id' => Auth::id()],
            [
                'status' => Chat::STATUS_OPEN,
                'last_message_at' => null,
            ]
        );

        $unread = ChatMessage::where('chat_id', $chat->id)
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
     * List messages for customer chat.
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
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'role' => $m->sender_id === Auth::id() ? 'user' : 'staff',
                'sender' => $m->sender?->name ?? ($m->sender_id === Auth::id() ? Auth::user()->name : 'Nhân viên Mộc An'),
                'content' => $m->message,
                'created_at' => $m->created_at->format('H:i d/m'),
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

        // If the conversation was closed, automatically reopen it so customer can continue chatting
        if ($chat->status !== Chat::STATUS_OPEN) {
            $chat->update(['status' => Chat::STATUS_OPEN]);
        }

        $msg = DB::transaction(function () use ($chat, $request) {
            $msg = $chat->messages()->create([
                'sender_id' => Auth::id(),
                'message' => $request->input('message'),
                'is_read' => false,
            ]);
            $chat->update([
                'last_message_at' => now(),
                'status' => Chat::STATUS_OPEN,
            ]);

            return $msg;
        });

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $msg->id,
                'role' => 'user',
                'sender' => Auth::user()->name,
                'content' => $msg->message,
                'created_at' => $msg->created_at->format('H:i d/m'),
            ],
        ]);
    }
}
