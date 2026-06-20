<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case PENDING = 'pending';
    case PICKED_UP = 'picked_up';
    case SHIPPED = 'shipped';
    case IN_TRANSIT = 'in_transit';
    case CUSTOMS = 'customs';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';
    case RETURNED = 'returned';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => '待发货',
            self::PICKED_UP => '已揽收',
            self::SHIPPED => '已发出',
            self::IN_TRANSIT => '运输中',
            self::CUSTOMS => '清关中',
            self::OUT_FOR_DELIVERY => '派送中',
            self::DELIVERED => '已签收',
            self::FAILED => '派送失败',
            self::RETURNED => '已退回',
            self::CANCELLED => '已取消',
        };
    }

    public function timestampField(): ?string
    {
        return match ($this) {
            self::SHIPPED => 'shipped_at',
            self::IN_TRANSIT => 'in_transit_at',
            self::CUSTOMS => 'customs_at',
            self::DELIVERED => 'delivered_at',
            self::FAILED => 'failed_at',
            default => null,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::DELIVERED,
            self::FAILED,
            self::RETURNED,
            self::CANCELLED,
        ], true);
    }

    public function canTransitionTo(self $target, array $context = []): bool
    {
        $transitions = [
            self::PENDING->value => [
                self::PICKED_UP->value,
                self::SHIPPED->value,
                self::CANCELLED->value,
            ],
            self::PICKED_UP->value => [
                self::SHIPPED->value,
                self::CANCELLED->value,
            ],
            self::SHIPPED->value => [
                self::IN_TRANSIT->value,
                self::CUSTOMS->value,
                self::OUT_FOR_DELIVERY->value,
                self::DELIVERED->value,
                self::CANCELLED->value,
                self::FAILED->value,
            ],
            self::IN_TRANSIT->value => [
                self::CUSTOMS->value,
                self::OUT_FOR_DELIVERY->value,
                self::DELIVERED->value,
                self::CANCELLED->value,
                self::FAILED->value,
                self::SHIPPED->value,
            ],
            self::CUSTOMS->value => [
                self::OUT_FOR_DELIVERY->value,
                self::DELIVERED->value,
                self::IN_TRANSIT->value,
                self::CANCELLED->value,
                self::FAILED->value,
            ],
            self::OUT_FOR_DELIVERY->value => [
                self::DELIVERED->value,
                self::FAILED->value,
                self::RETURNED->value,
                self::CUSTOMS->value,
            ],
            self::DELIVERED->value => [
                self::RETURNED->value,
            ],
            self::FAILED->value => [
                self::OUT_FOR_DELIVERY->value,
                self::RETURNED->value,
                self::IN_TRANSIT->value,
            ],
            self::RETURNED->value => [],
            self::CANCELLED->value => [],
        ];

        return in_array($target->value, $transitions[$this->value] ?? [], true);
    }
}
