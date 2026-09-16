<?php

namespace App\Services\Payments;

use App\Models\OnlineOrder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Izipay, formulario incrustado (API REST V4).
 *
 * 1. `checkout()` pide un formToken con el monto en céntimos.
 * 2. El navegador muestra el formulario de Izipay con ese token.
 * 3. Izipay devuelve `kr-answer` + `kr-hash` al navegador (firmado con la
 *    clave HMAC) y al servidor por IPN (firmado con la contraseña).
 *
 * El monto y el estado solo se confían tras validar la firma.
 */
class IzipayGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'izipay';
    }

    public function checkout(OnlineOrder $order): array
    {
        $this->ensureConfigured();

        try {
            $response = Http::withBasicAuth(config('izipay.username'), config('izipay.password'))
                ->acceptJson()
                ->timeout(15)
                ->post(rtrim(config('izipay.api_url'), '/').'/api-payment/V4/Charge/CreatePayment', [
                    'amount' => $order->totalInCents(),
                    'currency' => config('izipay.currency'),
                    'orderId' => $order->code,
                    'customer' => $this->customerPayload($order),
                ]);
        } catch (ConnectionException $e) {
            throw new PaymentGatewayException('Izipay no respondió: '.$e->getMessage(), previous: $e);
        }

        $token = $response->json('answer.formToken');

        if ($response->failed() || $response->json('status') !== 'SUCCESS' || ! $token) {
            throw new PaymentGatewayException('Izipay rechazó la creación del pago: '.$response->body());
        }

        return [
            'driver' => 'izipay',
            'formToken' => $token,
            'publicKey' => config('izipay.public_key'),
            'staticUrl' => rtrim(config('izipay.static_url'), '/'),
        ];
    }

    public function parseReturn(Request $request): ?PaymentResult
    {
        return $this->parse($request, (string) config('izipay.hmac_key'));
    }

    public function parseNotification(Request $request): ?PaymentResult
    {
        return $this->parse($request, (string) config('izipay.password'));
    }

    private function parse(Request $request, string $key): ?PaymentResult
    {
        $answer = $request->input('kr-answer');
        $hash = $request->input('kr-hash');

        if ($key === '' || ! is_string($answer) || ! is_string($hash)) {
            return null;
        }

        if (! hash_equals(hash_hmac('sha256', $answer, $key), $hash)) {
            return null;
        }

        $data = json_decode($answer, true);

        if (! is_array($data) || empty($data['orderDetails']['orderId'])) {
            return null;
        }

        return new PaymentResult(
            orderCode: (string) $data['orderDetails']['orderId'],
            paid: ($data['orderStatus'] ?? null) === 'PAID',
            transactionId: $data['transactions'][0]['uuid'] ?? null,
            amountInCents: (int) ($data['orderDetails']['orderTotalAmount'] ?? 0),
            payload: $data,
        );
    }

    /**
     * Datos del comprador para 3D Secure v2: cuanto más completos, más
     * probable es una autenticación sin fricción. Nombre y apellido se
     * separan porque Izipay los pide en campos distintos.
     *
     * @return array<string,mixed>
     */
    private function customerPayload(OnlineOrder $order): array
    {
        $parts = preg_split('/\s+/', trim($order->recipient_name), 2) ?: [];
        $identity = $order->clientUser?->document_number;

        return [
            'email' => $order->clientUser?->email,
            'reference' => (string) $order->client_user_id,
            'billingDetails' => array_filter([
                'firstName' => $parts[0] ?? $order->recipient_name,
                'lastName' => $parts[1] ?? null,
                'cellPhoneNumber' => $order->phone,
                'address' => $order->address,
                'city' => $order->district ? 'Lima' : null,
                'district' => $order->district,
                'country' => 'PE',
                'language' => 'es',
                'identityType' => $identity ? 'DNI' : null,
                'identityCode' => $identity,
            ]),
        ];
    }

    private function ensureConfigured(): void
    {
        foreach (['username', 'password', 'public_key', 'hmac_key'] as $key) {
            if (! config("izipay.{$key}")) {
                throw new PaymentGatewayException("Falta configurar IZIPAY_".strtoupper($key).' en el .env.');
            }
        }
    }
}
