<?php

namespace App\Services\Shop;

use App\Enums\EmailVerificationResult;
use App\Mail\ClientVerificationCodeMail;
use App\Models\ClientUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Código de verificación del correo al registrarse.
 *
 * Se pide una única vez: al verificar, la cuenta queda marcada y el código
 * se borra. Un código de 6 dígitos es adivinable por fuerza bruta, por eso
 * vence pronto y se invalida tras unos pocos intentos fallidos.
 */
class EmailVerificationService
{
    public const CODE_TTL_MINUTES = 15;

    public const MAX_ATTEMPTS = 5;

    public const RESEND_COOLDOWN_SECONDS = 60;

    public function send(ClientUser $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->forceFill([
            'verification_code' => Hash::make($code),
            'verification_code_expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
            'verification_sent_at' => now(),
            'verification_attempts' => 0,
        ])->save();

        Mail::to($user->email)->send(new ClientVerificationCodeMail($user, $code, self::CODE_TTL_MINUTES));
    }

    /** Segundos que faltan para poder pedir otro código. */
    public function secondsUntilResend(ClientUser $user): int
    {
        if (! $user->verification_sent_at) {
            return 0;
        }

        $elapsed = (int) abs($user->verification_sent_at->diffInSeconds(now()));

        return max(0, self::RESEND_COOLDOWN_SECONDS - $elapsed);
    }

    public function verify(ClientUser $user, string $code): EmailVerificationResult
    {
        if ($user->hasVerifiedEmail()) {
            return EmailVerificationResult::VERIFIED;
        }

        if (! $user->verification_code || $user->verification_code_expires_at?->isPast()) {
            return EmailVerificationResult::EXPIRED;
        }

        if ($user->verification_attempts >= self::MAX_ATTEMPTS) {
            return EmailVerificationResult::LOCKED;
        }

        if (! Hash::check($code, $user->verification_code)) {
            $user->increment('verification_attempts');

            return $user->verification_attempts >= self::MAX_ATTEMPTS
                ? EmailVerificationResult::LOCKED
                : EmailVerificationResult::INVALID;
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
            'verification_attempts' => 0,
        ])->save();

        return EmailVerificationResult::VERIFIED;
    }
}
