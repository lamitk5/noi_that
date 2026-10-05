<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Records each coupon used on an order. An account may combine several codes on one order
 * and use codes again on later orders. The cache lock serialises concurrent checkouts of one
 * account and the rate limiter slows down code guessing.
 */
class CouponRedemptionService
{
    public const APPLY_ATTEMPTS_PER_MINUTE = 20;

    /**
     * @return array{0: bool, 1: int} allowed, seconds until retry
     */
    public function hitApplyLimit(string $key): array
    {
        $key = 'coupon-apply:' . $key;
        if (RateLimiter::tooManyAttempts($key, self::APPLY_ATTEMPTS_PER_MINUTE)) {
            return [false, RateLimiter::availableIn($key)];
        }

        RateLimiter::hit($key, 60);

        return [true, 0];
    }

    /**
     * Run $callback while holding the per-account checkout lock; null when another checkout of the
     * same account is still running.
     */
    public function withCheckoutLock(?User $user, callable $callback): mixed
    {
        if (! $user) {
            return $callback();
        }

        $lock = Cache::lock('checkout:user:' . $user->id, 30);
        if (! $lock->get()) {
            throw new \RuntimeException('Đơn hàng trước của bạn đang được xử lý, vui lòng đợi vài giây rồi thử lại.');
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }

    /**
     * Must run inside the checkout transaction, after the order row exists.
     *
     * @throws \RuntimeException when the coupon can no longer be used
     */
    public function redeem(User $user, Order $order, Coupon $coupon, float $discount): CouponRedemption
    {
        $claimed = Coupon::whereKey($coupon->id)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
            ->increment('used_count');

        if ($claimed === 0) {
            throw new \RuntimeException("Mã {$coupon->code} vừa hết lượt sử dụng.");
        }

        try {
            return CouponRedemption::create([
                'coupon_id' => $coupon->id,
                'user_id' => $user->id,
                'order_id' => $order->id,
                'code' => $coupon->code,
                'discount_amount' => $discount,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new \RuntimeException("Mã {$coupon->code} đã được dùng cho đơn này.");
        }
    }

    /**
     * A cancelled order gives the account its coupon back.
     */
    public function release(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $redemptions = CouponRedemption::where('order_id', $order->id)->lockForUpdate()->get();
            foreach ($redemptions as $redemption) {
                if ($redemption->coupon_id) {
                    Coupon::whereKey($redemption->coupon_id)->where('used_count', '>', 0)->decrement('used_count');
                }
                $redemption->delete();
            }
        });
    }

    /**
     * Re-attach the coupon when a cancelled order is reopened, unless the account used another one since.
     */
    public function reclaim(Order $order): void
    {
        if (! $order->user_id || ! $order->coupon_code) {
            return;
        }

        $codes = array_values(array_filter(preg_split('/\s*,\s*/', strtoupper((string) $order->coupon_code)) ?: []));
        foreach ($codes as $index => $code) {
            $coupon = Coupon::findByCode($code);
            $inserted = CouponRedemption::query()->insertOrIgnore([
                'coupon_id' => $coupon?->id,
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'code' => $code,
                'discount_amount' => $index === 0 ? ($order->discount_amount ?? 0) : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted && $coupon) {
                $coupon->increment('used_count');
            }
        }
    }
}
