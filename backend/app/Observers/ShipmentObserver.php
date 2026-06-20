<?php

namespace App\Observers;

use App\Models\Shipment;
use App\Services\CrossBorderStatusService;

class ShipmentObserver
{
    public function __construct(
        private CrossBorderStatusService $statusService,
    ) {}

    public function updated(Shipment $shipment): void
    {
        if (!$shipment->wasChanged('status')) {
            return;
        }

        $oldStatus = $shipment->getOriginal('status');

        $this->statusService->syncShipmentToOrder($shipment, $oldStatus);
    }
}
