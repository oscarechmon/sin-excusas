<?php

namespace App\Actions\Users;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Alta de un usuario del sistema (§29).
 *
 * Crear la cuenta, asignar roles y vincularla con una ficha de personal son
 * tres escrituras que deben ocurrir juntas: una cuenta sin roles no podría
 * entrar a ninguna pantalla.
 */
class CreateUserAction
{
    /** @param array<string,mixed> $data Payload validado por StoreUserRequest. */
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                // El cast 'hashed' del modelo se encarga del hasheo (§30).
                'email' => $data['email'],
                'password' => $data['password'],
                'active' => $data['active'] ?? true,
            ]);

            $user->syncRoles($data['roles']);

            $this->linkEmployee($user, $data['employee_id'] ?? null);

            return $user->load('roles', 'employee');
        });
    }

    /**
     * Vincula la cuenta con una ficha de personal existente. La relación vive
     * en `employees.user_id`, así que se escribe desde ese lado.
     */
    private function linkEmployee(User $user, ?int $employeeId): void
    {
        if ($employeeId === null) {
            return;
        }

        Employee::whereKey($employeeId)->update(['user_id' => $user->id]);
    }
}
