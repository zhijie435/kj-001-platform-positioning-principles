<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'category_id' => $this->category_id,
            'supplier_id' => $this->supplier_id,
            'specification' => $this->specification,
            'unit' => $this->unit,
            'cost_price' => $this->cost_price,
            'wholesale_price' => $this->wholesale_price,
            'retail_price' => $this->retail_price,
            'agent_price' => $this->agent_price,
            'stock_quantity' => $this->stock_quantity,
            'safety_stock' => $this->safety_stock,
            'description' => $this->description,
            'images' => $this->images,
            'status' => $this->status,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
