<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case PURCHASE = 'purchase';
    case SERVICE_USAGE = 'service_usage';
    case SALE = 'sale';
    case MANUAL_IN = 'manual_in';
    case MANUAL_OUT = 'manual_out';
    case ADJUSTMENT = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::PURCHASE => 'Compra',
            self::SERVICE_USAGE => 'Consumo por atención',
            self::SALE => 'Venta',
            self::MANUAL_IN => 'Entrada manual',
            self::MANUAL_OUT => 'Salida manual',
            self::ADJUSTMENT => 'Ajuste',
        };
    }

    /**
     * Indica si el tipo suma stock. El ajuste queda fuera a propósito: su
     * signo lo define la cantidad enviada, no el tipo.
     */
    public function isIncoming(): bool
    {
        return in_array($this, [self::PURCHASE, self::MANUAL_IN], true);
    }

    public function isOutgoing(): bool
    {
        return in_array($this, [self::SERVICE_USAGE, self::SALE, self::MANUAL_OUT], true);
    }

    public function color(): string
    {
        return match ($this) {
            self::PURCHASE, self::MANUAL_IN => 'success',
            self::SERVICE_USAGE, self::SALE, self::MANUAL_OUT => 'danger',
            self::ADJUSTMENT => 'warning',
        };
    }
}
