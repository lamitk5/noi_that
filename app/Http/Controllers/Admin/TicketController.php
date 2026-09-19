<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $query = SupportTicket::query()->with('user')->latest('last_reply_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }

        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('ticket_code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('subject', 'like', "%{$q}%");
            });
        }

        $tickets = $query->paginate(15)->withQueryString();

        return view('admin.tickets.index', compact('tickets'));
    }

    public function show(SupportTicket $ticket): View
    {
        $ticket->load(['user', 'replies.user']);

        return view('admin.tickets.show', compact('ticket'));
    }

    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:open,in_progress,resolved,closed'],
        ]);

        $ticket->update(['status' => $validated['status']]);

        return redirect()->route('admin.tickets.show', $ticket)->with('success', 'Đã cập nhật trạng thái yêu cầu hỗ trợ.');
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'new_status' => ['nullable', 'string', 'in:open,in_progress,resolved,closed'],
        ], [
            'message.required' => 'Vui lòng nhập nội dung phản hồi.',
        ]);

        $user = $request->user();

        $ticket->replies()->create([
            'user_id' => $user->id,
            'author_name' => $user->name . ' (Chăm sóc khách hàng)',
            'is_admin' => true,
            'message' => $validated['message'],
        ]);

        $newStatus = $validated['new_status'] ?? SupportTicket::STATUS_IN_PROGRESS;

        $ticket->update([
            'status' => $newStatus,
            'last_reply_at' => now(),
        ]);

        return redirect()->route('admin.tickets.show', $ticket)->with('success', 'Đã gửi phản hồi cho khách hàng thành công.');
    }
}
