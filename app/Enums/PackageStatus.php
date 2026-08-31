<?php

namespace App\Enums;

enum PackageStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Activo',
            self::COMPLETED => 'Terminado',
            self::EXPIRED => 'Vencido',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::COMPLETED => 'secondary',
            self::EXPIRED => 'danger',
        };
    }
}
