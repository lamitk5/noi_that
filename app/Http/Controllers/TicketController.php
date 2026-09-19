<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $tickets = $request->user()
            ->supportTickets()
            ->latest('last_reply_at')
            ->paginate(10);

        return view('account.tickets.index', compact('tickets'));
    }

    public function create(Request $request): View
    {
        return view('account.tickets.create', [
            'user' => $request->user(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:general,order,delivery,product,warranty,complaint,technical'],
            'priority' => ['required', 'string', 'in:low,normal,high,urgent'],
            'message' => ['required', 'string', 'max:5000'],
        ], [
            'subject.required' => 'Vui lòng nhập tiêu đề yêu cầu.',
            'category.required' => 'Vui lòng chọn chủ đề.',
            'message.required' => 'Vui lòng nhập nội dung chi tiết.',
        ]);

        $user = $request->user();

        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'message' => $validated['message'],
            'status' => SupportTicket::STATUS_OPEN,
            'last_reply_at' => now(),
        ]);

        $ticket->replies()->create([
            'user_id' => $user->id,
            'author_name' => $user->name,
            'message' => $validated['message'],
            'is_admin' => false,
        ]);

        return redirect()->route('account.tickets.show', $ticket)
            ->with('success', 'Yêu cầu hỗ trợ của bạn đã được gửi thành công. Đội ngũ Mộc An sẽ phản hồi sớm nhất!');
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        if ($ticket->user_id !== $request->user()->id) {
            abort(403, 'Bạn không có quyền xem yêu cầu này.');
        }

        $ticket->load(['replies.user']);

        return view('account.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        if ($ticket->user_id !== $request->user()->id) {
            abort(403, 'Bạn không có quyền phản hồi yêu cầu này.');
        }

        if ($ticket->status === SupportTicket::STATUS_CLOSED) {
            return back()->with('error', 'Yêu cầu này đã được đóng, không thể gửi thêm phản hồi.');
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ], [
            'message.required' => 'Vui lòng nhập nội dung phản hồi.',
        ]);

        $user = $request->user();

        $ticket->replies()->create([
            'user_id' => $user->id,
            'author_name' => $user->name,
            'is_admin' => false,
            'message' => $validated['message'],
        ]);

        $ticket->update([
            'status' => SupportTicket::STATUS_OPEN,
            'last_reply_at' => now(),
        ]);

        return redirect()->route('account.tickets.show', $ticket)->with('success', 'Đã gửi phản hồi thành công.');
    }
}
