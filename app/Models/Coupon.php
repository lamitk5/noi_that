<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

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

    public function calculateDiscount(float $subtotal): float
    {
        if ($this->type === 'percent') {
            $discount = ($subtotal * (float) $this->value) / 100.0;
            if ($this->max_discount_amount !== null && $discount > (float) $this->max_discount_amount) {
                $discount = (float) $this->max_discount_amount;
            }
        } else {
            $discount = (float) $this->value;
        }

        return min(round($discount, 2), $subtotal);
    }
}
