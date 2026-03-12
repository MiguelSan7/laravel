<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureTwoFactorVerified
 *
 * Protege rutas que requieren que el usuario haya completado el 2FA.
 * Si el usuario está autenticado pero no ha verificado el 2FA en esta
 * sesión, lo redirige al formulario de verificación.
 */
class EnsureTwoFactorVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Si el usuario tiene 2FA habilitado y no lo ha verificado aún
        if ($user->two_factor_enabled && ! session()->get('two_factor_verified')) {
            // Evitamos redirección infinita si ya estamos en la ruta de 2FA
            if (! $request->routeIs('two-factor.*')) {
                return redirect()->route('two-factor.challenge');
            }
        }

        return $next($request);
    }
}
