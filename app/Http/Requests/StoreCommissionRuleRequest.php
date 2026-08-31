<?php

namespace App\Http\Requests;

use App\Enums\CommissionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCommissionRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Ambos nulos = regla general para todo el centro.
            'employee_id' => ['nullable', 'exists:employees,id'],
            'service_id' => ['nullable', 'exists:services,id'],
            'type' => ['required', Rule::enum(CommissionType::class)],
            'value' => ['required', 'numeric', 'min:0'],
            'active' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('type') !== CommissionType::PERCENTAGE->value) {
                return;
            }

            if ((float) $this->input('value') > 100) {
                $validator->errors()->add('value', 'Un porcentaje no puede superar 100.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Indique si la comisión es por porcentaje o monto fijo.',
        ];
    }
}
