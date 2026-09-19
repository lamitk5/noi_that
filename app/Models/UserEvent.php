<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserEvent extends Model
{
    use HasFactory;

    public const EVENT_VIEW_PRODUCT = 'view_product';
    public const EVENT_ADD_TO_CART = 'add_to_cart';
    public const EVENT_CHECKOUT_STARTED = 'checkout_started';
    public const EVENT_CART_ABANDONED = 'cart_abandoned';

    protected $fillable = [
        'user_id',
        'session_id',
        'event_type',
        'entity_type',
        'entity_id',
        'payload',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
