<?php

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionName::values() as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }

        foreach (RoleName::cases() as $roleName) {
            $role = Role::firstOrCreate(
                ['name' => $roleName->value],
                ['guard_name' => 'web']
            );

            $this->applyPermissions($role, $roleName);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function applyPermissions(Role $role, RoleName $roleName): void
    {
        // El Administrador siempre recibe todos los permisos, incluidos los que
        // se agreguen al enum más adelante: su definición es "acceso general".
        if ($roleName === RoleName::ADMINISTRADOR) {
            $role->syncPermissions(PermissionName::values());

            return;
        }

        // Los demás roles solo se siembran al crearse. Después son
        // configurables desde la pantalla de Usuarios y roles, y volver a
        // ejecutar el seeder no debe descartar esa configuración (§45).
        if ($role->wasRecentlyCreated) {
            $role->syncPermissions(PermissionName::forRole($roleName));
        }
    }
}
