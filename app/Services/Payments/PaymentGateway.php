<?php

namespace App\Services\Payments;

use App\Models\OnlineOrder;
use Illuminate\Http\Request;

/**
 * Contrato de una pasarela de pagos.
 *
 * El checkout depende de esta interfaz y no de Izipay directamente, de modo
 * que cambiar o sumar una pasarela no toca los controladores.
 */
interface PaymentGateway
{
    public function name(): string;

    /**
     * Datos que necesita la vista de pago (formulario, token, claves...).
     *
     * @return array<string,mixed>
     *
     * @throws PaymentGatewayException
     */
    public function checkout(OnlineOrder $order): array;

    /** Respuesta que vuelve con el navegador del cliente; null si la firma no es válida. */
    public function parseReturn(Request $request): ?PaymentResult;

    /** Notificación servidor a servidor (IPN); null si la firma no es válida. */
    public function parseNotification(Request $request): ?PaymentResult;
}
