<?php

namespace App\Http\Requests;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            // Opcional al editar: solo se cambia si se escribe una nueva.
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'active' => ['boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in(array_column(RoleName::cases(), 'value'))],
            'employee_id' => [
                'nullable',
                'exists:employees,id',
                Rule::unique('employees', 'user_id')->whereNotNull('user_id')->ignore($userId, 'user_id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'roles.required' => 'El usuario debe conservar al menos un rol.',
        ];
    }
}
