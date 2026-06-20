<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isUpdate() ? 'sometimes|required' : 'required';

        return [
            'order_id' => [$required, 'exists:orders,id'],
            'type' => [$required, 'in:income,expense'],
            'method' => [$required, 'in:cash,bank_transfer,alipay,wechat,credit,other'],
            'amount' => [$required, 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'max:10'],
            'payment_date' => [$required, 'date'],
            'transaction_no' => ['nullable', 'string', 'max:100'],
            'remark' => ['nullable', 'string'],
        ];
    }

    protected function isUpdate(): bool
    {
        return in_array($this->method(), ['PUT', 'PATCH']);
    }
}
