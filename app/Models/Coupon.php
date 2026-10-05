<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED = 'fixed';
    public const TYPE_SHIPPING = 'shipping';

    public const TYPES = [
        self::TYPE_PERCENT => 'Phần trăm (%)',
        self::TYPE_FIXED => 'Số tiền cố định (₫)',
        self::TYPE_SHIPPING => 'Miễn phí vận chuyển (₫)',
    ];

    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', strtoupper(trim($code)))->first();
    }

    public function isValidFor(float $subtotal, ?string &$error = null): bool
    {
        if (! $this->is_active) {
            $error = 'Mã giảm giá này hiện không khả dụng hoặc đã bị tạm ngưng.';
            return false;
        }

        $now = Carbon::now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            $error = 'Mã giảm giá chưa đến thời gian áp dụng.';
            return false;
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            $error = 'Mã giảm giá đã hết hạn sử dụng.';
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            $error = 'Mã giảm giá đã hết lượt sử dụng.';
            return false;
        }

        if ($this->min_order_amount !== null && $subtotal < (float) $this->min_order_amount) {
            $formattedMin = number_format((float) $this->min_order_amount, 0, ',', '.') . '₫';
            $error = "Mã này chỉ áp dụng cho đơn hàng từ {$formattedMin}.";
            return false;
        }

        return true;
    }

    /**
     * Never exceeds what it discounts: the item subtotal, or the shipping fee for shipping coupons.
     */
    public function calculateDiscount(float $subtotal, float $shippingFee = 0.0): float
    {
        $cap = $this->max_discount_amount !== null ? (float) $this->max_discount_amount : null;

        $discount = match ($this->type) {
            self::TYPE_PERCENT => $subtotal * (float) $this->value / 100.0,
            self::TYPE_SHIPPING => min($shippingFee, (float) $this->value),
            default => (float) $this->value,
        };

        if ($cap !== null) {
            $discount = min($discount, $cap);
        }

        $limit = $this->type === self::TYPE_SHIPPING ? $shippingFee : $subtotal;

        return max(0.0, min(round($discount, 2), $limit));
    }

    public function description(): string
    {
        if ($this->name) {
            return $this->name;
        }

        $money = fn ($v) => number_format((float) $v, 0, ',', '.') . '₫';
        $text = match ($this->type) {
            self::TYPE_PERCENT => 'Giảm ' . rtrim(rtrim((string) $this->value, '0'), '.') . '%'
                . ($this->max_discount_amount ? ' (tối đa ' . $money($this->max_discount_amount) . ')' : ''),
            self::TYPE_SHIPPING => ((float) $this->value >= 1000000 && $this->max_discount_amount === null)
                ? 'Miễn phí vận chuyển'
                : 'Miễn phí vận chuyển (tối đa ' . $money($this->max_discount_amount ?? $this->value) . ')',
            default => 'Giảm trực tiếp ' . $money($this->value),
        };

        return $this->min_order_amount ? $text . ' cho đơn từ ' . $money($this->min_order_amount) : $text;
    }

    /**
     * Shape kept in the session and read by the cart/checkout views.
     */
    public function toCartArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'value' => (float) $this->value,
            'max_discount' => $this->type === self::TYPE_SHIPPING
                ? (float) ($this->max_discount_amount ?? $this->value)
                : ($this->max_discount_amount !== null ? (float) $this->max_discount_amount : null),
            'min_order' => (float) ($this->min_order_amount ?? 0),
            'description' => $this->description(),
        ];
    }
}
