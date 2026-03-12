<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts (self-hosted, no Google Fonts CDN para cumplir CSP) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts + Styles compilados por Vite -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- reCAPTCHA v3 — se carga en todas las páginas de autenticación -->
        <script src="https://www.google.com/recaptcha/api.js?render={{ config('recaptcha.site_key') }}" nonce="{{ $cspNonce }}" async defer></script>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>

            <!-- Aviso legal reCAPTCHA (requerido por Google) -->
            <p class="mt-4 text-xs text-gray-500 text-center max-w-sm">
                Este sitio está protegido por reCAPTCHA y aplica la
                <a href="https://policies.google.com/privacy" class="underline" target="_blank" rel="noopener noreferrer">Política de Privacidad</a>
                y los
                <a href="https://policies.google.com/terms" class="underline" target="_blank" rel="noopener noreferrer">Términos de Servicio</a>
                de Google.
            </p>
        </div>
    </body>
</html>
