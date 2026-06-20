<?php

namespace App\Repositories;

use App\Models\Shipment;

class ShipmentRepository extends BaseRepository
{
    protected string $modelClass = Shipment::class;

    protected array $searchColumns = ['tracking_no', 'receiver_name', 'receiver_phone'];

    protected array $filterColumns = [
        'order_id' => ['column' => 'order_id', 'operator' => '=', 'type' => 'int'],
        'status' => ['column' => 'status', 'operator' => '='],
        'shipping_method_id' => ['column' => 'shipping_method_id', 'operator' => '=', 'type' => 'int'],
        'origin_market_id' => ['column' => 'origin_market_id', 'operator' => '=', 'type' => 'int'],
        'destination_market_id' => ['column' => 'destination_market_id', 'operator' => '=', 'type' => 'int'],
        'origin_warehouse_id' => ['column' => 'origin_warehouse_id', 'operator' => '=', 'type' => 'int'],
        'destination_warehouse_id' => ['column' => 'destination_warehouse_id', 'operator' => '=', 'type' => 'int'],
        'shipped_from' => ['column' => 'shipped_at', 'type' => 'date_from'],
        'shipped_to' => ['column' => 'shipped_at', 'type' => 'date_to'],
    ];
}
