<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\CrossBorderStatusService;

class PaymentObserver
{
    public function __construct(
        private CrossBorderStatusService $statusService,
    ) {}

    public function created(Payment $payment): void
    {
        if ($payment->isIncome()) {
            $this->statusService->syncPaymentToOrder($payment);
        }
    }

    public function updated(Payment $payment): void
    {
        if ($payment->wasChanged(['amount', 'type', 'order_id'])) {
            $this->statusService->syncPaymentToOrder($payment);
        }
    }

    public function deleted(Payment $payment): void
    {
        if ($payment->isIncome()) {
            $this->statusService->syncPaymentToOrder($payment);
        }
    }
}
