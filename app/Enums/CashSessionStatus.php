<?php

namespace App\Enums;

enum CashSessionStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Abierta',
            self::CLOSED => 'Cerrada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OPEN => 'success',
            self::CLOSED => 'secondary',
        };
    }
}
