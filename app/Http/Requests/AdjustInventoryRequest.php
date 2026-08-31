<?php

namespace App\Http\Requests;

use App\Enums\InventoryMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                Rule::in([
                    InventoryMovementType::PURCHASE->value,
                    InventoryMovementType::MANUAL_IN->value,
                    InventoryMovementType::MANUAL_OUT->value,
                    InventoryMovementType::ADJUSTMENT->value,
                ]),
            ],
            // En ADJUSTMENT es el saldo real contado, por eso admite 0.
            'quantity' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'El consumo por atención y la venta se generan automáticamente, no se registran a mano.',
            'quantity.min' => 'La cantidad no puede ser negativa.',
        ];
    }
}
