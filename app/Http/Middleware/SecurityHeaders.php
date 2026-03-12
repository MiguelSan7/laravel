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
        /** @var Response $response */
        $response = $next($request);

        // Content-Security-Policy
        // - default-src 'self': solo recursos del mismo origen por defecto
        // - script-src: permite Alpine.js inline y reCAPTCHA de Google
        // - style-src: permite estilos inline (necesario para Tailwind purge)
        // - img-src: permite data URIs y Google (para reCAPTCHA badge)
        // - frame-src: permite el iframe invisible de reCAPTCHA
        // - connect-src: permite llamadas AJAX a Google reCAPTCHA
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-".$this->getNonce()."' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https://www.gstatic.com/",
            "font-src 'self'",
            'frame-src https://www.google.com/recaptcha/ https://recaptcha.google.com/recaptcha/',
            "connect-src 'self' https://www.google.com/recaptcha/",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            'upgrade-insecure-requests',
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

    /**
     * Genera o recupera el nonce CSP de la sesión actual.
     * Se usa en las vistas via @nonce o {{ $nonce }}.
     */
    protected function getNonce(): string
    {
        if (! session()->has('csp_nonce')) {
            session(['csp_nonce' => base64_encode(random_bytes(16))]);
        }

        return session('csp_nonce');
    }
}
