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

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'short_description',
        'description',
        'price',
        'sale_price',
        'stock_quantity',
        'material',
        'dimensions',
        'color',
        'weight',
        'is_featured',
        'is_active',
        'views_count',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'stock_quantity' => 'integer',
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
        return $this->hasMany(ProductImage::class)->orderBy('sort_order', 'asc');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get effective product price.
     */
    public function getFinalPriceAttribute(): float
    {
        return (float) ($this->sale_price ?? $this->price);
    }

    /**
     * Check if product is on sale.
     */
    public function getIsOnSaleAttribute(): bool
    {
        return !is_null($this->sale_price) && $this->sale_price < $this->price;
    }

    /**
     * Check if product is currently in stock.
     */
    public function getIsInStockAttribute(): bool
    {
        return $this->stock_quantity > 0;
    }

    /**
     * Get primary image URL or fallback placeholder.
     */
    public function getPrimaryImageUrlAttribute(): string
    {
        $primary = $this->images->firstWhere('is_primary', true) ?? $this->images->first();

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

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true)->where('is_active', true);
    }

    /**
     * Comprehensive filter scope for furniture catalog.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['category_id'] ?? null, function ($q, $categoryId) {
                $q->where('category_id', $categoryId);
            })
            ->when($filters['category_slug'] ?? null, function ($q, $slug) {
                $q->whereHas('category', function ($sub) use ($slug) {
                    $sub->where('slug', $slug);
                });
            })
            ->when($filters['min_price'] ?? null, function ($q, $min) {
                $q->where(function ($sub) use ($min) {
                    $sub->where(function ($p) use ($min) {
                        $p->whereNull('sale_price')->where('price', '>=', $min);
                    })->orWhere(function ($sp) use ($min) {
                        $sp->whereNotNull('sale_price')->where('sale_price', '>=', $min);
                    });
                });
            })
            ->when($filters['max_price'] ?? null, function ($q, $max) {
                $q->where(function ($sub) use ($max) {
                    $sub->where(function ($p) use ($max) {
                        $p->whereNull('sale_price')->where('price', '<=', $max);
                    })->orWhere(function ($sp) use ($max) {
                        $sp->whereNotNull('sale_price')->where('sale_price', '<=', $max);
                    });
                });
            })
            ->when($filters['material'] ?? null, function ($q, $material) {
                $q->where('material', 'like', '%' . $material . '%');
            })
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhere('material', 'like', '%' . $search . '%');
                });
            })
            ->when($filters['in_stock'] ?? null, function ($q) {
                $q->where('stock_quantity', '>', 0);
            })
            ->when($filters['sort'] ?? 'latest', function ($q, $sort) {
                match ($sort) {
                    'price_asc' => $q->orderByRaw('COALESCE(sale_price, price) ASC'),
                    'price_desc' => $q->orderByRaw('COALESCE(sale_price, price) DESC'),
                    'popular' => $q->orderBy('views_count', 'desc'),
                    default => $q->latest(),
                };
            });
    }
}
