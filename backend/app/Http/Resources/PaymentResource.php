<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'payment_no' => $this->payment_no,
            'order_id' => $this->order_id,
            'created_by' => $this->created_by,
            'type' => $this->type,
            'method' => $this->method,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_date' => $this->payment_date,
            'transaction_no' => $this->transaction_no,
            'remark' => $this->remark,
            'order' => new OrderResource($this->whenLoaded('order')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
