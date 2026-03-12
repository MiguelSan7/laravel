<?php

namespace App\Traits;

use Illuminate\Support\Str;

/**
 * Trait SanitizesInput
 *
 * Provee métodos de sanitización reutilizables para FormRequests y Controllers.
 * Elimina vectores comunes de inyección: HTML, etiquetas, espacios fantasma,
 * caracteres de control y normaliza mayúsculas en emails.
 */
trait SanitizesInput
{
    /**
     * Sanitiza un campo de texto plano:
     * - Elimina etiquetas HTML y PHP
     * - Elimina caracteres de control (null bytes, etc.)
     * - Recorta espacios en los extremos
     */
    protected function sanitizeString(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        // strip_tags elimina HTML/PHP; preg_replace elimina chars de control U+0000–U+001F excepto LF/CR/TAB
        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? $value;

        return trim($value);
    }

    /**
     * Sanitiza un campo de email:
     * - Aplica sanitizeString()
     * - Convierte a minúsculas
     * - Elimina caracteres no válidos en emails según RFC
     */
    protected function sanitizeEmail(?string $value): string
    {
        $clean = $this->sanitizeString($value);

        return Str::lower(
            (string) filter_var($clean, FILTER_SANITIZE_EMAIL)
        );
    }

    /**
     * Sanitiza un campo numérico de texto (ej: código OTP):
     * - Extrae solo dígitos
     */
    protected function sanitizeDigits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value) ?? '';
    }
}
