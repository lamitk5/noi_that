<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
