<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'document_number' => 'nullable|string|unique:clients|max:50',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:M,F,O',
            'phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'district' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'how_knew' => 'nullable|string|max:100',
            'observations' => 'nullable|string',
            'allergies' => 'nullable|string',
            'restrictions' => 'nullable|string',
            'contraindications' => 'nullable|string',
            'medications' => 'nullable|string',
            'relevant_info' => 'nullable|string',
            'active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'El nombre es obligatorio.',
            'full_name.max' => 'El nombre no debe exceder 255 caracteres.',
            'document_number.unique' => 'Este documento ya está registrado.',
            'email.email' => 'El email debe ser válido.',
        ];
    }
}
