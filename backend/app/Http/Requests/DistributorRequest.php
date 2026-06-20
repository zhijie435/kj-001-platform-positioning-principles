<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DistributorRequest extends FormRequest
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
            'company_name' => ['nullable', 'string', 'max:255'],
            'business_license' => ['nullable', 'string', 'max:255'],
            'type' => [$required, 'in:regional_agent,wholesaler'],
            'region' => ['nullable', 'string', 'max:100'],
            'contact_person' => [$required, 'string', 'max:100'],
            'phone' => [$required, 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account' => ['nullable', 'string', 'max:100'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'balance' => ['nullable', 'numeric'],
            'discount_rate' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['nullable', 'in:pending,active,suspended,rejected'],
            'parent_id' => ['nullable', 'exists:distributors,id'],
            'remark' => ['nullable', 'string'],
        ];
    }

    protected function isUpdate(): bool
    {
        return in_array($this->method(), ['PUT', 'PATCH']);
    }
}
