<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VoucherController extends Controller
{
    /**
     * Display a listing of vouchers.
     */
    public function index(Request $request): View
    {
        $query = Voucher::query()->withCount('usages')->latest();

        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%");
            });
        }

        if ($request->has('status') && $request->query('status') !== '') {
            $query->where('is_active', $request->query('status') === 'active');
        }

        $vouchers = $query->paginate(15)->withQueryString();

        return view('admin.vouchers.index', [
            'vouchers' => $vouchers,
            'filters' => [
                'q' => $request->query('q', ''),
                'status' => $request->query('status', ''),
            ],
        ]);
    }

    /**
     * Show the form for creating a new voucher.
     */
    public function create(): View
    {
        return view('admin.vouchers.create');
    }

    /**
     * Store a newly created voucher.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:vouchers,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:fixed,percent'],
            'value' => ['required', 'numeric', 'min:1'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'code.required' => 'Vui lòng nhập mã khuyến mãi.',
            'code.unique' => 'Mã khuyến mãi này đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên chương trình khuyến mãi.',
            'value.required' => 'Vui lòng nhập giá trị giảm.',
            'expires_at.after_or_equal' => 'Ngày hết hạn phải sau hoặc bằng ngày bắt đầu.',
        ]);

        Voucher::create([
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'type' => $validated['type'],
            'value' => $validated['value'],
            'min_order_amount' => $validated['min_order_amount'] ?? 0,
            'max_discount' => $validated['max_discount'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Đã tạo mã khuyến mãi mới thành công.');
    }

    /**
     * Show the form for editing the voucher.
     */
    public function edit(Voucher $voucher): View
    {
        return view('admin.vouchers.edit', compact('voucher'));
    }

    /**
     * Update the specified voucher.
     */
    public function update(Request $request, Voucher $voucher): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('vouchers', 'code')->ignore($voucher->id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:fixed,percent'],
            'value' => ['required', 'numeric', 'min:1'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $voucher->update([
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'type' => $validated['type'],
            'value' => $validated['value'],
            'min_order_amount' => $validated['min_order_amount'] ?? 0,
            'max_discount' => $validated['max_discount'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : false,
        ]);

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Đã cập nhật mã khuyến mãi thành công.');
    }

    /**
     * Toggle active status of voucher.
     */
    public function toggleStatus(Voucher $voucher): RedirectResponse
    {
        $voucher->update(['is_active' => ! $voucher->is_active]);

        return back()->with('success', 'Đã thay đổi trạng thái mã khuyến mãi.');
    }

    /**
     * Remove the specified voucher.
     */
    public function destroy(Voucher $voucher): RedirectResponse
    {
        if ($voucher->usages()->exists()) {
            $voucher->update(['is_active' => false]);
            return back()->with('success', 'Mã đã được sử dụng nên đã chuyển sang trạng thái Ngưng áp dụng thay vì xóa hoàn toàn.');
        }

        $voucher->delete();

        return back()->with('success', 'Đã xóa mã khuyến mãi thành công.');
    }
}
