<?php

namespace App\Enums;

enum CommissionType: string
{
    case PERCENTAGE = 'percentage';
    case FIXED = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::PERCENTAGE => 'Porcentaje',
            self::FIXED => 'Monto fijo',
        };
    }

    /**
     * Calcula la comisión sobre un monto base. Mantener el cálculo junto al
     * tipo evita que cada consumidor reimplemente la fórmula.
     */
    public function calculate(float $baseAmount, float $value): float
    {
        $amount = match ($this) {
            self::PERCENTAGE => $baseAmount * ($value / 100),
            self::FIXED => $value,
        };

        return round(max(0, $amount), 2);
    }
}
