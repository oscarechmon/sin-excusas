<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Venta directa de un paquete a un cliente, sin pasar por el módulo de ventas.
 * Útil para cargar paquetes ya vendidos al poner el sistema en marcha.
 */
class SellPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'package_id' => ['required', 'exists:packages,id'],
            // Permite un precio pactado distinto al de lista.
            'price' => ['nullable', 'numeric', 'min:0'],
            'purchased_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'Debe seleccionar un cliente.',
            'package_id.required' => 'Debe seleccionar un paquete.',
        ];
    }
}
