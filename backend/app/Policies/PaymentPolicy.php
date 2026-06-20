<?php

namespace App\Policies;

use App\Enums\PaymentType;
use App\Models\Payment;
use App\Models\User;
use App\Services\PermissionService;

class PaymentPolicy
{
    public function __construct(
        protected PermissionService $permissionService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Payment $payment): bool
    {
        return $this->isVisibleToUser($user, $payment);
    }

    public function create(User $user, string $type): bool
    {
        $paymentType = PaymentType::tryFrom($type);

        if (!$paymentType) {
            return false;
        }

        return $this->permissionService->forUser($user)->canCreatePayment($paymentType);
    }

    public function delete(User $user, Payment $payment): bool
    {
        if ($user->isPlatform()) {
            return true;
        }

        return $this->isVisibleToUser($user, $payment) && $payment->isPending();
    }

    public function settle(User $user, Payment $payment): bool
    {
        return $this->permissionService->forUser($user)->canSettlePayment($payment);
    }

    public function refund(User $user, Payment $payment): bool
    {
        return $this->permissionService->forUser($user)->canRefundPayment($payment);
    }

    protected function isVisibleToUser(User $user, Payment $payment): bool
    {
        if ($user->isPlatform()) {
            return true;
        }

        $order = $payment->order;

        if (!$order) {
            return false;
        }

        if ($user->isSupplier()) {
            return $order->supplier_id === $user->supplier_id;
        }

        if ($user->isDistributor() && $user->distributor_id) {
            $allowedIds = [$user->distributor_id];

            if ($user->isRegionalAgent() && $user->distributor) {
                $allowedIds = array_merge($allowedIds, $user->distributor->descendantIds());
            }

            return in_array($order->distributor_id, $allowedIds, true);
        }

        return false;
    }
}
