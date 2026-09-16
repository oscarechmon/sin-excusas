<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOnlineOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fulfillment' => ['required', 'in:delivery,pickup'],
            'recipient_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required_if:fulfillment,delivery', 'nullable', 'string', 'max:255'],
            'district' => ['required_if:fulfillment,delivery', 'nullable', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
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
        ];
    }
}
