<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Exceptions\CannotModifyOwnAccountException;
use App\Exceptions\LastAdministratorException;
use App\Models\User;

/**
 * Invariantes de las cuentas de usuario (§30).
 *
 * Viven en un servicio y no repartidas por el controlador porque las mismas
 * reglas aplican al desactivar, al cambiar roles y al eliminar: tenerlas en
 * un solo sitio evita que una de las tres rutas se olvide de comprobarlas.
 */
class UserAccountService
{
    /** Administradores activos, opcionalmente excluyendo a uno. */
    public function activeAdministratorCount(?int $exceptUserId = null): int
    {
        return User::query()
            ->where('active', true)
            ->when($exceptUserId !== null, fn ($q) => $q->whereKeyNot($exceptUserId))
            ->whereHas('roles', fn ($q) => $q->where('name', RoleName::ADMINISTRADOR->value))
            ->count();
    }

    public function isAdministrator(User $user): bool
    {
        return $user->hasRole(RoleName::ADMINISTRADOR->value);
    }

    /** True si quitar a este usuario dejaría el sistema sin administradores. */
    public function isLastActiveAdministrator(User $user): bool
    {
        return $this->isAdministrator($user)
            && $user->active
            && $this->activeAdministratorCount($user->id) === 0;
    }

    public function assertCanDeactivate(User $target, User $actor): void
    {
        $this->assertNotSelf($target, $actor, 'desactivar');

        if ($this->isLastActiveAdministrator($target)) {
            throw LastAdministratorException::make('desactivar este usuario');
        }
    }

    public function assertCanDelete(User $target, User $actor): void
    {
        $this->assertNotSelf($target, $actor, 'eliminar');

        if ($this->isLastActiveAdministrator($target)) {
            throw LastAdministratorException::make('eliminar este usuario');
        }
    }

    /**
     * @param  array<int,string>  $newRoles  Roles que quedarían tras el cambio.
     */
    public function assertCanChangeRoles(User $target, User $actor, array $newRoles): void
    {
        $keepsAdmin = in_array(RoleName::ADMINISTRADOR->value, $newRoles, true);

        if ($keepsAdmin) {
            return;
        }

        if ($target->is($actor)) {
            throw CannotModifyOwnAccountException::make('quitarse el rol de Administrador de');
        }

        if ($this->isLastActiveAdministrator($target)) {
            throw LastAdministratorException::make('quitarle el rol de Administrador');
        }
    }

    private function assertNotSelf(User $target, User $actor, string $action): void
    {
        if ($target->is($actor)) {
            throw CannotModifyOwnAccountException::make($action);
        }
    }
}
