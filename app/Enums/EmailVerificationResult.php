<?php

namespace App\Enums;

enum EmailVerificationResult
{
    case VERIFIED;
    case INVALID;
    case EXPIRED;
    case LOCKED;

    public function message(): string
    {
        return match ($this) {
            self::VERIFIED => 'Correo verificado.',
            self::INVALID => 'El código no es correcto. Revisa tu correo e inténtalo de nuevo.',
            self::EXPIRED => 'El código venció. Solicita uno nuevo.',
            self::LOCKED => 'Demasiados intentos. Solicita un código nuevo.',
        };
    }
}
