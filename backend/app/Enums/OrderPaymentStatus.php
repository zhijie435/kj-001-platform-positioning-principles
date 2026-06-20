<?php

namespace App\Enums;

enum OrderPaymentStatus: string
{
    case UNPAID = 'unpaid';
    case PARTIAL = 'partial';
    case PAID = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::UNPAID => '未付款',
            self::PARTIAL => '部分付款',
            self::PAID => '已付款',
        };
    }

    public static function fromAmount(float $paid, float $total): self
    {
        if ($total <= 0 || $paid <= 0) {
            return self::UNPAID;
        }

        if ($paid >= $total) {
            return self::PAID;
        }

        return self::PARTIAL;
    }
}
