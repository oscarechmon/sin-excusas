<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // El orden importa: los permisos deben existir antes de asignarlos a
        // usuarios, y los servicios antes de vincularlos a personal y paquetes.
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            PaymentMethodSeeder::class,
            WebCatalogSeeder::class,
            EmployeeSeeder::class,
            InventorySeeder::class,
            CommissionRuleSeeder::class,
        ]);
    }
}
