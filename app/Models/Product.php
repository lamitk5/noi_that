<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    public const LOW_STOCK_THRESHOLD = 5;

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

    protected $casts = [
        'base_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'views_count' => 'integer',
        'weight' => 'decimal:2',
    ];

    protected $appends = [
        'final_price',
        'is_on_sale',
        'is_in_stock',
        'primary_image_url',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name) . '-' . Str::random(5);
            }
            if (empty($product->sku)) {
                $product->sku = 'FURN-' . strtoupper(Str::random(8));
            }
        });
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

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_variant_id');
    }

    public function isWishlistedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->wishlists()->where('user_id', $user->id)->exists();
    }

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

    public function getFinalPriceAttribute(): float
    {
        return (float) ($this->sale_price ?? $this->base_price);
    }

    public function getIsOnSaleAttribute(): bool
    {
        return ! is_null($this->sale_price) && $this->sale_price < $this->base_price;
    }

    public function getIsInStockAttribute(): bool
    {
        return $this->totalStock() > 0;
    }

    public function getPrimaryImageUrlAttribute(): string
    {
        $primary = $this->relationLoaded('primaryImage')
            ? $this->primaryImage
            : $this->primaryImage()->first();

        $primary = $primary ?? ($this->relationLoaded('images') ? $this->images->first() : $this->images()->first());

        if ($primary && $primary->image_path) {
            if (Str::startsWith($primary->image_path, ['http://', 'https://'])) {
                return $primary->image_path;
            }

            return asset('storage/' . $primary->image_path);
        }

        return 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=800&q=80';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeBestSelling($query, int $limit = 4)
    {
        return $query->where('is_active', true)
            ->whereHas('variants.orderItems.order', function ($q) {
                $q->whereIn('order_status', ['completed', 'confirmed', 'shipping'])
                    ->where('payment_status', '!=', 'failed');
            })
            ->orderByDesc('id')
            ->limit($limit);
    }
}
