<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DEFAULT_COUPONS = [
        ['code' => 'MOCAN10', 'name' => 'Giảm 10% (tối đa 500.000₫)', 'type' => 'percent', 'value' => 10, 'max_discount_amount' => 500000, 'min_order_amount' => null],
        ['code' => 'MOCAN20', 'name' => 'Giảm 20% (tối đa 1.000.000₫ cho đơn từ 3tr)', 'type' => 'percent', 'value' => 20, 'max_discount_amount' => 1000000, 'min_order_amount' => 3000000],
        ['code' => 'FREESHIP', 'name' => 'Miễn phí vận chuyển (tối đa 50.000₫)', 'type' => 'shipping', 'value' => 50000, 'max_discount_amount' => 50000, 'min_order_amount' => null],
        ['code' => 'VIP500', 'name' => 'Giảm trực tiếp 500.000₫ cho đơn từ 5tr', 'type' => 'fixed', 'value' => 500000, 'max_discount_amount' => 500000, 'min_order_amount' => 5000000],
        ['code' => 'CHAOBAN50', 'name' => 'Giảm 50.000₫ cho đơn từ 500.000₫', 'type' => 'fixed', 'value' => 50000, 'max_discount_amount' => 50000, 'min_order_amount' => 500000],
    ];

    public function up(): void
    {
        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->unique()->constrained('orders')->cascadeOnDelete();
            $table->string('code', 50);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        $now = now();
        foreach (self::DEFAULT_COUPONS as $coupon) {
            DB::table('coupons')->insertOrIgnore($coupon + [
                'used_count' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $couponIds = DB::table('coupons')->pluck('id', 'code');
        $firstUse = DB::table('orders')
            ->whereNotNull('user_id')
            ->whereNotNull('coupon_code')
            ->where('coupon_code', '!=', '')
            ->whereNotIn('order_status', ['cancelled', 'canceled'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'user_id', 'coupon_code', 'discount_amount', 'created_at'])
            ->unique('user_id');

        foreach ($firstUse as $order) {
            $code = strtoupper($order->coupon_code);
            DB::table('coupon_redemptions')->insertOrIgnore([
                'coupon_id' => $couponIds[$code] ?? null,
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'code' => $code,
                'discount_amount' => $order->discount_amount ?? 0,
                'created_at' => $order->created_at ?? $now,
                'updated_at' => $now,
            ]);
        }

        foreach (DB::table('coupon_redemptions')->whereNotNull('coupon_id')->select('coupon_id', DB::raw('count(*) as total'))->groupBy('coupon_id')->get() as $row) {
            DB::table('coupons')->where('id', $row->coupon_id)->where('used_count', '<', $row->total)->update(['used_count' => $row->total]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
    }
};
