<?php

namespace App\Repositories;

use App\Models\Order;

class OrderRepository extends BaseRepository
{
    protected string $modelClass = Order::class;

    protected array $searchColumns = ['order_no', 'tracking_no'];

    protected array $filterColumns = [
        'status' => ['column' => 'status', 'operator' => '='],
        'payment_status' => ['column' => 'payment_status', 'operator' => '='],
        'supplier_id' => ['column' => 'supplier_id', 'operator' => '=', 'type' => 'int'],
        'distributor_id' => ['column' => 'distributor_id', 'operator' => '=', 'type' => 'int'],
        'market_id' => ['column' => 'market_id', 'operator' => '=', 'type' => 'int'],
        'is_cross_border' => ['column' => 'is_cross_border', 'operator' => '=', 'type' => 'bool'],
        'type' => ['column' => 'type', 'operator' => '='],
        'created_from' => ['column' => 'created_at', 'type' => 'date_from'],
        'created_to' => ['column' => 'created_at', 'type' => 'date_to'],
    ];
}
