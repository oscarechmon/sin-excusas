<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * La autorización vive en el middleware `permission:` de las rutas y en las
     * Policies; este Request solo valida (§36: responsabilidad única).
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'document_number' => ['nullable', 'string', 'max:20'],
            'user_id' => [
                'nullable',
                'exists:users,id',
                // Un usuario no puede estar vinculado a dos fichas de personal.
                Rule::unique('employees', 'user_id')->ignore($employeeId),
            ],
            'active' => ['boolean'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer', 'exists:services,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es requerido.',
            'user_id.unique' => 'Ese usuario ya está vinculado a otro trabajador.',
        ];
    }
}
