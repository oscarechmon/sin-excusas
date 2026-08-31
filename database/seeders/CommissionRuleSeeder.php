<?php

namespace Database\Seeders;

use App\Enums\CommissionType;
use App\Models\CommissionRule;
use Illuminate\Database\Seeder;

/**
 * Regla de comisión por defecto del centro (§26).
 *
 * Se crea una sola regla general; el administrador puede añadir reglas más
 * específicas por especialista o servicio desde la interfaz, y esas tendrán
 * prioridad sobre esta.
 */
class CommissionRuleSeeder extends Seeder
{
    public function run(): void
    {
        CommissionRule::firstOrCreate(
            ['employee_id' => null, 'service_id' => null],
            [
                'type' => CommissionType::PERCENTAGE,
                'value' => 10,
                'active' => true,
            ]
        );
    }
}
