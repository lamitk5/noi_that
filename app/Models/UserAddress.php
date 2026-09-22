<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'recipient_name',
        'phone',
        'address_line',
        'ward',
        'district',
        'city',
        'is_default',
        'province_id',
        'district_id',
        'ward_code',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'province_id' => 'integer',
            'district_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullAddressAttribute(): string
    {
        return collect([$this->address_line, $this->ward, $this->district, $this->city])
            ->filter()
            ->implode(', ');
    }
}
