<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\RecaptchaService;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        protected TwoFactorService $twoFactor,
        protected RecaptchaService $recaptcha,
    ) {}

    /**
     * Muestra el formulario de ingreso del código 2FA.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if (! session()->has('two_factor_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    /**
     * Verifica el código 2FA ingresado por el usuario.
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. reCAPTCHA v3
        if (! $this->recaptcha->verify($request->input('recaptcha_token'), 'two_factor')) {
            return back()->withErrors(['code' => 'La verificación de seguridad falló. Inténtalo de nuevo.']);
        }

        // 2. Validar que el código tenga el formato correcto
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        // 3. Recuperar el usuario de la sesión temporal
        $userId = session('two_factor_user_id');
        $user = User::find($userId);

        if (! $user) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Sesión expirada. Por favor inicia sesión nuevamente.']);
        }

        // 4. Verificar el código
        try {
            $valid = $this->twoFactor->verifyCode($user, $request->input('code'));
        } catch (\RuntimeException $e) {
            $messages = [
                'two_factor_locked' => 'Tu cuenta ha sido bloqueada temporalmente por demasiados intentos fallidos. Espera 30 minutos.',
                'two_factor_expired' => 'El código ha expirado. Por favor solicita uno nuevo.',
            ];

            return back()->withErrors(['code' => $messages[$e->getMessage()] ?? 'Ocurrió un error. Inténtalo de nuevo.']);
        }

        if (! $valid) {
            $user->refresh();
            $remaining = max(0, 5 - $user->two_factor_attempts);

            return back()->withErrors([
                'code' => "Código incorrecto. Te quedan {$remaining} intento(s) antes del bloqueo.",
            ]);
        }

        // 5. Login exitoso: autenticar, marcar 2FA en sesión y limpiar datos temporales
        Auth::login($user);
        $request->session()->regenerate();
        session()->forget('two_factor_user_id');
        session(['two_factor_verified' => true]);

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Reenvía un nuevo código 2FA.
     */
    public function resend(Request $request): RedirectResponse
    {
        $userId = session('two_factor_user_id');
        $user = User::find($userId);

        if (! $user) {
            return redirect()->route('login');
        }

        // Throttle: no permitir reenvío si el código aún no ha expirado
        if ($user->two_factor_expires_at && $user->two_factor_expires_at->isFuture()) {
            $seconds = now()->diffInSeconds($user->two_factor_expires_at);

            return back()->withErrors([
                'code' => "Ya tienes un código activo. Espera {$seconds} segundos antes de solicitar otro.",
            ]);
        }

        $this->twoFactor->sendCode($user);

        return back()->with('status', 'Se ha enviado un nuevo código de verificación a tu correo.');
    }
}
