<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isUpdate() ? 'sometimes|required' : 'required';

        return [
            'type' => [$required, 'in:distributor_order,agent_order'],
            'supplier_id' => [$required, 'exists:suppliers,id'],
            'distributor_id' => [$required, 'exists:distributors,id'],
            'items' => [$required, 'array', 'min:1'],
            'items.*.product_id' => [$required, 'exists:products,id'],
            'items.*.quantity' => [$required, 'integer', 'min:1'],
            'items.*.unit_price' => [$required, 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'shipping' => ['nullable', 'numeric', 'min:0'],
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'billing_address' => ['nullable', 'string', 'max:500'],
            'remark' => ['nullable', 'string'],
        ];
    }

    protected function isUpdate(): bool
    {
        return in_array($this->method(), ['PUT', 'PATCH']);
    }
}
