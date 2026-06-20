<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isUpdate() ? 'sometimes|required' : 'required';

        return [
            'name' => [$required, 'string', 'max:255'],
            'sku' => [
                $this->isUpdate() ? 'sometimes' : 'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($this->route('product')),
            ],
            'barcode' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'supplier_id' => [$required, 'exists:suppliers,id'],
            'specification' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'retail_price' => ['nullable', 'numeric', 'min:0'],
            'agent_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'safety_stock' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'status' => ['nullable', 'in:draft,on_sale,off_sale,discontinued'],
        ];
    }

    protected function isUpdate(): bool
    {
        return in_array($this->method(), ['PUT', 'PATCH']);
    }
}
