<?php

namespace App\Enums;

enum FulfillmentType: string
{
    /** Delivery en Lima, a la dirección del cliente. */
    case DELIVERY = 'delivery';

    /** Envío a provincia por agencia Shalom: se recoge en la agencia con DNI o CE. */
    case PROVINCE = 'province';

    case PICKUP = 'pickup';

    public function label(): string
    {
        return match ($this) {
            self::DELIVERY => 'Delivery en Lima',
            self::PROVINCE => 'Envío a provincia (Shalom)',
            self::PICKUP => 'Recojo en el centro',
        };
    }

    /** Se envía (delivery o agencia) en lugar de recogerse en el centro. */
    public function isShipped(): bool
    {
        return $this !== self::PICKUP;
    }
}
