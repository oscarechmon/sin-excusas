<?php

namespace App\Enums;

enum OnlineOrderStatus: string
{
    case PENDING_PAYMENT = 'pending_payment';
    case PAYMENT_FAILED = 'payment_failed';
    case PAID = 'paid';
    case PREPARING = 'preparing';
    case SHIPPED = 'shipped';
    case READY_FOR_PICKUP = 'ready_for_pickup';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING_PAYMENT => 'Pendiente de pago',
            self::PAYMENT_FAILED => 'Pago rechazado',
            self::PAID => 'Pagado',
            self::PREPARING => 'En preparación',
            self::SHIPPED => 'En camino',
            self::READY_FOR_PICKUP => 'Listo para recoger',
            self::DELIVERED => 'Entregado',
            self::CANCELLED => 'Anulado',
        };
    }

    /** Severidad de PrimeVue para el panel. */
    public function color(): string
    {
        return match ($this) {
            self::PENDING_PAYMENT => 'warn',
            self::PAYMENT_FAILED => 'danger',
            self::PAID, self::PREPARING => 'info',
            self::SHIPPED, self::READY_FOR_PICKUP => 'contrast',
            self::DELIVERED => 'success',
            self::CANCELLED => 'secondary',
        };
    }

    /**
     * Estados a los que el personal puede mover el pedido. "Pagado" no está
     * en ninguna lista: solo lo fija la pasarela al confirmar el cobro.
     *
     * @return array<int,self>
     */
    public function nextStatuses(FulfillmentType $fulfillment): array
    {
        return match ($this) {
            self::PENDING_PAYMENT, self::PAYMENT_FAILED => [self::CANCELLED],
            self::PAID => [self::PREPARING, self::CANCELLED],
            self::PREPARING => [
                $fulfillment === FulfillmentType::DELIVERY ? self::SHIPPED : self::READY_FOR_PICKUP,
                self::CANCELLED,
            ],
            self::SHIPPED, self::READY_FOR_PICKUP => [self::DELIVERED],
            self::DELIVERED, self::CANCELLED => [],
        };
    }

    /** El cobro ya fue confirmado (el pedido está en curso o entregado). */
    public function isPaid(): bool
    {
        return in_array($this, [self::PAID, self::PREPARING, self::SHIPPED, self::READY_FOR_PICKUP, self::DELIVERED], true);
    }

    public function awaitsPayment(): bool
    {
        return in_array($this, [self::PENDING_PAYMENT, self::PAYMENT_FAILED], true);
    }

    /**
     * Pasos que ve el cliente en su seguimiento.
     *
     * @return array<int,self>
     */
    public static function trackingSteps(FulfillmentType $fulfillment): array
    {
        return [
            self::PAID,
            self::PREPARING,
            $fulfillment === FulfillmentType::DELIVERY ? self::SHIPPED : self::READY_FOR_PICKUP,
            self::DELIVERED,
        ];
    }
}
