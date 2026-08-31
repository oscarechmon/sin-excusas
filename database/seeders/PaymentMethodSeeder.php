<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

/**
 * Métodos de pago iniciales (§22). Quedan en base de datos, no en el código,
 * para que el administrador pueda agregar o retirar métodos sin desarrollo.
 */
class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name' => 'Efectivo', 'code' => 'cash', 'is_cash' => true, 'requires_reference' => false, 'sort_order' => 1],
            ['name' => 'Yape', 'code' => 'yape', 'is_cash' => false, 'requires_reference' => true, 'sort_order' => 2],
            ['name' => 'Plin', 'code' => 'plin', 'is_cash' => false, 'requires_reference' => true, 'sort_order' => 3],
            ['name' => 'Transferencia', 'code' => 'transfer', 'is_cash' => false, 'requires_reference' => true, 'sort_order' => 4],
            ['name' => 'POS', 'code' => 'pos', 'is_cash' => false, 'requires_reference' => false, 'sort_order' => 5],
        ];

        foreach ($methods as $method) {
            PaymentMethod::firstOrCreate(
                ['code' => $method['code']],
                $method + ['active' => true]
            );
        }
    }
}
