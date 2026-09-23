<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Resolve stored path to a browser-ready absolute URL.
     * picture/... → /media/picture/...; http(s) passthrough; else /storage/...
     */
    public function getUrlAttribute(): string
    {
        $path = $this->image_path;

        if (! $path) {
            return '';
        }

        if (Str::startsWith($path, ['http://', 'https://', '//', 'data:'])) {
            return $path;
        }

        if (Str::startsWith($path, '/')) {
            return $path;
        }

        if (Str::startsWith($path, 'picture/')) {
            $filename = basename($path);
            // EncodeURI-style: always absolute from site root, encode spaces/diacritics
            try {
                return route('media.picture', ['filename' => $filename]);
            } catch (\Throwable) {
                return '/media/picture/'.rawurlencode($filename);
            }
        }

        return asset('storage/'.ltrim($path, '/'));
    }
}

