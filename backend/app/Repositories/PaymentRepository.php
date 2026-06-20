<?php

namespace App\Repositories;

use App\Models\Payment;

class PaymentRepository extends BaseRepository
{
    protected string $modelClass = Payment::class;

    protected array $searchColumns = ['payment_no', 'transaction_no'];

    protected array $filterColumns = [
        'order_id' => ['column' => 'order_id', 'operator' => '=', 'type' => 'int'],
        'type' => ['column' => 'type', 'operator' => '='],
        'method' => ['column' => 'method', 'operator' => '='],
        'status' => ['column' => 'status', 'operator' => '='],
        'currency' => ['column' => 'currency', 'operator' => '='],
        'payment_from' => ['column' => 'payment_date', 'type' => 'date_from'],
        'payment_to' => ['column' => 'payment_date', 'type' => 'date_to'],
    ];
}
