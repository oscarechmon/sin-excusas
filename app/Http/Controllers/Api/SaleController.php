<?php

namespace App\Http\Controllers\Api;

use App\Actions\Payments\RegisterPaymentAction;
use App\Actions\Sales\CreateSaleAction;
use App\Enums\SaleStatus;
use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $sales = Sale::query()
            ->with('client')
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->integer('client_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($sales, SaleResource::class);
    }

    public function show(Sale $sale): JsonResponse
    {
        return $this->ok(new SaleResource($sale->load(
            'client', 'items.itemable', 'items.employee', 'payments.paymentMethod'
        )));
    }

    /**
     * Crea la venta y, si vienen pagos en el mismo payload, los registra.
     *
     * Todo comparte una transacción: una venta no puede quedar registrada a
     * medias si falla uno de sus pagos (§47).
     */
    public function store(
        StoreSaleRequest $request,
        CreateSaleAction $createSale,
        RegisterPaymentAction $registerPayment,
    ): JsonResponse {
        $sale = DB::transaction(function () use ($request, $createSale, $registerPayment) {
            $sale = $createSale->execute($request->validated(), $request->user()->id);

            foreach ($request->input('payments', []) as $payment) {
                $registerPayment->execute($sale, $payment, $request->user()->id);
            }

            return $sale;
        });

        return $this->created(
            new SaleResource($sale->fresh()->load('client', 'items.itemable', 'payments.paymentMethod')),
            'Venta registrada correctamente.'
        );
    }

    /** Registra un pago adicional; permite el pago mixto y los saldos (§22). */
    public function addPayment(
        StorePaymentRequest $request,
        Sale $sale,
        RegisterPaymentAction $action,
    ): JsonResponse {
        $payment = $action->execute($sale, $request->validated(), $request->user()->id);

        return $this->created([
            'payment' => new PaymentResource($payment),
            'sale' => new SaleResource($sale->fresh()->load('payments.paymentMethod')),
        ], 'Pago registrado correctamente.');
    }

    /**
     * Anula la venta. No se elimina: los movimientos financieros deben
     * conservar trazabilidad (§34).
     */
    public function cancel(Sale $sale): JsonResponse
    {
        if ($sale->status === SaleStatus::CANCELLED) {
            return $this->failed('La venta ya estaba anulada.');
        }

        $sale->update(['status' => SaleStatus::CANCELLED]);

        return $this->ok(new SaleResource($sale), 'Venta anulada correctamente.');
    }
}
