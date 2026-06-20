<?php

namespace App\Services;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\ShipmentStatus;
use App\Models\CustomsDeclaration;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CrossBorderStatusService
{
    public function syncPaymentToOrder(Payment $payment): void
    {
        $typeEnum = $payment->getTypeEnum();

        if (!$payment->order_id || !$typeEnum?->affectsOrderPaymentStatus()) {
            return;
        }

        $order = Order::find($payment->order_id);

        if (!$order) {
            return;
        }

        DB::transaction(function () use ($order) {
            $this->recalculateOrderPaymentStatus($order);
        });
    }

    public function recalculateOrderPaymentStatus(Order $order): void
    {
        $incomeAmount = $order->payments()
            ->whereIn('type', [PaymentType::ESCROW_DEPOSIT->value, PaymentType::PLATFORM_FEE->value])
            ->where('status', '!=', PaymentStatus::FAILED->value)
            ->sum('amount');

        $refundAmount = $order->payments()
            ->where('type', PaymentType::REFUND->value)
            ->where('status', '!=', PaymentStatus::FAILED->value)
            ->sum('amount');

        $netPaid = $incomeAmount - $refundAmount;

        $order->paid_amount = max(0, $netPaid);
        $total = (float) $order->total;

        $paymentStatus = OrderPaymentStatus::fromAmount($netPaid, $total);
        $order->payment_status = $paymentStatus->value;

        $order->saveQuietly();
    }

    public function syncShipmentToOrder(Shipment $shipment, ?string $oldStatus = null): void
    {
        if (!$shipment->order_id) {
            return;
        }

        $order = Order::find($shipment->order_id);

        if (!$order) {
            return;
        }

        $newStatus = $shipment->status;

        DB::transaction(function () use ($order, $shipment, $newStatus, $oldStatus) {
            $this->resolveOrderUpdatesFromShipment($order, $shipment, $newStatus, $oldStatus);
        });
    }

    protected function resolveOrderUpdatesFromShipment(Order $order, Shipment $shipment, string $newStatus, ?string $oldStatus): void
    {
        if (!$this->shouldSyncShipmentToOrder($order, $shipment, $newStatus)) {
            return;
        }

        $targetShipmentStatus = ShipmentStatus::tryFrom($newStatus);

        if (!$targetShipmentStatus) {
            return;
        }

        $updated = match ($targetShipmentStatus) {
            ShipmentStatus::SHIPPED => $this->applyOrderShipped($order),
            ShipmentStatus::DELIVERED => $this->applyOrderDelivered($order),
            ShipmentStatus::CANCELLED => $this->maybeRevertOrderFromCancelledShipment($order, $shipment),
            default => false,
        };

        if ($updated) {
            $order->saveQuietly();
        }
    }

    protected function shouldSyncShipmentToOrder(Order $order, Shipment $shipment, string $newStatus): bool
    {
        $orderStatusEnum = $order->getStatusEnum();

        if ($orderStatusEnum->isTerminal()) {
            return false;
        }

        $shipmentStatusEnum = ShipmentStatus::tryFrom($newStatus);
        if (!$shipmentStatusEnum) {
            return false;
        }

        if ($shipmentStatusEnum === ShipmentStatus::SHIPPED
            && in_array($orderStatusEnum, [ShipmentStatus::SHIPPED, OrderStatus::DELIVERED, OrderStatus::COMPLETED], true)) {
            return false;
        }

        if ($shipmentStatusEnum === ShipmentStatus::DELIVERED
            && in_array($orderStatusEnum, [OrderStatus::DELIVERED, OrderStatus::COMPLETED], true)) {
            return false;
        }

        return true;
    }

    protected function applyOrderShipped(Order $order): bool
    {
        if ($order->status === OrderStatus::SHIPPED->value) {
            return false;
        }

        $order->status = OrderStatus::SHIPPED->value;
        $order->shipped_at = $order->shipped_at ?? now();
        $order->tracking_no = $order->tracking_no ?: null;

        return true;
    }

    protected function applyOrderDelivered(Order $order): bool
    {
        if ($order->status === OrderStatus::DELIVERED->value) {
            return false;
        }

        $order->status = OrderStatus::DELIVERED->value;
        $order->delivered_at = $order->delivered_at ?? now();

        return true;
    }

    protected function maybeRevertOrderFromCancelledShipment(Order $order, Shipment $shipment): bool
    {
        $orderStatus = $order->getStatusEnum();

        if (!in_array($orderStatus, [OrderStatus::SHIPPED, OrderStatus::PROCESSING], true)) {
            return false;
        }

        $activeShipmentCount = $order->shipments()
            ->whereNotIn('status', [
                ShipmentStatus::DELIVERED->value,
                ShipmentStatus::CANCELLED->value,
                ShipmentStatus::RETURNED->value,
                ShipmentStatus::FAILED->value,
            ])
            ->count();

        if ($activeShipmentCount > 0) {
            return false;
        }

        $order->status = OrderStatus::CONFIRMED->value;
        $order->shipped_at = null;
        $order->tracking_no = null;

        return true;
    }

    public function syncCustomsToShipment(CustomsDeclaration $declaration): void
    {
        if (!$declaration->shipment_id) {
            return;
        }

        $shipment = Shipment::find($declaration->shipment_id);

        if (!$shipment) {
            return;
        }

        $marketCode = $shipment->destinationMarket?->country_code;
        $rules = $this->resolveMarketCustomsRules($marketCode);

        DB::transaction(function () use ($shipment, $declaration, $rules) {
            $this->applyCustomsRulesToShipment($shipment, $declaration, $rules);
        });
    }

    protected function resolveMarketCustomsRules(?string $marketCode): array
    {
        $common = [
            'require_release_before_transit' => false,
            'auto_fail_on_rejected' => false,
            'advance_to_out_for_delivery_on_release' => false,
        ];

        return match ($marketCode) {
            'BR' => array_merge($common, [
                'require_release_before_transit' => true,
                'auto_fail_on_rejected' => true,
                'advance_to_out_for_delivery_on_release' => true,
            ]),
            'US' => array_merge($common, [
                'require_release_before_transit' => false,
                'auto_fail_on_rejected' => false,
                'advance_to_out_for_delivery_on_release' => true,
            ]),
            default => $common,
        };
    }

    protected function applyCustomsRulesToShipment(Shipment $shipment, CustomsDeclaration $declaration, array $rules): void
    {
        $statusEnum = $declaration->getStatusEnum();
        $shipmentStatusEnum = $shipment->getStatusEnum();

        if ($statusEnum->value === 'declared' && $shipmentStatusEnum === ShipmentStatus::IN_TRANSIT) {
            $shipment->status = ShipmentStatus::CUSTOMS->value;
            $shipment->customs_at = $shipment->customs_at ?? now();
            $shipment->saveQuietly();

            return;
        }

        if ($statusEnum->value === 'released') {
            $this->handleCustomsReleased($shipment, $declaration, $rules);

            return;
        }

        if ($statusEnum->value === 'rejected' && $rules['auto_fail_on_rejected']) {
            $shipment->status = ShipmentStatus::FAILED->value;
            $shipment->failed_at = now();
            $shipment->saveQuietly();

            $shipment->addTrackingEvent('failed', '', '报关被拒，物流状态自动标记为失败');
            $shipment->saveQuietly();

            return;
        }

        if ($statusEnum->value === 'rejected' && !$rules['auto_fail_on_rejected']) {
            $shipment->addTrackingEvent(
                'customs',
                '',
                '报关被拒，等待人工处理（市场规则：不自动失败）'
            );
            $shipment->saveQuietly();
        }
    }

    protected function handleCustomsReleased(Shipment $shipment, CustomsDeclaration $declaration, array $rules): void
    {
        $declaration->release_date = $declaration->release_date ?? now()->toDateString();
        $declaration->saveQuietly();

        if (!$rules['advance_to_out_for_delivery_on_release']) {
            $shipment->addTrackingEvent('customs', '', '报关已放行，等待手动继续');
            $shipment->saveQuietly();

            return;
        }

        $shipmentStatusEnum = $shipment->getStatusEnum();

        if (in_array($shipmentStatusEnum, [
            ShipmentStatus::CUSTOMS,
            ShipmentStatus::IN_TRANSIT,
            ShipmentStatus::SHIPPED,
        ], true)) {
            $shipment->status = 'out_for_delivery';
            $shipment->saveQuietly();

            $shipment->addTrackingEvent('out_for_delivery', '', '报关放行，进入派送环节');
            $shipment->saveQuietly();
        }
    }

    public function validateShipmentTransition(Shipment $shipment, string $targetStatus): array
    {
        $marketCode = $shipment->destinationMarket?->country_code;
        $rules = $this->resolveMarketCustomsRules($marketCode);
        $targetStatusEnum = ShipmentStatus::tryFrom($targetStatus);

        if ($targetStatusEnum === ShipmentStatus::IN_TRANSIT && $rules['require_release_before_transit']) {
            $hasReleasedDeclaration = $shipment->declarations()
                ->where('status', 'released')
                ->exists();

            if (!$hasReleasedDeclaration) {
                return [
                    'valid' => false,
                    'message' => '当前市场（巴西）要求报关单放行后物流才能继续运输',
                ];
            }
        }

        return ['valid' => true];
    }

    public function syncOrderCancellation(Order $order): void
    {
        if ($order->status !== OrderStatus::CANCELLED->value) {
            return;
        }

        DB::transaction(function () use ($order) {
            $shipments = $order->shipments()
                ->whereNotIn('status', [
                    ShipmentStatus::DELIVERED->value,
                    ShipmentStatus::CANCELLED->value,
                    ShipmentStatus::RETURNED->value,
                ])
                ->get();

            foreach ($shipments as $shipment) {
                $shipment->status = ShipmentStatus::CANCELLED->value;
                $shipment->saveQuietly();

                $shipment->addTrackingEvent('cancelled', '', '订单已取消，物流自动取消');
                $shipment->saveQuietly();
            }
        });
    }
}
