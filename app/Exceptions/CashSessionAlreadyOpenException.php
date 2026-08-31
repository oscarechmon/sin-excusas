<?php

namespace App\Exceptions;

use App\Models\CashSession;

class CashSessionAlreadyOpenException extends BusinessException
{
    public static function for(CashSession $session): self
    {
        $exception = new self(
            'Ya existe una caja abierta. Debe cerrarla antes de abrir una nueva.'
        );

        $exception->context = [
            'cash_session_id' => $session->id,
            'opened_at' => $session->opened_at?->toDateTimeString(),
        ];

        return $exception;
    }
}
