<?php

namespace App\Exceptions;

class CashSessionNotOpenException extends BusinessException
{
    public static function make(): self
    {
        return new self(
            'No hay una caja abierta. Abra la caja para registrar movimientos.'
        );
    }
}
