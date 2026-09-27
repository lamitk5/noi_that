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
    public function index(Request $request): View|JsonResponse
    {
        $totalRevenue = (float) Order::whereNotIn('order_status', ['canceled', 'cancelled'])
            ->where(function ($q) {
                $q->where('payment_status', 'paid')
                    ->orWhere('order_status', 'completed');
            })
            ->sum('total_price');

        $totalOrders = Order::count();
        $pendingOrders = Order::where('order_status', 'pending')->count();
        $totalProducts = Product::count();
        $lowStockCount = Product::whereHas('variants', function ($q) {
            $q->where('stock', '<=', 5);
        })->count();
        $totalCustomers = User::where('role', 'customer')->count();

        $recentOrders = Order::with('items')
            ->latest()
            ->take(8)
            ->get();

        $lowStockProducts = Product::with(['category', 'variants'])
            ->whereHas('variants', function ($q) {
                $q->where('stock', '<=', 5);
            })
            ->take(5)
            ->get();

        $user = $request->user();

        $data = compact(
            'user',
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

        return app(AnalyticsController::class)->index($request);
    }
}
