<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_code',
        'customer_name',
        'customer_phone',
        'customer_email',
        'shipping_address',
        'note',
        'total_price',
        'shipping_fee',
        'payment_method',
        'payment_status',
        'order_status',
    ];

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
        ];
    }

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_SHIPPING = 'shipping';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'canceled';

    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_FAILED = 'failed';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function getOrderNumberAttribute(): ?string
    {
        return $this->order_code;
    }

    public function setOrderNumberAttribute($value): void
    {
        $this->attributes['order_code'] = $value;
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->total_price;
    }

    public function setTotalAmountAttribute($value): void
    {
        $this->attributes['total_price'] = $value;
    }

    public function getNotesAttribute(): ?string
    {
        return $this->note;
    }

    public function setNotesAttribute($value): void
    {
        $this->attributes['note'] = $value;
    }

    public function getSubtotalAttribute(): float
    {
        return (float) ($this->total_price - $this->shipping_fee);
    }

    public function getOrderStatusLabelAttribute(): string
    {
        return match ($this->order_status) {
            'pending' => 'Chờ xử lý',
            'confirmed' => 'Đã xác nhận',
            'shipping' => 'Đang giao hàng',
            'completed' => 'Hoàn thành',
            'canceled', 'cancelled' => 'Đã hủy',
            default => ucfirst($this->order_status),
        };
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            'pending' => 'Chờ thanh toán',
            'paid' => 'Đã thanh toán',
            'failed' => 'Thất bại',
            default => ucfirst($this->payment_status),
        };
    }

    public static function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6));
    }
}
