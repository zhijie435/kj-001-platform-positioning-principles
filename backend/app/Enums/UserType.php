<?php

namespace App\Enums;

enum UserType: string
{
    case PLATFORM = 'platform';
    case SUPPLIER = 'supplier';
    case DISTRIBUTOR = 'distributor';

    public function label(): string
    {
        return match ($this) {
            self::PLATFORM => '平台管理员',
            self::SUPPLIER => '供应商',
            self::DISTRIBUTOR => '分销商',
        };
    }
}
