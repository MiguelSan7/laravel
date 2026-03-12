<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'two_factor_enabled',
        'two_factor_code',
        'two_factor_expires_at',
        'two_factor_attempts',
        'two_factor_locked_until',
        'login_attempts',
        'locked_until',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_code',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'two_factor_enabled' => 'boolean',
        'two_factor_expires_at' => 'datetime',
        'two_factor_locked_until' => 'datetime',
        'locked_until' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    // ── 2FA ─────────────────────────────────────────────────────────────────

    /**
     * Genera y persiste un código numérico de 2FA.
     */
    public function generateTwoFactorCode(): string
    {
        $length = (int) config('app.two_factor_code_length', 6);
        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);

        $this->update([
            'two_factor_code' => $code,
            'two_factor_expires_at' => Carbon::now()->addMinutes((int) config('app.two_factor_code_expiry', 10)),
            'two_factor_attempts' => 0,
        ]);

        return $code;
    }

    /**
     * Limpia el código 2FA tras validación o expiración.
     */
    public function clearTwoFactorCode(): void
    {
        $this->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
            'two_factor_attempts' => 0,
        ]);
    }

    /**
     * Verifica si el código 2FA es válido y no ha expirado.
     */
    public function isTwoFactorCodeValid(string $code): bool
    {
        return $this->two_factor_code !== null
            && $this->two_factor_expires_at !== null
            && $this->two_factor_expires_at->isFuture()
            && hash_equals($this->two_factor_code, $code);
    }

    /**
     * Verifica si el usuario está bloqueado por demasiados intentos 2FA.
     */
    public function isTwoFactorLocked(): bool
    {
        return $this->two_factor_locked_until !== null
            && $this->two_factor_locked_until->isFuture();
    }

    // ── Bloqueo de cuenta ────────────────────────────────────────────────────

    public function isAccountLocked(): bool
    {
        return $this->locked_until !== null
            && $this->locked_until->isFuture();
    }

    public function incrementLoginAttempts(): void
    {
        $this->increment('login_attempts');
        if ($this->login_attempts >= 5) {
            $this->update(['locked_until' => Carbon::now()->addMinutes(15)]);
        }
    }

    public function resetLoginAttempts(): void
    {
        $this->update(['login_attempts' => 0, 'locked_until' => null]);
    }
}
