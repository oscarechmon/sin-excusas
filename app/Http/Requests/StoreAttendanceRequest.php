<?php

namespace App\Http\Requests;

use App\Models\ClientPackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'service_id' => ['required', 'exists:services,id'],
            'employee_id' => ['required', 'exists:employees,id'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'client_package_id' => ['nullable', 'exists:client_packages,id'],
            'attended_at' => ['required', 'date'],
            'observations' => ['nullable', 'string'],
            'measurements' => ['nullable', 'string'],
            'supplies' => ['nullable', 'array'],
            'supplies.*.inventory_item_id' => ['required', 'integer', 'exists:inventory_items,id'],
            'supplies.*.quantity' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * El paquete debe pertenecer al cliente de la atención. Es una regla que
     * necesita dos campos a la vez, por eso va aquí y no en `rules()`.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $packageId = $this->input('client_package_id');

            if ($packageId === null) {
                return;
            }

            $belongsToClient = ClientPackage::whereKey($packageId)
                ->where('client_id', $this->input('client_id'))
                ->exists();

            if (! $belongsToClient) {
                $validator->errors()->add(
                    'client_package_id',
                    'El paquete seleccionado no pertenece a este cliente.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'Debe seleccionar un cliente.',
            'service_id.required' => 'Debe seleccionar un servicio.',
            'employee_id.required' => 'Debe seleccionar un especialista.',
            'attended_at.required' => 'La fecha de atención es requerida.',
        ];
    }
}
