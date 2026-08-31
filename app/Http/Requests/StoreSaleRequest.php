<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'exists:clients,id'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', Rule::in(['service', 'product', 'package'])],
            'items.*.id' => ['required', 'integer'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.employee_id' => ['nullable', 'exists:employees,id'],

            // Pagos opcionales: una venta puede quedar con saldo pendiente.
            'payments' => ['nullable', 'array'],
            'payments.*.payment_method_id' => ['required', 'exists:payment_methods,id'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Vender un paquete crea el saldo de sesiones de un cliente concreto, así
     * que no tiene sentido sin cliente asignado.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $hasPackage = collect($this->input('items', []))
                ->contains(fn ($item) => ($item['type'] ?? null) === 'package');

            if ($hasPackage && ! $this->filled('client_id')) {
                $validator->errors()->add(
                    'client_id',
                    'Para vender un paquete debe indicar el cliente al que pertenece.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.required' => 'La venta debe tener al menos un concepto.',
            'items.*.type.in' => 'El tipo de ítem debe ser servicio, producto o paquete.',
        ];
    }
}
