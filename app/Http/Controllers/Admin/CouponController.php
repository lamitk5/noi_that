<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(): View
    {
        $coupons = Coupon::latest()->paginate(15);

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create(): View
    {
        return view('admin.coupons.create');
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'code' => ['required', 'string', 'max:50', 'unique:coupons,code'],
                'name' => ['nullable', 'string', 'max:255'],
                'type' => ['required', 'in:percent,fixed'],
                'value' => ['required', 'numeric', 'min:0.01'],
                'min_order_amount' => ['nullable', 'numeric', 'min:0'],
                'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
                'usage_limit' => ['nullable', 'integer', 'min:1'],
                'starts_at' => ['nullable', 'date'],
                'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
                'is_active' => ['nullable'],
            ], [
                'code.required' => 'Mã khuyến mãi không được để trống.',
                'code.unique' => 'Mã khuyến mãi này đã tồn tại.',
                'value.required' => 'Giá trị giảm không được để trống.',
                'expires_at.after_or_equal' => 'Ngày hết hạn phải sau ngày bắt đầu.',
            ]);

            $validated['code'] = strtoupper(trim($validated['code']));
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['value'] = (float) $validated['value'];

            Coupon::create($validated);

            return redirect()->route('admin.coupons.index')->with('success', 'Tạo mã giảm giá thành công!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Coupon store failed', ['error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Không thể tạo mã giảm giá: '.$e->getMessage());
        }
    }

    public function edit(Coupon $coupon): View
    {
        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'code' => ['required', 'string', 'max:50', Rule::unique('coupons')->ignore($coupon->id)],
                'name' => ['nullable', 'string', 'max:255'],
                'type' => ['required', 'in:percent,fixed'],
                'value' => ['required', 'numeric', 'min:0.01'],
                'min_order_amount' => ['nullable', 'numeric', 'min:0'],
                'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
                'usage_limit' => ['nullable', 'integer', 'min:1'],
                'starts_at' => ['nullable', 'date'],
                'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
                'is_active' => ['nullable'],
            ]);

            $validated['code'] = strtoupper(trim($validated['code']));
            $validated['is_active'] = $request->boolean('is_active');
            $validated['value'] = (float) $validated['value'];

            $coupon->update($validated);

            return redirect()->route('admin.coupons.index')->with('success', 'Cập nhật mã giảm giá thành công!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Coupon update failed', ['id' => $coupon->id, 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Cập nhật mã giảm giá thất bại: '.$e->getMessage());
        }
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        try {
            $coupon->delete();

            return redirect()->route('admin.coupons.index')->with('success', 'Đã xóa mã giảm giá thành công.');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Coupon destroy failed', ['id' => $coupon->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Không thể xóa mã giảm giá: '.$e->getMessage());
        }
    }
}
