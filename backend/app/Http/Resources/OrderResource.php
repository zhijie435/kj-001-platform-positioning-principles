<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
            'type' => $this->type,
            'supplier_id' => $this->supplier_id,
            'distributor_id' => $this->distributor_id,
            'created_by' => $this->created_by,
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'discount' => $this->discount,
            'shipping' => $this->shipping,
            'total' => $this->total,
            'paid_amount' => $this->paid_amount,
            'payment_status' => $this->payment_status,
            'status' => $this->status,
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'tracking_no' => $this->tracking_no,
            'confirmed_at' => $this->confirmed_at,
            'shipped_at' => $this->shipped_at,
            'delivered_at' => $this->delivered_at,
            'completed_at' => $this->completed_at,
            'remark' => $this->remark,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'distributor' => new DistributorResource($this->whenLoaded('distributor')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
