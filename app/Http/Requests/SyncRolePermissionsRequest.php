<?php

namespace App\Http\Requests;

use App\Enums\PermissionName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['present', 'array'],
            // Solo se aceptan permisos declarados en el enum: uno inventado
            // quedaría en base de datos sin que ninguna ruta lo comprobara.
            'permissions.*' => [Rule::in(PermissionName::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'permissions.present' => 'Debe enviar la lista de permisos, aunque esté vacía.',
            'permissions.*.in' => 'Uno de los permisos enviados no existe en el sistema.',
        ];
    }
}
