<?php

namespace App\Exceptions;

use App\Models\ClientPackage;

class PackageWithoutRemainingSessionsException extends BusinessException
{
    public static function for(ClientPackage $clientPackage): self
    {
        $reason = match (true) {
            $clientPackage->isExpired() => 'el paquete está vencido',
            $clientPackage->remainingSessions() <= 0 => 'no le quedan sesiones disponibles',
            default => 'el paquete no está activo',
        };

        $exception = new self(sprintf(
            'No se puede consumir una sesión de "%s": %s.',
            $clientPackage->package_name,
            $reason
        ));

        $exception->context = [
            'client_package_id' => $clientPackage->id,
            'remaining_sessions' => $clientPackage->remainingSessions(),
            'status' => $clientPackage->status->value,
        ];

        return $exception;
    }
}
