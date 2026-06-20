<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isUpdate() ? 'sometimes|required' : 'required';

        return [
            'product_id' => [$required, 'exists:products,id'],
            'supplier_id' => [$required, 'exists:suppliers,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'quantity' => [$required, 'integer', 'min:0'],
            'available_quantity' => ['nullable', 'integer', 'min:0'],
            'reserved_quantity' => ['nullable', 'integer', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'batch_no' => ['nullable', 'string', 'max:100'],
            'expiry_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:100'],
            'remark' => ['nullable', 'string'],
        ];
    }

    protected function isUpdate(): bool
    {
        return in_array($this->method(), ['PUT', 'PATCH']);
    }
}
