<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'oechegaray@360creative.pe'],
            [
                'name' => 'Oscar Echegaray',
                'password' => bcrypt('password'),
                'active' => true,
            ]
        );

        $admin->assignRole(RoleName::ADMINISTRADOR->value);
    }
}
