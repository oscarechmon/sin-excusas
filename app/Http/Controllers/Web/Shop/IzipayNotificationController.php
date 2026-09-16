<?php

namespace App\Http\Controllers\Web\Shop;

use App\Actions\Shop\ConfirmOnlinePaymentAction;
use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * IPN de Izipay: aviso servidor a servidor del resultado del pago.
 *
 * Es la fuente confiable: confirma el pedido aunque el cliente cierre el
 * navegador antes de volver a la web. Configurar en el Back Office de Izipay
 * la URL https://<dominio>/pagos/izipay/notificacion.
 */
class IzipayNotificationController extends Controller
{
    public function __invoke(Request $request, PaymentGatewayManager $gateways, ConfirmOnlinePaymentAction $confirm): Response
    {
        $gateway = $gateways->driver();
        $result = $gateway->parseNotification($request);

        if (! $result) {
            Log::warning('IPN de Izipay con firma inválida.', ['ip' => $request->ip()]);

            return response('Firma inválida', 400);
        }

        $order = $confirm->execute($result, $gateway->name());

        if (! $order) {
            Log::warning('IPN de Izipay para un pedido inexistente.', ['order' => $result->orderCode]);
        }

        return response('OK');
    }
}
