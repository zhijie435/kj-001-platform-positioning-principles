<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DistributorResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'company_name' => $this->company_name,
            'business_license' => $this->business_license,
            'type' => $this->type,
            'type_label' => $this->type === 'regional_agent' ? '区域代理' : '普通批发商',
            'region' => $this->region,
            'contact_person' => $this->contact_person,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'bank_name' => $this->bank_name,
            'bank_account' => $this->bank_account,
            'credit_limit' => $this->credit_limit,
            'balance' => $this->balance,
            'discount_rate' => $this->discount_rate,
            'status' => $this->status,
            'parent_id' => $this->parent_id,
            'remark' => $this->remark,
            'parent' => new DistributorResource($this->whenLoaded('parent')),
            'children' => DistributorResource::collection($this->whenLoaded('children')),
            'users_count' => $this->whenCounted('users'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
