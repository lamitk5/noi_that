<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'ticket_code',
        'user_id',
        'name',
        'email',
        'phone',
        'subject',
        'category',
        'priority',
        'status',
        'message',
        'last_reply_at',
    ];

    protected function casts(): array
    {
        return [
            'last_reply_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_code)) {
                $ticket->ticket_code = 'TK-' . strtoupper(Str::random(8));
            }
            if (empty($ticket->last_reply_at)) {
                $ticket->last_reply_at = now();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->orderBy('created_at', 'asc');
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'bg-amber-500/10 text-amber-600 border-amber-500/20 dark:text-amber-400',
            self::STATUS_IN_PROGRESS => 'bg-blue-500/10 text-blue-600 border-blue-500/20 dark:text-blue-400',
            self::STATUS_RESOLVED => 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20 dark:text-emerald-400',
            self::STATUS_CLOSED => 'bg-stone-500/10 text-stone-600 border-stone-500/20 dark:text-stone-400',
            default => 'bg-surface-alt text-muted border-ui-border',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'Đang chờ xử lý',
            self::STATUS_IN_PROGRESS => 'Đang xử lý',
            self::STATUS_RESOLVED => 'Đã giải quyết',
            self::STATUS_CLOSED => 'Đã đóng',
            default => $this->status,
        };
    }

    public function priorityLabel(): string
    {
        return match ($this->priority) {
            'low' => 'Thấp',
            'normal' => 'Bình thường',
            'high' => 'Cao',
            'urgent' => 'Khẩn cấp',
            default => $this->priority,
        };
    }
}
