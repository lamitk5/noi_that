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
 * Each account may redeem exactly one coupon, ever.
 *
 * The unique index on coupon_redemptions.user_id is the hard guarantee. The cache lock serialises
 * concurrent checkouts of one account and the rate limiter slows down code guessing; both use the
 * configured cache store, so they move to Redis automatically when CACHE_STORE=redis.
 */
class CouponRedemptionService
{
    public const APPLY_ATTEMPTS_PER_MINUTE = 5;

    public const USED_MESSAGE = 'Mỗi tài khoản chỉ được sử dụng 1 mã giảm giá. Tài khoản của bạn đã dùng mã :code.';

    public function redemptionFor(User $user): ?CouponRedemption
    {
        return CouponRedemption::where('user_id', $user->id)->first();
    }

    public function usedMessage(CouponRedemption $redemption): string
    {
        return str_replace(':code', $redemption->code, self::USED_MESSAGE);
    }

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
        if ($existing = CouponRedemption::where('user_id', $user->id)->lockForUpdate()->first()) {
            throw new \RuntimeException($this->usedMessage($existing));
        }

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
            throw new \RuntimeException(str_replace(':code', $coupon->code, self::USED_MESSAGE));
        }
    }

    /**
     * A cancelled order gives the account its coupon back.
     */
    public function release(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $redemption = CouponRedemption::where('order_id', $order->id)->lockForUpdate()->first();
            if (! $redemption) {
                return;
            }

            if ($redemption->coupon_id) {
                Coupon::whereKey($redemption->coupon_id)->where('used_count', '>', 0)->decrement('used_count');
            }
            $redemption->delete();
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

        $coupon = Coupon::findByCode($order->coupon_code);
        $inserted = CouponRedemption::query()->insertOrIgnore([
            'coupon_id' => $coupon?->id,
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'code' => strtoupper($order->coupon_code),
            'discount_amount' => $order->discount_amount ?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($inserted && $coupon) {
            $coupon->increment('used_count');
        }
    }
}
