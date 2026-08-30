<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RoleName::cases() as $role) {
            Role::firstOrCreate(
                ['name' => $role->value],
                ['guard_name' => 'web']
            );
        }
    }
}
