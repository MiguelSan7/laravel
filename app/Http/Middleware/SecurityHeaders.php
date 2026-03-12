<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecurityHeaders
 *
 * Inyecta cabeceras HTTP de seguridad en todas las respuestas:
 *  - Content-Security-Policy (CSP) para prevenir XSS e inyección de recursos
 *  - X-Frame-Options para prevenir clickjacking
 *  - X-Content-Type-Options para prevenir MIME sniffing
 *  - Referrer-Policy para controlar información enviada al referrer
 *  - Permissions-Policy para desactivar APIs de navegador no usadas
 *  - Strict-Transport-Security (HSTS) para forzar HTTPS
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Nonce único por request — se comparte con las vistas ANTES de renderizar
        $nonce = base64_encode(random_bytes(16));
        view()->share('cspNonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        // Content-Security-Policy
        // - script-src: 'self' + nonce para scripts inline + dominios de reCAPTCHA
        //   Google carga reCAPTCHA desde google.com y archivos JS desde gstatic.com
        // - style-src: 'unsafe-inline' requerido por Tailwind CSS (clases dinámicas)
        // - img-src: gstatic.com para el badge de reCAPTCHA, bunny.net para fuentes
        // - font-src: bunny.net (fuente Figtree en guest layout)
        // - frame-src: iframes invisibles que crea el widget de reCAPTCHA
        // - connect-src: peticiones XHR del SDK de reCAPTCHA
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "img-src 'self' data: https://www.gstatic.com/",
            "font-src 'self' https://fonts.bunny.net",
            'frame-src https://www.google.com/recaptcha/ https://recaptcha.google.com/recaptcha/',
            "connect-src 'self' https://www.google.com/recaptcha/ https://www.gstatic.com/",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=()');

        // HSTS: 1 año, incluye subdominios, apto para preload
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        // Elimina cabeceras que revelan información del servidor
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
