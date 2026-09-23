<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display admin overview metrics.
     */
    public function index(Request $request): View|JsonResponse
    {
        $totalRevenue = (float) Order::where('order_status', '!=', Order::STATUS_CANCELLED)
            ->where(function ($q) {
                $q->where('payment_status', Order::PAYMENT_PAID)
                    ->orWhere('order_status', Order::STATUS_COMPLETED);
            })
            ->sum('total_price');

        $totalOrders = Order::count();
        $pendingOrders = Order::where('order_status', Order::STATUS_PENDING)->count();
        $totalProducts = Product::count();
        $lowStockCount = \App\Models\ProductVariant::where('stock', '<=', 5)->count();
        $totalCustomers = User::where('role', 'customer')->count();

        $recentOrders = Order::with('items')
            ->latest()
            ->take(10)
            ->get();

        $lowStockProducts = Product::with(['category', 'variants'])
            ->whereHas('variants', function ($q) {
                $q->where('stock', '<=', 5);
            })
            ->take(5)
            ->get();

        $data = compact(
            'totalRevenue',
            'totalOrders',
            'pendingOrders',
            'totalProducts',
            'lowStockCount',
            'totalCustomers',
            'recentOrders',
            'lowStockProducts'
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('admin.dashboard', $data);
    }
}
