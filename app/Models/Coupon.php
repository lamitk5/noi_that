<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isValidFor(float $subtotal, ?string &$error = null): bool
    {
        if (! $this->is_active) {
            $error = 'M├ú giß║úm gi├í n├áy hiß╗çn kh├┤ng khß║ú dß╗Ñng hoß║╖c ─æ├ú bß╗ï tß║ím ng╞░ng.';
            return false;
        }

        $now = Carbon::now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            $error = 'M├ú giß║úm gi├í ch╞░a ─æß║┐n thß╗¥i gian ├íp dß╗Ñng.';
            return false;
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            $error = 'M├ú giß║úm gi├í ─æ├ú hß║┐t hß║ín sß╗¡ dß╗Ñng.';
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            $error = 'M├ú giß║úm gi├í ─æ├ú hß║┐t l╞░ß╗út sß╗¡ dß╗Ñng.';
            return false;
        }

        if ($this->min_order_amount !== null && $subtotal < (float) $this->min_order_amount) {
            $formattedMin = number_format((float) $this->min_order_amount, 0, ',', '.') . 'Γé½';
            $error = "M├ú n├áy chß╗ë ├íp dß╗Ñng cho ─æ╞ín h├áng tß╗½ {$formattedMin}.";
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $subtotal): float
    {
        if ($this->type === 'percent') {
            $discount = ($subtotal * (float) $this->value) / 100.0;
            if ($this->max_discount_amount !== null && $discount > (float) $this->max_discount_amount) {
                $discount = (float) $this->max_discount_amount;
            }
        } else {
            $discount = (float) $this->value;
        }

        return min(round($discount, 2), $subtotal);
    }
}
