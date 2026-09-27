<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffController extends Controller
{
    protected const MANAGED_ROLES = ['staff', 'manager'];

    /**
     * List staff members.
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = User::whereIn('role', self::MANAGED_ROLES)
            ->withCount('orders');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        $staff = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $staff]);
        }

        return view('admin.staff.index', compact('staff'));
    }

    /**
     * Create form.
     */
    public function create(): View
    {
        return view('admin.staff.create');
    }

    /**
     * Store new staff member.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', Rule::in(self::MANAGED_ROLES)],
        ], [
            'name.required' => 'Vui lòng nhập họ tên nhân viên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.unique' => 'Email đã được sử dụng.',
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'username.unique' => 'Tên đăng nhập đã được sử dụng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'role.required' => 'Vui lòng chọn vai trò.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'phone' => $validated['phone'] ?? null,
            'username' => strtolower($validated['username']),
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Tạo nhân viên thành công!', 'data' => $user], 201);
        }

        return redirect()->route('admin.staff.index')->with('success', 'Tạo nhân viên thành công!');
    }

    /**
     * Edit form.
     */
    public function edit(User $staff): View
    {
        abort_unless(in_array($staff->role, self::MANAGED_ROLES), 403);

        return view('admin.staff.edit', compact('staff'));
    }

    /**
     * Update staff member.
     */
    public function update(Request $request, User $staff): RedirectResponse|JsonResponse
    {
        abort_unless(in_array($staff->role, self::MANAGED_ROLES), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff->id)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($staff->id)],
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($staff->id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'role' => ['required', Rule::in(self::MANAGED_ROLES)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $staff->name = $validated['name'];
        $staff->email = strtolower($validated['email']);
        $staff->phone = $validated['phone'] ?? null;
        $staff->username = strtolower($validated['username']);
        $staff->role = $validated['role'];
        $staff->is_active = $request->boolean('is_active', true);

        if (! empty($validated['password'])) {
            $staff->password = Hash::make($validated['password']);
        }

        $staff->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Cập nhật nhân viên thành công!']);
        }

        return redirect()->route('admin.staff.index')->with('success', 'Cập nhật nhân viên thành công!');
    }

    /**
     * Delete staff member.
     */
    public function destroy(Request $request, User $staff): RedirectResponse|JsonResponse
    {
        abort_unless(in_array($staff->role, self::MANAGED_ROLES), 403);

        if ($staff->id === auth()->id()) {
            $msg = 'Không thể xóa tài khoản của chính bạn.';
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $staff->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã xóa nhân viên.']);
        }

        return redirect()->route('admin.staff.index')->with('success', 'Đã xóa nhân viên.');
    }

    /**
     * Toggle active status.
     */
    public function toggle(Request $request, User $staff): RedirectResponse|JsonResponse
    {
        abort_unless(in_array($staff->role, self::MANAGED_ROLES), 403);

        $staff->is_active = ! $staff->is_active;
        $staff->save();

        $msg = $staff->is_active ? 'Đã kích hoạt nhân viên.' : 'Đã tạm ngưng nhân viên.';

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg, 'is_active' => $staff->is_active]);
        }

        return back()->with('success', $msg);
    }
}
