<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PACKED = 'packed';
    case SHIPPING = 'shipping';
    case COMPLETED = 'completed';
    case CANCELED = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Đang xử lý',
            self::CONFIRMED => 'Đã xác nhận',
            self::PACKED => 'Đã đóng gói',
            self::SHIPPING => 'Đang vận chuyển',
            self::COMPLETED => 'Đã giao',
            self::CANCELED => 'Đã hủy',
        };
    }

    public static function labels(): array
    {
        return [
            self::PENDING->value => 'Đang xử lý',
            self::CONFIRMED->value => 'Đã xác nhận',
            self::PACKED->value => 'Đã đóng gói',
            self::SHIPPING->value => 'Đang vận chuyển',
            self::COMPLETED->value => 'Đã giao',
            self::CANCELED->value => 'Đã hủy',
        ];
    }
}
