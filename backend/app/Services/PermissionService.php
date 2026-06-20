<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentType;
use App\Enums\UserType;
use App\Exceptions\ForbiddenException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;

class PermissionService
{
    public function __construct(
        protected ?User $user = null,
    ) {
    }

    public function forUser(?User $user): self
    {
        $instance = clone $this;
        $instance->user = $user;

        return $instance;
    }

    public function requirePlatformUser(): void
    {
        $this->ensureAuthenticated();

        if (!$this->user->isPlatform()) {
            throw new ForbiddenException('该操作仅允许平台管理员执行');
        }
    }

    public function requireSupplierUser(): void
    {
        $this->ensureAuthenticated();

        if (!$this->user->isSupplier()) {
            throw new ForbiddenException('该操作仅允许供应商执行');
        }
    }

    public function requireDistributorUser(): void
    {
        $this->ensureAuthenticated();

        if (!$this->user->isDistributor()) {
            throw new ForbiddenException('该操作仅允许分销商执行');
        }
    }

    public function requireNotPlatform(): void
    {
        $this->ensureAuthenticated();

        if ($this->user->isPlatform()) {
            throw new ForbiddenException('平台不直接参与买卖，请使用分销商账号操作');
        }
    }

    public function requireUserType(UserType ...$types): void
    {
        $this->ensureAuthenticated();
        $currentType = UserType::from($this->user->user_type);

        foreach ($types as $type) {
            if ($currentType === $type) {
                return;
            }
        }

        $labels = array_map(fn (UserType $t) => $t->label(), $types);
        throw new ForbiddenException('该操作仅允许以下用户类型执行：'.implode('、', $labels));
    }

    public function canCreateOrder(): bool
    {
        if (!$this->user) {
            return false;
        }

        return !$this->user->isPlatform();
    }

    public function ensureCanCreateOrder(): void
    {
        if (!$this->canCreateOrder()) {
            throw new ForbiddenException('平台不直接参与买卖，订单由分销商向供应商下单');
        }
    }

    public function canUpdateOrderStatus(Order $order, OrderStatus $targetStatus): bool
    {
        if (!$this->user) {
            return false;
        }

        if ($this->user->isPlatform()) {
            return true;
        }

        if ($this->user->isSupplier()) {
            return $this->isOrderOwnerSupplier($order) && $this->canSupplierTransitionOrder($targetStatus);
        }

        if ($this->user->isDistributor()) {
            return $this->isOrderOwnerDistributor($order) && $this->canDistributorTransitionOrder($targetStatus);
        }

        return false;
    }

    public function ensureCanUpdateOrderStatus(Order $order, OrderStatus $targetStatus): void
    {
        if (!$this->canUpdateOrderStatus($order, $targetStatus)) {
            throw new ForbiddenException('您无权执行该订单状态变更操作');
        }
    }

    protected function canSupplierTransitionOrder(OrderStatus $targetStatus): bool
    {
        return in_array($targetStatus, [
            OrderStatus::CONFIRMED,
            OrderStatus::PROCESSING,
            OrderStatus::SHIPPED,
            OrderStatus::REJECTED,
        ], true);
    }

    protected function canDistributorTransitionOrder(OrderStatus $targetStatus): bool
    {
        return in_array($targetStatus, [
            OrderStatus::CANCELLED,
            OrderStatus::COMPLETED,
            OrderStatus::REFUNDED,
        ], true);
    }

    public function canApproveOrder(Order $order): bool
    {
        if (!$this->user) {
            return false;
        }

        if ($this->user->isPlatform()) {
            return true;
        }

        if ($this->user->isSupplier()) {
            return $this->isOrderOwnerSupplier($order);
        }

        return false;
    }

    public function ensureCanApproveOrder(Order $order): void
    {
        if (!$this->canApproveOrder($order)) {
            throw new ForbiddenException('您无权审批该订单');
        }
    }

    public function canCreatePayment(PaymentType $type): bool
    {
        if (!$this->user) {
            return false;
        }

        return match ($type) {
            PaymentType::ESCROW_DEPOSIT => $this->user->isDistributor() || $this->user->isPlatform(),
            PaymentType::ESCROW_RELEASE, PaymentType::PLATFORM_FEE => $this->user->isPlatform(),
            PaymentType::REFUND => $this->user->isPlatform(),
            default => false,
        };
    }

    public function ensureCanCreatePayment(PaymentType $type): void
    {
        if (!$this->canCreatePayment($type)) {
            throw new ForbiddenException('您无权创建该类型的付款记录');
        }
    }

    public function canSettlePayment(Payment $payment): bool
    {
        return $this->user?->isPlatform() ?? false;
    }

    public function ensureCanSettlePayment(Payment $payment): void
    {
        if (!$this->canSettlePayment($payment)) {
            throw new ForbiddenException('结算操作仅允许平台管理员执行');
        }
    }

    public function canRefundPayment(Payment $payment): bool
    {
        return $this->user?->isPlatform() ?? false;
    }

    public function ensureCanRefundPayment(Payment $payment): void
    {
        if (!$this->canRefundPayment($payment)) {
            throw new ForbiddenException('退款操作仅允许平台管理员执行');
        }
    }

    public function canManageShipment(Shipment $shipment): bool
    {
        if (!$this->user) {
            return false;
        }

        if ($this->user->isPlatform()) {
            return true;
        }

        $order = $shipment->order;

        if ($this->user->isSupplier()) {
            return $this->isOrderOwnerSupplier($order);
        }

        if ($this->user->isDistributor()) {
            return $this->isOrderOwnerDistributor($order);
        }

        return false;
    }

    public function ensureCanManageShipment(Shipment $shipment): void
    {
        if (!$this->canManageShipment($shipment)) {
            throw new ForbiddenException('您无权管理该物流记录');
        }
    }

    public function hasMarketAccess(int $marketId): bool
    {
        return $this->user?->hasMarketAccess($marketId) ?? false;
    }

    public function ensureMarketAccess(int $marketId): void
    {
        if (!$this->hasMarketAccess($marketId)) {
            throw new ForbiddenException('您无权访问该市场的资源');
        }
    }

    protected function ensureAuthenticated(): void
    {
        if (!$this->user) {
            throw new ForbiddenException('请先登录', [], 401);
        }
    }

    protected function isOrderOwnerSupplier(Order $order): bool
    {
        return $this->user?->isSupplier()
            && $order->supplier_id === $this->user->supplier_id;
    }

    protected function isOrderOwnerDistributor(Order $order): bool
    {
        if (!$this->user?->isDistributor() || !$this->user->distributor_id) {
            return false;
        }

        $allowedIds = [$this->user->distributor_id];

        if ($this->user->isRegionalAgent() && $this->user->distributor) {
            $allowedIds = array_merge($allowedIds, $this->user->distributor->descendantIds());
        }

        return in_array($order->distributor_id, $allowedIds, true);
    }
}
