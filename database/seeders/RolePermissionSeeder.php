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

            // syncPermissions deja el rol exactamente con lo declarado en el
            // enum: si se retira un permiso del código, también se retira aquí.
            $role->syncPermissions(PermissionName::forRole($roleName));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
