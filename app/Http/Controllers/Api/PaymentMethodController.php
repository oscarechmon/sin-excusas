<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentMethodRequest;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Los métodos de pago son configurables desde administración (§22): no están
 * quemados en el código, para poder agregar uno nuevo sin tocar el sistema.
 */
class PaymentMethodController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $methods = PaymentMethod::query()
            ->when($request->boolean('only_active', true), fn ($q) => $q->active())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->ok(PaymentMethodResource::collection($methods));
    }

    public function store(StorePaymentMethodRequest $request): JsonResponse
    {
        $method = PaymentMethod::create($request->validated());

        return $this->created(new PaymentMethodResource($method), 'Método de pago creado correctamente.');
    }

    public function update(StorePaymentMethodRequest $request, PaymentMethod $paymentMethod): JsonResponse
    {
        $paymentMethod->update($request->validated());

        return $this->ok(new PaymentMethodResource($paymentMethod), 'Método de pago actualizado correctamente.');
    }

    public function destroy(PaymentMethod $paymentMethod): JsonResponse
    {
        if ($paymentMethod->payments()->exists()) {
            $paymentMethod->update(['active' => false]);

            return $this->ok(null, 'El método tiene pagos registrados, por lo que se desactivó en lugar de eliminarse.');
        }

        $paymentMethod->delete();

        return $this->ok(null, 'Método de pago eliminado correctamente.');
    }
}
