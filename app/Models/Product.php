<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'short_description',
        'description',
        'base_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            OrderItem::class,
            ProductVariant::class,
            'product_id',
            'product_variant_id',
            'id',
            'id'
        );
    }

    public const LOW_STOCK_THRESHOLD = 5;

    public function totalStock(): int
    {
        if ($this->relationLoaded('variants')) {
            return (int) $this->variants->sum('stock');
        }

        return (int) $this->variants()->sum('stock');
    }

    public function isOutOfStock(): bool
    {
        return $this->totalStock() <= 0;
    }

    public function isLowStock(): bool
    {
        $stock = $this->totalStock();
        return $stock > 0 && $stock <= self::LOW_STOCK_THRESHOLD;
    }

    public function stockStatusText(): string
    {
        if ($this->isOutOfStock()) {
            return 'Hết hàng';
        }

        if ($this->isLowStock()) {
            return 'Sắp hết hàng (còn ' . $this->totalStock() . ')';
        }

        return 'Còn hàng';
    }

    public function scopeBestSelling($query, int $limit = 4)
    {
        return $query->where('is_active', true)
            ->whereHas('variants.orderItems.order', function ($q) {
                $q->where('order_status', 'completed')
                    ->where('payment_status', 'paid');
            })
            ->withSum(['orderItems as total_sold' => function ($q) {
                $q->whereHas('order', function ($orderQ) {
                    $orderQ->where('order_status', 'completed')
                        ->where('payment_status', 'paid');
                });
            }], 'quantity')
            ->orderByDesc('total_sold')
            ->orderByDesc('id')
            ->limit($limit);
    }
}
