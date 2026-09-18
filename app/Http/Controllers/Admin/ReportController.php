<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Display sales analytics and revenue performance reports.
     */
    public function index(Request $request): View
    {
        $preset = $request->query('preset', '30days');
        $now = Carbon::now();

        if ($preset === '7days') {
            $startDate = $now->copy()->subDays(6)->startOfDay();
            $endDate = $now->copy()->endOfDay();
        } elseif ($preset === 'this_month') {
            $startDate = $now->copy()->startOfMonth();
            $endDate = $now->copy()->endOfDay();
        } elseif ($preset === 'all_time') {
            $startDate = Carbon::create(2020, 1, 1)->startOfDay();
            $endDate = $now->copy()->endOfDay();
        } elseif ($request->filled('from_date') && $request->filled('to_date')) {
            $startDate = Carbon::parse($request->query('from_date'))->startOfDay();
            $endDate = Carbon::parse($request->query('to_date'))->endOfDay();
            $preset = 'custom';
        } else {
            // Default 30 days
            $preset = '30days';
            $startDate = $now->copy()->subDays(29)->startOfDay();
            $endDate = $now->copy()->endOfDay();
        }

        // Base query for authoritative revenue
        $completedPaidOrdersQuery = Order::query()
            ->where('order_status', Order::STATUS_COMPLETED)
            ->where('payment_status', Order::PAYMENT_PAID)
            ->whereBetween('created_at', [$startDate, $endDate]);

        $totalRevenue = (float) (clone $completedPaidOrdersQuery)->sum('total_price');
        $totalOrdersCount = (int) (clone $completedPaidOrdersQuery)->count();
        $averageOrderValue = $totalOrdersCount > 0 ? (int) ($totalRevenue / $totalOrdersCount) : 0;

        $totalItemsSold = (int) OrderItem::query()
            ->whereHas('order', function ($query) use ($startDate, $endDate) {
                $query->where('order_status', Order::STATUS_COMPLETED)
                    ->where('payment_status', Order::PAYMENT_PAID)
                    ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->sum('quantity');

        // Revenue by Payment Method
        $paymentBreakdown = (clone $completedPaidOrdersQuery)
            ->select('payment_method', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(total_price) as total_revenue'))
            ->groupBy('payment_method')
            ->get()
            ->map(function ($item) use ($totalRevenue) {
                $item->share_percentage = $totalRevenue > 0
                    ? round(($item->total_revenue / $totalRevenue) * 100, 1)
                    : 0;
                return $item;
            });

        // Top 10 Best-Selling Products in this period
        $topProducts = OrderItem::query()
            ->whereHas('order', function ($query) use ($startDate, $endDate) {
                $query->where('order_status', Order::STATUS_COMPLETED)
                    ->where('payment_status', Order::PAYMENT_PAID)
                    ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->select(
                'product_name',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(quantity * price) as total_revenue')
            )
            ->groupBy('product_name')
            ->orderByDesc('total_quantity')
            ->take(10)
            ->get();

        // Daily Breakdown for chart/table
        $dailyRecords = (clone $completedPaidOrdersQuery)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_price) as daily_revenue')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        return view('admin.reports.index', [
            'preset' => $preset,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'metrics' => [
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrdersCount,
                'aov' => $averageOrderValue,
                'items_sold' => $totalItemsSold,
            ],
            'paymentBreakdown' => $paymentBreakdown,
            'topProducts' => $topProducts,
            'dailyRecords' => $dailyRecords,
        ]);
    }
}
