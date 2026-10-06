<?php

namespace App\Http\Requests;

use App\Support\Peru;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PlaceOnlineOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Espacios de más fuera y el documento sin espacios ni guiones. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'recipient_name' => preg_replace('/\s+/u', ' ', trim((string) $this->input('recipient_name'))),
            'document_number' => strtoupper(preg_replace('/[\s.-]+/', '', (string) $this->input('document_number'))) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'fulfillment' => ['required', 'in:delivery,province,pickup'],
            'recipient_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required_if:fulfillment,delivery', 'nullable', 'string', 'max:255'],
            'district' => ['required_if:fulfillment,delivery', 'nullable', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:255'],
            // Envío a provincia: la agencia entrega con el documento de quien recoge.
            'document_type' => ['required_if:fulfillment,province', 'nullable', 'in:dni,ce'],
            'document_number' => ['required_if:fulfillment,province', 'nullable', 'string', 'max:20'],
            'department' => ['required_if:fulfillment,province', 'nullable', Rule::in(Peru::DEPARTMENTS)],
            'province' => ['required_if:fulfillment,province', 'nullable', 'string', 'max:100'],
            'agency' => ['required_if:fulfillment,province', 'nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** El DNI tiene 8 dígitos; el carné de extranjería, de 9 a 12 caracteres. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->input('fulfillment') !== 'province' || $validator->errors()->hasAny(['document_type', 'document_number', 'recipient_name'])) {
                    return;
                }

                $number = (string) $this->input('document_number');
                $valid = $this->input('document_type') === 'dni'
                    ? preg_match('/^\d{8}$/', $number)
                    : preg_match('/^[A-Z0-9]{9,12}$/', $number);

                if (! $valid) {
                    $validator->errors()->add('document_number', $this->input('document_type') === 'dni'
                        ? 'El DNI debe tener 8 dígitos.'
                        : 'El carné de extranjería debe tener entre 9 y 12 caracteres.');
                }

                // La agencia pide el nombre completo, como figura en el documento.
                if (count(explode(' ', (string) $this->input('recipient_name'))) < 2) {
                    $validator->errors()->add('recipient_name', 'Escribe nombres y apellidos completos, como figuran en el documento.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'fulfillment.required' => 'Elige cómo quieres recibir tu pedido.',
            'recipient_name.required' => 'Indica el nombre de quien recibe.',
            'phone.required' => 'Indica un teléfono de contacto.',
            'address.required_if' => 'La dirección es necesaria para el delivery.',
            'district.required_if' => 'El distrito es necesario para el delivery.',
            'document_type.required_if' => 'Elige el tipo de documento.',
            'document_number.required_if' => 'Indica el número de documento de quien recoge en la agencia.',
            'department.required_if' => 'Indica el departamento de destino.',
            'department.in' => 'Elige un departamento de la lista.',
            'province.required_if' => 'Indica la provincia o ciudad de destino.',
            'agency.required_if' => 'Indica la agencia Shalom donde recogerás tu pedido.',
        ];
    }
}
