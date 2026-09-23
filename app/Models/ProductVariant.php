<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'color',
        'size',
        'material',
        'price',
        'stock',
        'sku',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    protected $appends = [
        'final_price',
        'is_in_stock',
        'display_label',
        'color_hex',
    ];

    /**
     * Map common Vietnamese wood / finish names to swatch hex colors.
     */
    public const WOOD_HEX = [
        'gỗ sáng' => '#E8D5B7',
        'gỗ sồi sáng' => '#E8D5B7',
        'gỗ sồi trắng' => '#E8D5B7',
        'gỗ sồi tự nhiên' => '#D2B48C',
        'gỗ sồi' => '#D2B48C',
        'gỗ tự nhiên' => '#D2B48C',
        'nâu óc chó' => '#5C4033',
        'gỗ óc chó' => '#5C4033',
        'walnut' => '#5C4033',
        'walnut đậm' => '#3E2723',
        'nâu hạt dẻ' => '#8B4513',
        'gỗ căm xe' => '#8B4513',
        'gỗ xoan đào' => '#A0522D',
        'gỗ tần bì' => '#C4A35A',
        'gỗ maple' => '#F5DEB3',
        'trắng' => '#F5F5F5',
        'trắng vân mây' => '#F5F5F5',
        'đen' => '#2C2C2C',
        'đen nhám' => '#2C2C2C',
        'xám' => '#8C8C8C',
        'xám khói' => '#8C8C8C',
        'xanh rêu' => '#556B2F',
        'vàng kim' => '#D4AF37',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_variant_id');
    }

    public function getFinalPriceAttribute(): float
    {
        $product = $this->relationLoaded('product') ? $this->product : $this->product()->first();
        $price = (float) $this->price;

        if ($product && $product->is_on_sale) {
            $discount = (float) $product->price - (float) $product->sale_price;
            return max(0, $price - $discount);
        }

        return $price;
    }

    public function getIsInStockAttribute(): bool
    {
        return $this->stock > 0;
    }

    public function getDisplayLabelAttribute(): string
    {
        return trim(implode(' · ', array_filter([$this->size, $this->color])));
    }

    public function getColorHexAttribute(): string
    {
        $name = mb_strtolower(trim((string) $this->color));

        if (isset(self::WOOD_HEX[$name])) {
            return self::WOOD_HEX[$name];
        }

        foreach (self::WOOD_HEX as $key => $hex) {
            if ($name !== '' && str_contains($name, $key)) {
                return $hex;
            }
        }

        return '#C4A574';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('stock', '>', 0);
    }
}
