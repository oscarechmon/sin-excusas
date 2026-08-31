<?php

namespace App\Actions\Users;

use App\Models\Employee;
use App\Models\User;
use App\Services\UserAccountService;
use Illuminate\Support\Facades\DB;

/**
 * Edición de un usuario.
 *
 * Antes de escribir nada comprueba las invariantes de cuenta: no dejar el
 * sistema sin administradores ni permitir que alguien se bloquee a sí mismo.
 */
class UpdateUserAction
{
    public function __construct(private readonly UserAccountService $accounts) {}

    /** @param array<string,mixed> $data Payload validado por UpdateUserRequest. */
    public function execute(User $user, array $data, User $actor): User
    {
        $roles = $data['roles'];
        $willBeActive = $data['active'] ?? $user->active;

        $this->accounts->assertCanChangeRoles($user, $actor, $roles);

        if ($user->active && ! $willBeActive) {
            $this->accounts->assertCanDeactivate($user, $actor);
        }

        return DB::transaction(function () use ($user, $data, $roles, $willBeActive) {
            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'active' => $willBeActive,
            ]);

            // Solo se cambia la contraseña si se envió una nueva; dejar el
            // campo vacío al editar no debe borrar la existente.
            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }

            $user->save();
            $user->syncRoles($roles);

            // Desactivar la cuenta no invalida por sí solo los tokens de
            // Sanctum ya emitidos: hay que revocarlos explícitamente, o el
            // usuario seguiría entrando con su sesión abierta (§30).
            if (! $willBeActive) {
                $user->tokens()->delete();
            }

            $this->syncEmployeeLink($user, $data['employee_id'] ?? null);

            return $user->load('roles', 'employee');
        });
    }

    private function syncEmployeeLink(User $user, ?int $employeeId): void
    {
        $current = Employee::where('user_id', $user->id)->first();

        if ($current?->id === $employeeId) {
            return;
        }

        // Se libera el vínculo anterior antes de crear el nuevo: un usuario
        // solo puede estar asociado a una ficha de personal.
        $current?->update(['user_id' => null]);

        if ($employeeId !== null) {
            Employee::whereKey($employeeId)->update(['user_id' => $user->id]);
        }
    }
}
