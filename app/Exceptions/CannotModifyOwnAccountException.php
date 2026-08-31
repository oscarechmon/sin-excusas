<?php

namespace App\Exceptions;

/**
 * Evita que un usuario se bloquee a sí mismo el acceso.
 *
 * Cambiar la propia contraseña o el propio nombre sí está permitido; lo que
 * se impide es desactivarse, eliminarse o quitarse los propios roles.
 */
class CannotModifyOwnAccountException extends BusinessException
{
    public static function make(string $action): self
    {
        return new self("No puede {$action} su propia cuenta.");
    }
}
