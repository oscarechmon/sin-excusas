<?php

namespace App\Services\Payments;

/** Resuelve la pasarela configurada en config/izipay.php. */
class PaymentGatewayManager
{
    public function driver(): PaymentGateway
    {
        return match (config('izipay.driver')) {
            'izipay' => app(IzipayGateway::class),
            'fake' => app()->isProduction()
                ? throw new PaymentGatewayException('El pago simulado no puede usarse en producción.')
                : app(FakeGateway::class),
            default => throw new PaymentGatewayException('No hay una pasarela de pago configurada.'),
        };
    }
}
