<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the Admin Dashboard with real-time operational metrics.
     */
    public function index(Request $request): View
    {
        $revenue = (float) Order::where('order_status', Order::STATUS_COMPLETED)
            ->where('payment_status', Order::PAYMENT_PAID)
            ->sum('total_price');

        $completedOrdersCount = Order::where('order_status', Order::STATUS_COMPLETED)->count();
        $pendingOrdersCount = Order::where('order_status', Order::STATUS_PENDING)->count();
        $totalCustomersCount = User::where('role', 'customer')->count();
        $activeProductsCount = Product::where('is_active', true)->count();
        $lowStockVariantsCount = ProductVariant::where('stock', '>', 0)
            ->where('stock', '<=', Product::lowStockThreshold())
            ->count();
        $outOfStockVariantsCount = ProductVariant::where('stock', '<=', 0)->count();

        $recentOrders = Order::with('user')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', [
            'user' => $request->user(),
            'stats' => [
                'revenue' => $revenue,
                'completed_orders' => $completedOrdersCount,
                'pending_orders' => $pendingOrdersCount,
                'total_customers' => $totalCustomersCount,
                'active_products' => $activeProductsCount,
                'low_stock_variants' => $lowStockVariantsCount,
                'out_of_stock_variants' => $outOfStockVariantsCount,
            ],
            'recentOrders' => $recentOrders,
        ]);
    }
}
