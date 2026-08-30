<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case ATTENDED = 'attended';
    case CANCELLED = 'cancelled';
    case POSTPONED = 'postponed';
    case NO_SHOW = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::CONFIRMED => 'Confirmada',
            self::ATTENDED => 'Atendida',
            self::CANCELLED => 'Cancelada',
            self::POSTPONED => 'Pospuesta',
            self::NO_SHOW => 'No presentado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'info',
            self::ATTENDED => 'success',
            self::CANCELLED => 'danger',
            self::POSTPONED => 'secondary',
            self::NO_SHOW => 'danger',
        };
    }
}
