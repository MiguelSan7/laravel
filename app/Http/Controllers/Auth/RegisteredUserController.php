<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RecaptchaService;
use App\Traits\SanitizesInput;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    use SanitizesInput;

    public function __construct(
        protected RecaptchaService $recaptcha,
    ) {}

    /**
     * Muestra el formulario de registro.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Procesa el registro de un nuevo usuario.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. reCAPTCHA v3
        if (! $this->recaptcha->verify($request->input('recaptcha_token'), 'register')) {
            return back()
                ->withInput($request->only('name', 'email'))
                ->withErrors(['email' => 'La verificación de seguridad falló. Inténtalo de nuevo.']);
        }

        // 2. Sanitizar antes de validar
        $name = $this->sanitizeString($request->input('name'));
        $email = $this->sanitizeEmail($request->input('email'));

        $request->merge(['name' => $name, 'email' => $email]);

        // 3. Validar
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email:rfc,dns', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // 4. Crear usuario con 2FA activado por defecto
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($request->password),
            'two_factor_enabled' => true,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
