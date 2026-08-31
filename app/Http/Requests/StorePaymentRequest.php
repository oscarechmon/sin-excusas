<?php

namespace App\Http\Requests;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Yape, Plin o transferencia exigen número de operación. Qué método lo
     * requiere es configurable en base de datos, no está quemado aquí (§22).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $method = PaymentMethod::find($this->input('payment_method_id'));

            if ($method?->requires_reference && ! $this->filled('reference')) {
                $validator->errors()->add(
                    'reference',
                    sprintf('El método "%s" requiere número de operación.', $method->name)
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'El monto debe ser mayor que cero.',
            'payment_method_id.required' => 'Debe seleccionar un método de pago.',
        ];
    }
}
