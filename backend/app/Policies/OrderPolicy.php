<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    public function __construct(
        protected PermissionService $permissionService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Order $order): bool
    {
        return $this->isVisibleToUser($user, $order);
    }

    public function create(User $user): bool
    {
        return $this->permissionService->forUser($user)->canCreateOrder();
    }

    public function update(User $user, Order $order): bool
    {
        if ($user->isPlatform()) {
            return true;
        }

        return $this->isOwner($user, $order) && !$order->status === OrderStatus::COMPLETED->value;
    }

    public function delete(User $user, Order $order): bool
    {
        if ($user->isPlatform()) {
            return true;
        }

        return $this->isOwner($user, $order) && $order->isPending();
    }

    public function updateStatus(User $user, Order $order, string $status): Response
    {
        $targetStatus = OrderStatus::tryFrom($status);

        if (!$targetStatus) {
            return Response::deny('无效的订单状态');
        }

        if (!$this->permissionService->forUser($user)->canUpdateOrderStatus($order, $targetStatus)) {
            return Response::deny('您无权执行该订单状态变更操作');
        }

        return Response::allow();
    }

    public function approve(User $user, Order $order): bool
    {
        return $this->permissionService->forUser($user)->canApproveOrder($order);
    }

    protected function isOwner(User $user, Order $order): bool
    {
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

    protected function isVisibleToUser(User $user, Order $order): bool
    {
        if ($user->isPlatform()) {
            return true;
        }

        return $this->isOwner($user, $order);
    }
}
