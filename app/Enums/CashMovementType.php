<?php

namespace App\Enums;

enum CashMovementType: string
{
    case OPENING = 'opening';
    case SALE = 'sale';
    case EXPENSE = 'expense';
    case ADJUSTMENT = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::OPENING => 'Apertura',
            self::SALE => 'Venta',
            self::EXPENSE => 'Egreso',
            self::ADJUSTMENT => 'Ajuste',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OPENING => 'info',
            self::SALE => 'success',
            self::EXPENSE => 'danger',
            self::ADJUSTMENT => 'warning',
        };
    }
}
