<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SupportRequest extends Model
{
    public const TYPE_RETURN = 'return';
    public const TYPE_COMPLAINT = 'complaint';

    public const STATUS_PENDING = 'pending';
    public const STATUS_REVIEWING = 'reviewing';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';

    public const RETURN_WINDOW_DAYS = 7;

    public const TYPES = [
        self::TYPE_RETURN => 'Hoàn hàng',
        self::TYPE_COMPLAINT => 'Khiếu nại',
    ];

    public const REASONS = [
        self::TYPE_RETURN => [
            'damaged' => 'Hàng bị hư hỏng, trầy xước khi nhận',
            'wrong_item' => 'Giao sai mẫu, màu hoặc kích thước',
            'defective' => 'Lỗi kỹ thuật, lỗi lắp ráp',
            'not_as_described' => 'Không đúng mô tả, hình ảnh',
            'changed_mind' => 'Không hợp không gian, không còn nhu cầu',
        ],
        self::TYPE_COMPLAINT => [
            'delivery' => 'Giao hàng chậm hoặc thái độ giao hàng',
            'quality' => 'Chất lượng sản phẩm',
            'service' => 'Thái độ tư vấn, chăm sóc khách hàng',
            'payment' => 'Thanh toán, hoàn tiền',
            'warranty' => 'Bảo hành, lắp đặt',
            'other' => 'Vấn đề khác',
        ],
    ];

    public const RESOLUTIONS = [
        'refund' => 'Trả hàng, hoàn tiền',
        'exchange' => 'Đổi sản phẩm mới',
    ];

    public const STATUSES = [
        self::STATUS_PENDING => 'Chờ tiếp nhận',
        self::STATUS_REVIEWING => 'Đang xử lý',
        self::STATUS_APPROVED => 'Đã chấp nhận',
        self::STATUS_REJECTED => 'Từ chối',
        self::STATUS_COMPLETED => 'Hoàn tất',
    ];

    public const OPEN_STATUSES = [self::STATUS_PENDING, self::STATUS_REVIEWING, self::STATUS_APPROVED];

    protected $fillable = [
        'code',
        'user_id',
        'order_id',
        'type',
        'reason',
        'subject',
        'description',
        'items',
        'photos',
        'resolution',
        'refund_account',
        'refund_amount',
        'status',
        'admin_note',
        'handled_by',
        'resolved_at',
        'restocked_at',
    ];

    protected $casts = [
        'items' => 'array',
        'photos' => 'array',
        'refund_amount' => 'decimal:2',
        'resolved_at' => 'datetime',
        'restocked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (SupportRequest $request) {
            if (empty($request->code)) {
                $prefix = $request->type === self::TYPE_RETURN ? 'HH' : 'KN';
                do {
                    $code = $prefix.'-'.date('ymd').'-'.strtoupper(Str::random(5));
                } while (static::where('code', $code)->exists());
                $request->code = $code;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->type][$this->reason] ?? $this->reason;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function resolutionLabel(): ?string
    {
        return $this->resolution ? (self::RESOLUTIONS[$this->resolution] ?? $this->resolution) : null;
    }

    /**
     * @return array<string, string>
     */
    public function allowedStatuses(): array
    {
        if ($this->type === self::TYPE_COMPLAINT) {
            return array_diff_key(self::STATUSES, [self::STATUS_APPROVED => true]);
        }

        return self::STATUSES;
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'bg-amber-500/10 text-amber-700 border-amber-500/30',
            self::STATUS_REVIEWING => 'bg-sky-500/10 text-sky-700 border-sky-500/30',
            self::STATUS_APPROVED => 'bg-indigo-500/10 text-indigo-700 border-indigo-500/30',
            self::STATUS_REJECTED => 'bg-rose-500/10 text-rose-700 border-rose-500/30',
            self::STATUS_COMPLETED => 'bg-emerald-500/10 text-emerald-700 border-emerald-500/30',
            default => 'bg-stone-500/10 text-stone-700 border-stone-500/30',
        };
    }

    public function itemsTotal(): float
    {
        return collect($this->items ?? [])->sum(fn ($item) => (float) $item['price'] * (int) $item['quantity']);
    }

    /**
     * @return array<int, string>
     */
    public function photoUrls(): array
    {
        return array_map(fn (string $file) => route('media.picture', ['filename' => $file]), $this->photos ?? []);
    }
}
