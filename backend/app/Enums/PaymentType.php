<?php

namespace App\Enums;

enum PaymentType: string
{
    case ESCROW_DEPOSIT = 'escrow_deposit';
    case ESCROW_RELEASE = 'escrow_release';
    case PLATFORM_FEE = 'platform_fee';
    case REFUND = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::ESCROW_DEPOSIT => '托管存款',
            self::ESCROW_RELEASE => '托管释放',
            self::PLATFORM_FEE => '平台费用',
            self::REFUND => '退款',
        };
    }

    public function isIncome(): bool
    {
        return in_array($this, [self::ESCROW_DEPOSIT, self::PLATFORM_FEE], true);
    }

    public function isExpense(): bool
    {
        return in_array($this, [self::ESCROW_RELEASE, self::REFUND], true);
    }

    public function affectsOrderPaymentStatus(): bool
    {
        return in_array($this, [self::ESCROW_DEPOSIT, self::REFUND], true);
    }
}
