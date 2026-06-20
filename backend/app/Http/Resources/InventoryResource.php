<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'supplier_id' => $this->supplier_id,
            'quantity' => $this->quantity,
            'available_quantity' => $this->available_quantity,
            'reserved_quantity' => $this->reserved_quantity,
            'unit_cost' => $this->unit_cost,
            'batch_no' => $this->batch_no,
            'expiry_date' => $this->expiry_date,
            'location' => $this->location,
            'remark' => $this->remark,
            'product' => new ProductResource($this->whenLoaded('product')),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
