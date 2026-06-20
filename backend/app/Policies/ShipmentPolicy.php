<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;
use App\Services\PermissionService;

class ShipmentPolicy
{
    public function __construct(
        protected PermissionService $permissionService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Shipment $shipment): bool
    {
        return $this->isVisibleToUser($user, $shipment);
    }

    public function create(User $user): bool
    {
        return !$user->isPlatform() || $user->hasRole('admin');
    }

    public function update(User $user, Shipment $shipment): bool
    {
        return $this->permissionService->forUser($user)->canManageShipment($shipment);
    }

    public function delete(User $user, Shipment $shipment): bool
    {
        if ($user->isPlatform()) {
            return true;
        }

        return $this->isVisibleToUser($user, $shipment) && $shipment->isPending();
    }

    public function updateStatus(User $user, Shipment $shipment): bool
    {
        return $this->permissionService->forUser($user)->canManageShipment($shipment);
    }

    protected function isVisibleToUser(User $user, Shipment $shipment): bool
    {
        if ($user->isPlatform()) {
            return true;
        }

        $order = $shipment->order;

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
