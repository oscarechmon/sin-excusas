<?php

namespace App\Http\Requests;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            // `confirmed` exige password_confirmation: al crear una cuenta ajena
            // un error de tecleo dejaría al usuario sin poder entrar.
            'password' => ['required', 'confirmed', Password::min(8)],
            'active' => ['boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in(array_column(RoleName::cases(), 'value'))],
            // Vincula la cuenta con una ficha de personal ya existente.
            'employee_id' => ['nullable', 'exists:employees,id', Rule::unique('employees', 'user_id')->whereNotNull('user_id')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es requerido.',
            'email.required' => 'El correo es requerido.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'password.required' => 'La contraseña es requerida.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'roles.required' => 'Asigne al menos un rol al usuario.',
            'roles.min' => 'Asigne al menos un rol al usuario.',
        ];
    }
}
