<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('payment_method')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('payment_methods', 'name')->ignore($id)],
            'code' => ['required', 'string', 'max:30', Rule::unique('payment_methods', 'code')->ignore($id)],
            'requires_reference' => ['boolean'],
            'is_cash' => ['boolean'],
            'active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Ya existe un método de pago con ese nombre.',
            'code.unique' => 'Ya existe un método de pago con ese código.',
        ];
    }
}
