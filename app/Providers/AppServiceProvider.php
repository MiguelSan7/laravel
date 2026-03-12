<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePasswordDefaults();
        $this->configureRateLimiting();
	URL::forceScheme('http');
    }

    /**
     * Configura las reglas de contraseña por defecto.
     *
     * Aplica en todos los formularios que usen Rules\Password::defaults().
     */
    protected function configurePasswordDefaults(): void
    {
        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(); // verifica contra HaveIBeenPwned API
        });
    }

    /**
     * Configura rate limiters personalizados para rutas de autenticación.
     */
    protected function configureRateLimiting(): void
    {
        // Login: máximo 5 intentos por IP + email, bloqueado 1 minuto
        RateLimiter::for('login', function (Request $request) {
            $key = strtolower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key)->response(function () {
                return back()
                    ->withInput()
                    ->withErrors(['email' => 'Demasiados intentos de inicio de sesión. Espera un minuto.']);
            });
        });

        // Register: máximo 3 registros por IP en 10 minutos (anti spam)
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinutes(10, 3)->by($request->ip());
        });

        // 2FA: máximo 10 intentos por IP en 5 minutos
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinutes(5, 10)->by($request->ip());
        });
    }
}
