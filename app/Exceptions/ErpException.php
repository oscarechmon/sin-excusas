<?php

namespace App\Exceptions;

/** El sistema (ERP) no respondió o rechazó la operación. */
class ErpException extends BusinessException
{
    public static function unavailable(): self
    {
        $exception = new self('No se pudo conectar con el sistema. Intenta de nuevo en unos minutos.');
        $exception->status = 503;

        return $exception;
    }

    /** El sistema contestó con un error: su mensaje ya está en español. */
    public static function rejected(string $message, int $status): self
    {
        $exception = new self($message);
        // Un 5xx del sistema es un problema allá, no un dato mal ingresado aquí.
        $exception->status = $status >= 500 ? 502 : 422;

        return $exception;
    }

    public static function notLinked(string $name): self
    {
        return new self("«{$name}» no está vinculado al sistema. Ejecuta la sincronización del catálogo.");
    }

    /** Lo que antes se hacía en este panel ahora se hace en el sistema. */
    public static function managedInErp(string $what): self
    {
        return self::movedToErp("{$what} se administra");
    }

    /** @param  string  $sentence  Sujeto y verbo, p. ej. "Los clientes se administran". */
    public static function movedToErp(string $sentence): self
    {
        $exception = new self("{$sentence} en el sistema: ".config('erp.url'));
        $exception->status = 409;

        return $exception;
    }
}
