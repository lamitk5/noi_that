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
        'sale_price',
        'material',
        'dimensions',
        'color',
        'weight',
        'is_featured',
        'is_active',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'weight' => 'decimal:2',
            'views_count' => 'integer',
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

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true)->latest();
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function isWishlistedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->wishlists()->where('user_id', $user->id)->exists();
    }

    public function averageRating(): float
    {
        if ($this->relationLoaded('reviews')) {
            $approved = $this->reviews->where('is_approved', true);
            return $approved->count() > 0 ? round((float) $approved->avg('rating'), 1) : 5.0;
        }

        $avg = $this->approvedReviews()->avg('rating');
        return $avg ? round((float) $avg, 1) : 5.0;
    }

    public function reviewsCount(): int
    {
        if ($this->relationLoaded('reviews')) {
            return $this->reviews->where('is_approved', true)->count();
        }

        return $this->approvedReviews()->count();
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function getPriceAttribute(): float
    {
        return (float) $this->base_price;
    }

    public function setPriceAttribute($value): void
    {
        $this->attributes['base_price'] = $value;
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->sale_price !== null
            && (float) $this->sale_price > 0
            && (float) $this->sale_price < (float) $this->base_price;
    }

    public function getFinalPriceAttribute(): float
    {
        return $this->is_on_sale ? (float) $this->sale_price : (float) $this->base_price;
    }

    public function getDiscountPercentAttribute(): int
    {
        if (! $this->is_on_sale) {
            return 0;
        }

        $base = (float) $this->base_price;
        if ($base <= 0) {
            return 0;
        }

        return (int) round((1 - ((float) $this->sale_price / $base)) * 100);
    }

    public function getStockQuantityAttribute(): int
    {
        return $this->totalStock();
    }

    public function getIsInStockAttribute(): bool
    {
        return !$this->isOutOfStock();
    }

    public function getPrimaryImageUrlAttribute(): string
    {
        return $this->primaryImage?->url
            ?? 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=800&q=80';
    }

    public function scopeBestSelling($query, int $limit = 4)
    {
        return $query->where('is_active', true)
            ->whereHas('variants.orderItems.order', function ($q) {
                $q->whereIn('order_status', ['completed', 'confirmed', 'shipping'])
                    ->where('payment_status', '!=', 'failed');
            })
            ->withSum(['orderItems as total_sold' => function ($q) {
                $q->whereHas('order', function ($orderQ) {
                    $orderQ->whereIn('order_status', ['completed', 'confirmed', 'shipping'])
                        ->where('payment_status', '!=', 'failed');
                });
            }], 'quantity')
            ->orderByDesc('total_sold')
            ->orderByDesc('id')
            ->limit($limit);
    }
}
