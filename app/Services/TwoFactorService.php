<?php

namespace App\Services;

use App\Mail\TwoFactorCodeMail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class TwoFactorService
{
    /**
     * Envía el código 2FA al email del usuario.
     */
    public function sendCode(User $user): void
    {
        $code = $user->generateTwoFactorCode();
        Mail::to($user->email)->send(new TwoFactorCodeMail($code));
    }

    /**
     * Valida el código ingresado por el usuario.
     *
     * @throws \RuntimeException si la cuenta está bloqueada por intentos fallidos
     */
    public function verifyCode(User $user, string $inputCode): bool
    {
        // Verificar bloqueo por demasiados intentos 2FA
        if ($user->isTwoFactorLocked()) {
            throw new \RuntimeException('two_factor_locked');
        }

        // Verificar expiración
        if ($user->two_factor_expires_at === null || $user->two_factor_expires_at->isPast()) {
            $user->clearTwoFactorCode();
            throw new \RuntimeException('two_factor_expired');
        }

        if (! $user->isTwoFactorCodeValid($inputCode)) {
            $this->handleFailedAttempt($user);

            return false;
        }

        $user->clearTwoFactorCode();

        return true;
    }

    /**
     * Maneja un intento fallido de 2FA.
     * Bloquea al usuario después de 5 intentos consecutivos.
     */
    private function handleFailedAttempt(User $user): void
    {
        $user->increment('two_factor_attempts');
        $user->refresh();

        if ($user->two_factor_attempts >= 5) {
            $user->update([
                'two_factor_locked_until' => Carbon::now()->addMinutes(30),
                'two_factor_code' => null,
                'two_factor_expires_at' => null,
                'two_factor_attempts' => 0,
            ]);
        }
    }
}
