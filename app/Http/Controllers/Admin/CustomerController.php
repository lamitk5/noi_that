<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = User::query()
            ->where('role', '!=', 'admin')
            ->withCount(['orders'])
            ->withSum('orders as total_spent', 'total_price');

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'locked') {
                $query->where('is_active', false);
            }
        }

        $customers = $query->latest('id')->paginate(15)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $customers]);
        }

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $customer): View
    {
        abort_if($customer->role === 'admin', 404);

        $customer->load(['orders' => fn ($q) => $q->latest()->limit(20)]);
        $ordersCount = $customer->orders()->count();
        $totalSpent = (float) $customer->orders()
            ->whereNotIn('order_status', ['canceled', 'cancelled'])
            ->sum('total_price');

        return view('admin.customers.show', compact('customer', 'ordersCount', 'totalSpent'));
    }

    /**
     * Edit form for customer info.
     */
    public function edit(User $customer): View
    {
        abort_if($customer->role === 'admin', 404);

        return view('admin.customers.edit', compact('customer'));
    }

    /**
     * Update customer info. Email is checked against the DB for duplicates.
     */
    public function update(Request $request, User $customer): RedirectResponse|JsonResponse
    {
        abort_if($customer->role === 'admin', 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($customer->id),
            ],
            'phone' => [
                'nullable', 'string', 'max:20',
                Rule::unique('users', 'phone')->ignore($customer->id),
            ],
            'username' => [
                'nullable', 'string', 'min:3', 'max:50', 'alpha_dash',
                Rule::unique('users', 'username')->ignore($customer->id),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'Vui lòng nhập họ tên khách hàng.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email đã tồn tại trong hệ thống, vui lòng dùng email khác.',
            'phone.unique' => 'Số điện thoại đã tồn tại trong hệ thống, vui lòng dùng số khác.',
            'username.unique' => 'Tên đăng nhập đã tồn tại trong hệ thống.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
        ]);

        $customer->name = $validated['name'];
        $customer->email = strtolower($validated['email']);
        $customer->phone = $validated['phone'] ?? null;
        $customer->address = $validated['address'] ?? null;

        if (! empty($validated['username'])) {
            $customer->username = strtolower($validated['username']);
        }

        if (! empty($validated['password'])) {
            $customer->password = Hash::make($validated['password']);
        }

        if ($request->has('is_active') && ! $customer->isAdmin()) {
            $customer->is_active = $request->boolean('is_active');
        }

        $customer->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã cập nhật thông tin khách hàng.']);
        }

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', 'Đã cập nhật thông tin khách hàng.');
    }

    public function toggle(User $customer): RedirectResponse|JsonResponse
    {
        if ($customer->isAdmin()) {
            $message = 'Không thể khóa tài khoản quản trị viên.';

            return request()->wantsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : back()->with('error', $message);
        }

        $customer->is_active = ! $customer->isActive();
        $customer->save();

        $message = $customer->is_active
            ? "Đã mở khóa tài khoản {$customer->name}."
            : "Đã khóa tài khoản {$customer->name}.";

        return request()->wantsJson()
            ? response()->json(['success' => true, 'message' => $message, 'is_active' => $customer->is_active])
            : back()->with('success', $message);
    }
}
