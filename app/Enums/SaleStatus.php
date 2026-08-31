<?php

namespace App\Enums;

enum SaleStatus: string
{
    case PENDING = 'pending';
    case PARTIAL = 'partial';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::PARTIAL => 'Pago parcial',
            self::PAID => 'Pagada',
            self::CANCELLED => 'Anulada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PARTIAL => 'info',
            self::PAID => 'success',
            self::CANCELLED => 'danger',
        };
    }

    /**
     * Estado que corresponde a un total y un monto pagado. Centralizarlo evita
     * que cada punto que registra un pago recalcule la regla por su cuenta.
     */
    public static function fromAmounts(float $total, float $paid): self
    {
        if ($paid <= 0) {
            return self::PENDING;
        }

        return $paid >= $total ? self::PAID : self::PARTIAL;
    }
}
