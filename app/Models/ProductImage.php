<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'image_path',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'url',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getUrlAttribute(): string
    {
        $path = $this->attributes['image_path'] ?? '';
        if (empty($path)) {
            return 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=900&q=80';
        }

        if (Str::startsWith($path, ['http://', 'https://', '//', 'data:'])) {
            return $path;
        }

        if (Str::startsWith($path, 'picture/')) {
            $filename = basename($path);
            try {
                return route('media.picture', ['filename' => $filename]);
            } catch (\Throwable) {
                return '/media/picture/' . rawurlencode($filename);
            }
        }

        $cleanPath = ltrim($path, '/');
        $encodedSegments = array_map('rawurlencode', explode('/', $cleanPath));
        $encodedPath = implode('/', $encodedSegments);

        if (file_exists(public_path('storage/' . $cleanPath))) {
            return asset('storage/' . $encodedPath);
        }
        if (file_exists(public_path($cleanPath))) {
            return asset($encodedPath);
        }

        return asset('storage/' . $encodedPath);
    }
}
