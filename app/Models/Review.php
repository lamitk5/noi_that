<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'rating',
        'comment',
        'is_approved',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_approved' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * A review counts only when its order was delivered to the same account that wrote it.
     */
    public function scopeVerifiedPurchase($query)
    {
        return $query->whereHas('order', function ($orders) {
            $orders->whereColumn('orders.user_id', 'reviews.user_id')
                ->where('orders.order_status', Order::STATUS_COMPLETED);
        });
    }

    public function isVerifiedPurchase(): bool
    {
        return $this->order_id !== null
            && $this->order !== null
            && (int) $this->order->user_id === (int) $this->user_id
            && $this->order->order_status === Order::STATUS_COMPLETED;
    }
}
