<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'total_sessions' => ['required', 'integer', 'min:1'],
            'validity_days' => ['nullable', 'integer', 'min:1'],
            'active' => ['boolean'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer', 'exists:services,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del paquete es requerido.',
            'total_sessions.min' => 'El paquete debe incluir al menos una sesión.',
            'service_ids.required' => 'Debe incluir al menos un servicio en el paquete.',
        ];
    }
}
