<?php

namespace App\Services\Payments;

use App\Models\OnlineOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Pago simulado para desarrollo y pruebas, sin credenciales de Izipay.
 *
 * La vista muestra "aprobar" y "rechazar"; cada opción va firmada con la
 * APP_KEY para que no se pueda aprobar un pedido editando el formulario.
 * PaymentGatewayManager impide usarlo en producción.
 */
class FakeGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'simulado';
    }

    public function checkout(OnlineOrder $order): array
    {
        return [
            'driver' => 'fake',
            'approve' => $this->sign($order->code, 'approved'),
            'refuse' => $this->sign($order->code, 'refused'),
        ];
    }

    public function parseReturn(Request $request): ?PaymentResult
    {
        $code = $request->input('order');
        $result = $request->input('result');
        $signature = $request->input('signature');

        if (! is_string($code) || ! in_array($result, ['approved', 'refused'], true) || ! is_string($signature)) {
            return null;
        }

        if (! hash_equals($this->sign($code, $result), $signature)) {
            return null;
        }

        $order = OnlineOrder::query()->where('code', $code)->first();

        return new PaymentResult(
            orderCode: $code,
            paid: $result === 'approved',
            transactionId: 'SIM-'.Str::upper(Str::random(12)),
            amountInCents: $order?->totalInCents() ?? 0,
            payload: ['simulated' => true, 'result' => $result],
        );
    }

    public function parseNotification(Request $request): ?PaymentResult
    {
        return null;
    }

    private function sign(string $code, string $result): string
    {
        return hash_hmac('sha256', "{$code}|{$result}", (string) config('app.key'));
    }
}
