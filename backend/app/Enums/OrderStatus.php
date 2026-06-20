<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => '待确认',
            self::CONFIRMED => '已确认',
            self::PROCESSING => '处理中',
            self::SHIPPED => '已发货',
            self::DELIVERED => '已送达',
            self::COMPLETED => '已完成',
            self::CANCELLED => '已取消',
            self::REFUNDED => '已退款',
            self::REJECTED => '已拒绝',
        };
    }

    public function timestampField(): ?string
    {
        return match ($this) {
            self::CONFIRMED => 'confirmed_at',
            self::SHIPPED => 'shipped_at',
            self::DELIVERED => 'delivered_at',
            self::COMPLETED => 'completed_at',
            default => null,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CANCELLED,
            self::REFUNDED,
            self::REJECTED,
        ], true);
    }

    public function canTransitionTo(self $target): bool
    {
        $transitions = [
            self::PENDING->value => [
                self::CONFIRMED->value,
                self::CANCELLED->value,
                self::REJECTED->value,
            ],
            self::CONFIRMED->value => [
                self::PROCESSING->value,
                self::SHIPPED->value,
                self::CANCELLED->value,
            ],
            self::PROCESSING->value => [
                self::SHIPPED->value,
                self::CANCELLED->value,
                self::CONFIRMED->value,
            ],
            self::SHIPPED->value => [
                self::DELIVERED->value,
                self::CANCELLED->value,
                self::PROCESSING->value,
                self::CONFIRMED->value,
            ],
            self::DELIVERED->value => [
                self::COMPLETED->value,
                self::REFUNDED->value,
                self::SHIPPED->value,
            ],
            self::CANCELLED->value => [],
            self::COMPLETED->value => [],
            self::REFUNDED->value => [],
            self::REJECTED->value => [],
        ];

        return in_array($target->value, $transitions[$this->value] ?? [], true);
    }

    public function allowedTransitions(): array
    {
        $transitions = [
            self::PENDING->value => [self::CONFIRMED, self::CANCELLED, self::REJECTED],
            self::CONFIRMED->value => [self::PROCESSING, self::SHIPPED, self::CANCELLED],
            self::PROCESSING->value => [self::SHIPPED, self::CANCELLED, self::CONFIRMED],
            self::SHIPPED->value => [self::DELIVERED, self::CANCELLED, self::PROCESSING, self::CONFIRMED],
            self::DELIVERED->value => [self::COMPLETED, self::REFUNDED, self::SHIPPED],
            self::CANCELLED->value => [],
            self::COMPLETED->value => [],
            self::REFUNDED->value => [],
            self::REJECTED->value => [],
        ];

        return $transitions[$this->value] ?? [];
    }
}
