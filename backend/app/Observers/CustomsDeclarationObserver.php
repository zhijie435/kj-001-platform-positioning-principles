<?php

namespace App\Observers;

use App\Models\CustomsDeclaration;
use App\Services\CrossBorderStatusService;

class CustomsDeclarationObserver
{
    public function __construct(
        private CrossBorderStatusService $statusService,
    ) {}

    public function updated(CustomsDeclaration $declaration): void
    {
        if (!$declaration->wasChanged('status')) {
            return;
        }

        $this->statusService->syncCustomsToShipment($declaration);
    }
}
