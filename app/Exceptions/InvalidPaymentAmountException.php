<?php

namespace App\Exceptions;

use App\Models\Sale;

class InvalidPaymentAmountException extends BusinessException
{
    public static function exceedsBalance(Sale $sale, float $amount): self
    {
        $exception = new self(sprintf(
            'El pago de S/ %s supera el saldo pendiente de la venta (S/ %s).',
            number_format($amount, 2),
            number_format($sale->balance(), 2)
        ));

        $exception->context = [
            'sale_id' => $sale->id,
            'balance' => $sale->balance(),
            'amount' => $amount,
        ];

        return $exception;
    }

    public static function notPositive(): self
    {
        return new self('El monto del pago debe ser mayor que cero.');
    }

    public static function saleCancelled(Sale $sale): self
    {
        $exception = new self('No se pueden registrar pagos sobre una venta anulada.');
        $exception->context = ['sale_id' => $sale->id];

        return $exception;
    }
}
