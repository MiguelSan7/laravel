<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PreventBackHistory
 *
 * Previene que el navegador cachee páginas autenticadas.
 * Después del logout, si el usuario presiona "atrás" en el navegador,
 * el navegador debe hacer una nueva petición al servidor (donde ya no hay sesión)
 * en vez de mostrar la versión cacheada de la página.
 */
class PreventBackHistory
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
