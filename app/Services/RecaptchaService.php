<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecaptchaService
{
    protected string $secretKey;

    protected float $minScore;

    protected string $verifyUrl;

    public function __construct()
    {
        $this->secretKey = (string) config('recaptcha.secret_key');
        $this->minScore = (float) config('recaptcha.min_score', 0.5);
        $this->verifyUrl = (string) config('recaptcha.verify_url', 'https://www.google.com/recaptcha/api/siteverify');
    }

    /**
     * Verifica el token de reCAPTCHA v3 contra la API de Google.
     *
     * @param  string|null  $token  Token generado en el frontend
     * @param  string  $action  Nombre de la acción (login, register, etc.)
     */
    public function verify(?string $token, string $action = ''): bool
    {
        if (empty($token)) {
            Log::warning('reCAPTCHA: token vacío', ['action' => $action]);

            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post($this->verifyUrl, [
                    'secret' => $this->secretKey,
                    'response' => $token,
                    'remoteip' => request()->ip(),
                ]);

            if (! $response->successful()) {
                Log::warning('reCAPTCHA: respuesta HTTP no exitosa', [
                    'status' => $response->status(),
                    'action' => $action,
                ]);

                return false;
            }

            $body = $response->json();

            if (! ($body['success'] ?? false)) {
                Log::warning('reCAPTCHA: validación fallida', [
                    'errors' => $body['error-codes'] ?? [],
                    'action' => $action,
                ]);

                return false;
            }

            $score = (float) ($body['score'] ?? 0.0);

            // Verificamos que la acción coincida si fue enviada
            if (! empty($action) && isset($body['action']) && $body['action'] !== $action) {
                Log::warning('reCAPTCHA: acción no coincide', [
                    'expected' => $action,
                    'received' => $body['action'],
                ]);

                return false;
            }

            if ($score < $this->minScore) {
                Log::warning('reCAPTCHA: score insuficiente', [
                    'score' => $score,
                    'min' => $this->minScore,
                    'action' => $action,
                ]);

                return false;
            }

            return true;

        } catch (\Throwable $e) {
            // Si el servicio de Google no responde, fallamos de forma segura
            Log::error('reCAPTCHA: excepción al verificar', [
                'message' => $e->getMessage(),
                'action' => $action,
            ]);

            return false;
        }
    }
}
