<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => '待处理',
            self::COMPLETED => '已完成',
            self::FAILED => '已失败',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        $transitions = [
            self::PENDING->value => [
                self::COMPLETED->value,
                self::FAILED->value,
            ],
            self::COMPLETED->value => [],
            self::FAILED->value => [self::PENDING->value],
        ];

        return in_array($target->value, $transitions[$this->value] ?? [], true);
    }
}
