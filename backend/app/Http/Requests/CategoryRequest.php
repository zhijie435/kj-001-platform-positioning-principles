<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
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
            'code' => [
                $this->isUpdate() ? 'sometimes' : 'required',
                'string',
                'max:100',
                Rule::unique('categories', 'code')->ignore($this->route('category')),
            ],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function isUpdate(): bool
    {
        return in_array($this->method(), ['PUT', 'PATCH']);
    }
}
