<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\RecaptchaService;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        protected RecaptchaService $recaptcha,
        protected TwoFactorService $twoFactor,
    ) {}

    /**
     * Muestra el formulario de login.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Procesa el intento de login.
     *
     * Flujo:
     *  1. Validar reCAPTCHA v3
     *  2. Validar credenciales (LoginRequest)
     *  3. Verificar bloqueo de cuenta
     *  4. Si el usuario tiene 2FA, enviar código y redirigir al challenge
     *  5. Si no tiene 2FA, completar login directamente
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // 1. reCAPTCHA v3
        if (! $this->recaptcha->verify($request->input('recaptcha_token'), 'login')) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'La verificación de seguridad falló. Inténtalo de nuevo.']);
        }

        // 2. Autenticar (LoginRequest lanza ValidationException si falla)
        $request->authenticate();

        /** @var User $user */
        $user = Auth::user();

        // 3. Verificar bloqueo de cuenta
        if ($user->isAccountLocked()) {
            Auth::guard('web')->logout();

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Tu cuenta está temporalmente bloqueada por demasiados intentos fallidos.']);
        }

        $user->resetLoginAttempts();
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // 4. Si tiene 2FA habilitado, enviar código y redirigir
        if ($user->two_factor_enabled) {
            Auth::guard('web')->logout();           // deslogueamos hasta que pase el 2FA
            $request->session()->invalidate();      // limpia la sesión vieja
            $request->session()->regenerateToken();  // nuevo CSRF token
            $request->session()->regenerate();       // nueva sesión limpia
            session(['two_factor_user_id' => $user->id]); // guardar DESPUÉS de regenerar
            $this->twoFactor->sendCode($user);

            return redirect()->route('two-factor.challenge')
                ->with('status', 'Se ha enviado un código de verificación a tu correo electrónico.');
        }

        // 5. Login directo sin 2FA
        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
