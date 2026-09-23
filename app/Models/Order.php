<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_SHIPPING = 'shipping';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_FAILED = 'failed';

    protected $fillable = [
        'order_code',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'shipping_fee',
        'discount_amount',
        'total_price',
        'payment_method',
        'payment_status',
        'order_status',
        'note',
        'province_id',
        'province_name',
        'district_id',
        'district_name',
        'ward_code',
        'ward_name',
        'ghn_order_code',
        'ghn_status',
        'ghn_expected_delivery_at',
        'ghn_log',
    ];

    protected $casts = [
        'shipping_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'ghn_expected_delivery_at' => 'datetime',
        'ghn_log' => 'array',
    ];

    protected $appends = [
        'order_status_label',
        'payment_status_label',
        'ghn_status_label',
        'ghn_tracking_url',
    ];

    public static function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));
    }

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

    public function getGhnTrackingUrlAttribute(): ?string
    {
        return $this->ghn_order_code
            ? 'https://tracking.ghn.vn/?order_code=' . urlencode($this->ghn_order_code)
            : null;
    }

    public function getGhnStatusLabelAttribute(): string
    {
        return match ($this->ghn_status) {
            'ready_to_pick' => 'Chờ lấy hàng',
            'picking' => 'Đang lấy hàng',
            'picked' => 'Đã lấy hàng',
            'storing' => 'Đang ở kho GHN',
            'transporting' => 'Đang trung chuyển',
            'sorting' => 'Đang phân loại',
            'delivering' => 'Đang giao hàng',
            'delivered' => 'Đã giao hàng',
            'return' => 'Đang hoàn hàng',
            'return_transporting' => 'Đang chuyển hoàn',
            'return_sorting' => 'Phân loại hoàn',
            'returning' => 'Đang hoàn về shop',
            'returned' => 'Đã hoàn về shop',
            'cancel' => 'Đã hủy',
            'delivery_fail' => 'Giao hàng thất bại',
            default => $this->ghn_status ?: 'Chưa tạo đơn',
        };
    }

    /**
     * Compatibility aliases used by feature/lam views/controllers.
     */
    public function getOrderNumberAttribute(): string
    {
        return (string) ($this->order_code ?? $this->getRawOriginal('order_number'));
    }

    public function setOrderNumberAttribute(string $value): void
    {
        $this->attributes['order_code'] = $value;
    }

    public function getNotesAttribute(): ?string
    {
        return $this->note;
    }

    public function setNotesAttribute(?string $value): void
    {
        $this->attributes['note'] = $value;
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) ($this->total_price ?? 0);
    }

    public function setTotalAmountAttribute($value): void
    {
        $this->attributes['total_price'] = $value;
    }

    public function getSubtotalAttribute(): float
    {
        if (array_key_exists('subtotal', $this->attributes)) {
            return (float) $this->attributes['subtotal'];
        }

        return max(0, (float) $this->total_price - (float) $this->shipping_fee + (float) $this->discount_amount);
    }

    public function setSubtotalAttribute($value): void
    {
        // Live schema stores only total_price; keep subtotal out of INSERT.
        $this->attributes['_subtotal'] = $value;
    }

    public function getOrderStatusLabelAttribute(): string
    {
        return match ($this->order_status) {
            self::STATUS_PENDING => 'Chờ xử lý',
            self::STATUS_CONFIRMED => 'Đã xác nhận',
            self::STATUS_SHIPPING => 'Đang giao hàng',
            self::STATUS_COMPLETED => 'Hoàn thành',
            self::STATUS_CANCELLED => 'Đã hủy',
            default => 'Không xác định',
        };
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_PENDING => 'Chờ thanh toán',
            self::PAYMENT_PAID => 'Đã thanh toán',
            self::PAYMENT_FAILED => 'Thanh toán thất bại',
            default => 'Chưa rõ',
        };
    }
}
