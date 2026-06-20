<?php

namespace App\Repositories;

use App\Models\CustomsDeclaration;

class CustomsDeclarationRepository extends BaseRepository
{
    protected string $modelClass = CustomsDeclaration::class;

    protected array $searchColumns = ['declaration_no', 'declarant', 'customs_broker'];

    protected array $filterColumns = [
        'order_id' => ['column' => 'order_id', 'operator' => '=', 'type' => 'int'],
        'shipment_id' => ['column' => 'shipment_id', 'operator' => '=', 'type' => 'int'],
        'type' => ['column' => 'type', 'operator' => '='],
        'status' => ['column' => 'status', 'operator' => '='],
    ];
}
