<?php

namespace App\Enums;

enum FulfillmentType: string
{
    case DELIVERY = 'delivery';
    case PICKUP = 'pickup';

    public function label(): string
    {
        return match ($this) {
            self::DELIVERY => 'Delivery',
            self::PICKUP => 'Recojo en el centro',
        };
    }
}
