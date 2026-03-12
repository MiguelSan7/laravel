<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google reCAPTCHA v3
    |--------------------------------------------------------------------------
    |
    | site_key   → clave pública, se envía al frontend
    | secret_key → clave privada, solo se usa en el backend para verificar
    | score      → umbral mínimo (0.0–1.0). Google recomienda 0.5
    |
    */

    'site_key' => env('RECAPTCHA_SITE_KEY'),
    'secret_key' => env('RECAPTCHA_SECRET_KEY'),
    'score' => env('RECAPTCHA_SCORE_THRESHOLD', 0.5),

];
