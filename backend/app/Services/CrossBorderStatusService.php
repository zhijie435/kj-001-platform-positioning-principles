<?php

namespace App\Services;

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
        if (!$payment->order_id) {
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
            ->where('type', 'income')
            ->sum('amount');

        $order->paid_amount = $incomeAmount;
        $total = (float) $order->total;

        if ($incomeAmount >= $total && $total > 0) {
            $order->payment_status = 'paid';
        } elseif ($incomeAmount > 0) {
            $order->payment_status = 'partial';
        } else {
            $order->payment_status = 'unpaid';
        }

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
        $marketCode = $shipment->destinationMarket?->country_code;

        DB::transaction(function () use ($order, $shipment, $newStatus, $oldStatus) {
            $updates = $this->resolveOrderUpdatesFromShipment($order, $shipment, $newStatus, $oldStatus);

            if (empty($updates)) {
                return;
            }

            $order->saveQuietly();
        });
    }

    protected function resolveOrderUpdatesFromShipment(Order $order, Shipment $shipment, string $newStatus, ?string $oldStatus): array
    {
        if (!$this->shouldSyncShipmentToOrder($order, $shipment, $newStatus)) {
            return [];
        }

        return match ($newStatus) {
            'shipped' => $this->applyOrderShipped($order),
            'delivered' => $this->applyOrderDelivered($order),
            'cancelled' => $this->maybeRevertOrderFromCancelledShipment($order, $shipment),
            default => [],
        };
    }

    protected function shouldSyncShipmentToOrder(Order $order, Shipment $shipment, string $newStatus): bool
    {
        $terminalStatuses = ['completed', 'cancelled', 'refunded'];

        if (in_array($order->status, $terminalStatuses, true)) {
            return false;
        }

        if ($newStatus === 'shipped' && in_array($order->status, ['shipped', 'delivered', 'completed'], true)) {
            return false;
        }

        if ($newStatus === 'delivered' && in_array($order->status, ['delivered', 'completed'], true)) {
            return false;
        }

        return true;
    }

    protected function applyOrderShipped(Order $order): array
    {
        $order->status = 'shipped';
        $order->shipped_at = $order->shipped_at ?? now();
        $order->tracking_no = $order->tracking_no ?: null;

        return ['status', 'shipped_at'];
    }

    protected function applyOrderDelivered(Order $order): array
    {
        $order->status = 'delivered';
        $order->delivered_at = $order->delivered_at ?? now();

        return ['status', 'delivered_at'];
    }

    protected function maybeRevertOrderFromCancelledShipment(Order $order, Shipment $shipment): array
    {
        if (!in_array($order->status, ['shipped', 'processing'], true)) {
            return [];
        }

        $order->status = 'confirmed';
        $order->shipped_at = null;
        $order->tracking_no = null;

        return ['status', 'shipped_at', 'tracking_no'];
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
        $status = $declaration->status;

        if ($status === 'declared' && $shipment->status === 'in_transit') {
            $shipment->status = 'customs';
            $shipment->customs_at = $shipment->customs_at ?? now();
            $shipment->saveQuietly();

            return;
        }

        if ($status === 'released') {
            $this->handleCustomsReleased($shipment, $declaration, $rules);

            return;
        }

        if ($status === 'rejected' && $rules['auto_fail_on_rejected']) {
            $shipment->status = 'failed';
            $shipment->failed_at = now();
            $shipment->saveQuietly();

            $shipment->addTrackingEvent('failed', '', '报关被拒，物流状态自动标记为失败');
            $shipment->saveQuietly();

            return;
        }

        if ($status === 'rejected' && !$rules['auto_fail_on_rejected']) {
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

        if (in_array($shipment->status, ['customs', 'in_transit', 'shipped'], true)) {
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

        if ($targetStatus === 'in_transit' && $rules['require_release_before_transit']) {
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
        if ($order->status !== 'cancelled') {
            return;
        }

        DB::transaction(function () use ($order) {
            $shipments = $order->shipments()
                ->whereNotIn('status', ['delivered', 'cancelled', 'returned'])
                ->get();

            foreach ($shipments as $shipment) {
                $shipment->status = 'cancelled';
                $shipment->saveQuietly();

                $shipment->addTrackingEvent('cancelled', '', '订单已取消，物流自动取消');
                $shipment->saveQuietly();
            }
        });
    }
}
