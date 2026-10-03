<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\SupportRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = SupportRequest::with(['user', 'order'])->latest();

        if (array_key_exists((string) $request->query('type'), SupportRequest::TYPES)) {
            $query->where('type', $request->query('type'));
        }
        if (array_key_exists((string) $request->query('status'), SupportRequest::STATUSES)) {
            $query->where('status', $request->query('status'));
        }
        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('code', 'like', "%{$q}%")
                    ->orWhereHas('order', fn ($o) => $o->where('order_code', 'like', "%{$q}%"))
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
            });
        }

        return view('admin.support.index', [
            'requests' => $query->paginate(15)->withQueryString(),
            'stats' => [
                'pending' => SupportRequest::where('status', SupportRequest::STATUS_PENDING)->count(),
                'open' => SupportRequest::whereIn('status', SupportRequest::OPEN_STATUSES)->count(),
                'returns' => SupportRequest::where('type', SupportRequest::TYPE_RETURN)->count(),
                'complaints' => SupportRequest::where('type', SupportRequest::TYPE_COMPLAINT)->count(),
            ],
        ]);
    }

    public function show(SupportRequest $supportRequest): View
    {
        $supportRequest->load(['user', 'order.items', 'handler']);

        return view('admin.support.show', ['req' => $supportRequest]);
    }

    public function update(Request $request, SupportRequest $supportRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys($supportRequest->allowedStatuses()))],
            'admin_note' => ['nullable', 'string', 'max:2000', Rule::requiredIf($request->input('status') === SupportRequest::STATUS_REJECTED)],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
        ], [
            'admin_note.required' => 'Vui lòng ghi rõ lý do từ chối để gửi cho khách.',
        ]);

        $restocked = false;

        DB::transaction(function () use ($supportRequest, $validated, &$restocked) {
            $data = [
                'status' => $validated['status'],
                'admin_note' => $validated['admin_note'] ?? null,
                'handled_by' => Auth::id(),
                'resolved_at' => in_array($validated['status'], [SupportRequest::STATUS_COMPLETED, SupportRequest::STATUS_REJECTED], true)
                    ? ($supportRequest->resolved_at ?? now())
                    : null,
            ];
            if ($supportRequest->type === SupportRequest::TYPE_RETURN) {
                $data['refund_amount'] = $validated['refund_amount'] ?? null;
            }

            $shouldRestock = $supportRequest->type === SupportRequest::TYPE_RETURN
                && $supportRequest->resolution === 'refund'
                && $validated['status'] === SupportRequest::STATUS_COMPLETED
                && $supportRequest->restocked_at === null;

            if ($shouldRestock) {
                foreach ($supportRequest->items ?? [] as $item) {
                    if (!empty($item['product_variant_id'])) {
                        ProductVariant::whereKey($item['product_variant_id'])->increment('stock', (int) $item['quantity']);
                    }
                }
                $data['restocked_at'] = now();
                $restocked = true;
            }

            $supportRequest->update($data);
        });

        return back()->with('success', 'Đã cập nhật yêu cầu '.$supportRequest->code.'.'.($restocked ? ' Sản phẩm hoàn đã được cộng lại vào tồn kho.' : ''));
    }
}
