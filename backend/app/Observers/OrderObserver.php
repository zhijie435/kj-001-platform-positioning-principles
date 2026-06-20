<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\CrossBorderStatusService;

class OrderObserver
{
    public function __construct(
        private CrossBorderStatusService $statusService,
    ) {}

    public function updated(Order $order): void
    {
        if (!$order->wasChanged('status')) {
            return;
        }

        if ($order->status === 'cancelled') {
            $this->statusService->syncOrderCancellation($order);
        }
    }
}
