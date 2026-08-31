<?php

namespace App\Exceptions;

/**
 * Impide dejar el sistema sin ningún administrador activo.
 *
 * Sin esta regla, quitarle el rol al último administrador —o desactivarlo—
 * dejaría el ERP sin nadie capaz de gestionar usuarios, y solo se podría
 * recuperar tocando la base de datos a mano.
 */
class LastAdministratorException extends BusinessException
{
    public static function make(string $action): self
    {
        return new self(
            "No se puede {$action}: es el único administrador activo del sistema. ".
            'Asigne el rol de Administrador a otro usuario antes de continuar.'
        );
    }
}
