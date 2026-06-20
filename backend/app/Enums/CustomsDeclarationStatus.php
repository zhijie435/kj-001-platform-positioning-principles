<?php

namespace App\Enums;

enum CustomsDeclarationStatus: string
{
    case PENDING = 'pending';
    case DECLARED = 'declared';
    case INSPECTING = 'inspecting';
    case RELEASED = 'released';
    case REJECTED = 'rejected';
    case APPEALING = 'appealing';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => '待申报',
            self::DECLARED => '已申报',
            self::INSPECTING => '查验中',
            self::RELEASED => '已放行',
            self::REJECTED => '已驳回',
            self::APPEALING => '申诉中',
        };
    }

    public function timestampField(): ?string
    {
        return match ($this) {
            self::DECLARED => 'declaration_date',
            self::RELEASED => 'release_date',
            default => null,
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::RELEASED;
    }

    public function canTransitionTo(self $target): bool
    {
        $transitions = [
            self::PENDING->value => [
                self::DECLARED->value,
            ],
            self::DECLARED->value => [
                self::INSPECTING->value,
                self::RELEASED->value,
                self::REJECTED->value,
            ],
            self::INSPECTING->value => [
                self::RELEASED->value,
                self::REJECTED->value,
            ],
            self::RELEASED->value => [],
            self::REJECTED->value => [
                self::APPEALING->value,
                self::DECLARED->value,
            ],
            self::APPEALING->value => [
                self::RELEASED->value,
                self::REJECTED->value,
            ],
        ];

        return in_array($target->value, $transitions[$this->value] ?? [], true);
    }
}
