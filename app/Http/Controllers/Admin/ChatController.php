<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ChatController extends Controller
{
    /**
     * List all conversations with filters.
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = Chat::has('messages')
            ->with(['user:id,name,email,phone', 'staff:id,name', 'latestMessage'])
            ->withCount(['messages as unread_count' => function ($q) {
                $q->where('is_read', false);
            }]);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $chats = $query->latest('last_message_at')->paginate(15)->withQueryString();

        $stats = [
            'open' => Chat::has('messages')->where('status', Chat::STATUS_OPEN)->count(),
            'closed' => Chat::has('messages')->where('status', Chat::STATUS_CLOSED)->count(),
            'unread' => ChatMessage::where('is_read', false)
                ->whereHas('chat', fn ($q) => $q->whereHas('user'))
                ->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $chats, 'stats' => $stats]);
        }

        return view('admin.chats.index', compact('chats', 'stats'));
    }

    /**
     * View a conversation and reply.
     */
    public function show(Request $request, Chat $chat): View|JsonResponse
    {
        $chat->load(['user:id,name,email,phone', 'staff:id,name']);
        $messages = $chat->messages()
            ->with('sender:id,name,role')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'sender_id' => $m->sender_id,
                'sender_name' => $m->sender?->name,
                'is_mine' => $m->sender_id === Auth::id(),
                'message' => $m->message,
                'is_read' => $m->is_read,
                'created_at' => $m->created_at->format('d/m/Y H:i'),
            ]);

        // Mark customer messages as read by staff
        $chat->messages()
            ->where('sender_id', '!=', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $staff = User::whereIn('role', ['admin', 'staff'])->where('is_active', true)->orderBy('name')->get(['id', 'name', 'role']);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'chat' => $chat, 'messages' => $messages]);
        }

        return view('admin.chats.show', compact('chat', 'messages', 'staff'));
    }

    /**
     * Reply from staff/admin.
     */
    public function reply(Request $request, Chat $chat): RedirectResponse|JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $msg = DB::transaction(function () use ($chat, $request) {
            $msg = $chat->messages()->create([
                'sender_id' => Auth::id(),
                'message' => $request->input('message'),
                'is_read' => true,
            ]);
            $chat->update([
                'last_message_at' => now(),
                'staff_id' => Auth::id(),
            ]);

            return $msg;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $msg->id,
                    'sender_id' => $msg->sender_id,
                    'sender_name' => Auth::user()->name,
                    'is_mine' => true,
                    'message' => $msg->message,
                    'created_at' => $msg->created_at->format('d/m/Y H:i'),
                ],
            ]);
        }

        return redirect()->route('admin.chats.show', $chat)->with('success', 'Đã gửi phản hồi.');
    }

    /**
     * Close a conversation.
     */
    public function close(Request $request, Chat $chat): RedirectResponse|JsonResponse
    {
        $chat->update(['status' => Chat::STATUS_CLOSED]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã đóng cuộc trò chuyện.']);
        }

        return redirect()->route('admin.chats.index')->with('success', 'Đã đóng cuộc trò chuyện.');
    }

    /**
     * Reopen a conversation.
     */
    public function reopen(Request $request, Chat $chat): RedirectResponse|JsonResponse
    {
        $chat->update(['status' => Chat::STATUS_OPEN]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã mở lại cuộc trò chuyện.']);
        }

        return redirect()->route('admin.chats.show', $chat)->with('success', 'Đã mở lại cuộc trò chuyện.');
    }

    /**
     * Claim or takeover a conversation.
     */
    public function claim(Request $request, Chat $chat): RedirectResponse|JsonResponse
    {
        $chat->update(['staff_id' => Auth::id()]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Bạn đã tiếp nhận cuộc trò chuyện này.',
                'staff' => [
                    'id' => Auth::id(),
                    'name' => Auth::user()->name,
                ],
            ]);
        }

        return redirect()->route('admin.chats.show', $chat)->with('success', 'Bạn đã tiếp nhận cuộc trò chuyện này.');
    }

    /**
     * Assign a staff member to a conversation.
     */
    public function assign(Request $request, Chat $chat): RedirectResponse|JsonResponse
    {
        $request->validate([
            'staff_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $chat->update(['staff_id' => $request->input('staff_id')]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã phân công nhân viên.']);
        }

        return redirect()->route('admin.chats.show', $chat)->with('success', 'Đã phân công nhân viên.');
    }

    /**
     * Unread badge count for admin polling.
     */
    public function unread(): JsonResponse
    {
        $count = ChatMessage::where('is_read', false)
            ->whereHas('chat.user')
            ->where('sender_id', '!=', Auth::id())
            ->count();

        return response()->json(['success' => true, 'unread' => $count]);
    }
}
