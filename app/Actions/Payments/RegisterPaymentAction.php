<?php

namespace App\Actions\Payments;

use App\Enums\SaleStatus;
use App\Exceptions\InvalidPaymentAmountException;
use App\Models\Payment;
use App\Models\Sale;
use App\Services\CashService;
use Illuminate\Support\Facades\DB;

/**
 * Registra un pago sobre una venta (§22).
 *
 * Admite pago mixto: se llama una vez por cada método. El saldo se recalcula
 * dentro de la transacción y con bloqueo, de modo que dos peticiones
 * simultáneas no puedan dejar la venta sobrepagada (§47).
 */
class RegisterPaymentAction
{
    public function __construct(private readonly CashService $cash) {}

    /**
     * @param  array<string,mixed>  $data  Payload validado por StorePaymentRequest.
     */
    public function execute(Sale $sale, array $data, ?int $userId = null): Payment
    {
        return DB::transaction(function () use ($sale, $data, $userId) {
            $locked = Sale::whereKey($sale->getKey())->lockForUpdate()->firstOrFail();

            $amount = round((float) $data['amount'], 2);

            $this->assertPayable($locked, $amount);

            $payment = $locked->payments()->create([
                'payment_method_id' => $data['payment_method_id'],
                'amount' => $amount,
                'reference' => $data['reference'] ?? null,
                'paid_at' => $data['paid_at'] ?? now(),
                'created_by' => $userId,
            ]);

            $paid = round((float) $locked->paid_amount + $amount, 2);

            $locked->forceFill([
                'paid_amount' => $paid,
                'status' => SaleStatus::fromAmounts((float) $locked->total, $paid),
            ])->save();

            // El ingreso a caja forma parte de la misma transacción: no puede
            // quedar un pago registrado sin su movimiento de caja (§11).
            $this->cash->registerPayment($payment, $userId);

            $sale->refresh();

            return $payment->load('paymentMethod');
        });
    }

    private function assertPayable(Sale $sale, float $amount): void
    {
        if ($sale->status === SaleStatus::CANCELLED) {
            throw InvalidPaymentAmountException::saleCancelled($sale);
        }

        if ($amount <= 0) {
            throw InvalidPaymentAmountException::notPositive();
        }

        if ($amount > $sale->balance()) {
            throw InvalidPaymentAmountException::exceedsBalance($sale, $amount);
        }
    }
}
